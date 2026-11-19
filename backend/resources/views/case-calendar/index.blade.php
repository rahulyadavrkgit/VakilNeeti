@extends('layouts.app')

@section('title', 'Calendar & Hearings')
@section('page-heading', 'Calendar & Hearings')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Calendar & Hearings</h2>
            <p class="text-sm text-slate-500">
                Court hearings, next hearing dates and task deadlines.
            </p>
        </div>

        <a href="{{ route('tasks.create') }}"
           class="rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white">
            + Create Task
        </a>
    </div>

    <!-- Month navigation -->
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-white p-4">
        <a href="{{ route('cases.calendar', ['month' => $previousMonth, 'view' => $viewMode]) }}"
           class="rounded-lg border px-4 py-2 text-sm font-semibold text-slate-700">
            &larr; Previous
        </a>

        <h3 class="text-lg font-bold text-slate-900">
            {{ $month->format('F Y') }}
        </h3>

        <a href="{{ route('cases.calendar', ['month' => $nextMonth, 'view' => $viewMode]) }}"
           class="rounded-lg border px-4 py-2 text-sm font-semibold text-slate-700">
            Next &rarr;
        </a>
    </div>

    <!-- View selector -->
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('cases.calendar', ['month' => $month->format('Y-m'), 'view' => 'month']) }}"
           class="rounded-lg px-5 py-3 text-sm font-semibold {{ $viewMode === 'month' ? 'bg-blue-600 text-white' : 'border bg-white text-slate-700' }}">
            Monthly Calendar
        </a>

        <a href="{{ route('cases.calendar', ['month' => $month->format('Y-m'), 'view' => 'agenda']) }}"
           class="rounded-lg px-5 py-3 text-sm font-semibold {{ $viewMode === 'agenda' ? 'bg-blue-600 text-white' : 'border bg-white text-slate-700' }}">
            Agenda / List
        </a>
    </div>

    <!-- Event legend -->
    <div class="flex flex-wrap gap-4 rounded-xl border bg-white p-4 text-sm">
        <span class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-blue-600"></span>
            Court Hearing
        </span>
        <span class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-purple-600"></span>
            Next Hearing
        </span>
        <span class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
            Task Deadline
        </span>
    </div>

    @if($viewMode === 'month')

        <!-- MONTHLY CALENDAR GRID -->
        <div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
            <div class="min-w-[850px]">

                <div class="grid grid-cols-7 border-b bg-slate-100">
                    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $weekday)
                        <div class="p-3 text-center text-xs font-bold uppercase text-slate-600">
                            {{ $weekday }}
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7">
                    @foreach($calendarDays as $day)
                        @php
                            $dateKey = $day->toDateString();
                            $dayEvents = $eventsByDate->get($dateKey, collect());
                            $isCurrentMonth = $day->month === $month->month;
                            $isToday = $day->isToday();
                        @endphp

                        <div class="min-h-[145px] border-b border-r p-2 {{ $isCurrentMonth ? 'bg-white' : 'bg-slate-50' }}">

                            <div class="mb-2 flex items-center justify-between">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold
                                    {{ $isToday ? 'bg-blue-600 text-white' : ($isCurrentMonth ? 'text-slate-800' : 'text-slate-400') }}">
                                    {{ $day->day }}
                                </span>

                                @if($dayEvents->isNotEmpty())
                                    <span class="text-xs text-slate-400">
                                        {{ $dayEvents->count() }} event(s)
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-1">
                                @foreach($dayEvents->take(3) as $event)

                                    @php
                                        $eventClass = match($event['type']) {
                                            'hearing' => 'bg-blue-100 text-blue-800',
                                            'next_hearing' => 'bg-purple-100 text-purple-800',
                                            default => 'bg-amber-100 text-amber-800',
                                        };

                                        $eventUrl = $event['type'] === 'task'
                                            ? route('tasks.show', $event['id'])
                                            : (!empty($event['case_id'])
                                                ? route('cases.show', $event['case_id'])
                                                : '#');
                                    @endphp

                                    <a href="{{ $eventUrl }}"
                                       title="{{ $event['title'] }}"
                                       class="block truncate rounded px-2 py-1 text-xs font-medium {{ $eventClass }}">
                                        @if(!empty($event['time']))
                                            {{ $event['time'] }} -
                                        @endif
                                        {{ $event['title'] }}
                                    </a>

                                @endforeach

                                @if($dayEvents->count() > 3)
                                    <a href="{{ route('cases.calendar', ['month' => $month->format('Y-m'), 'view' => 'agenda']) }}"
                                       class="block px-2 text-xs font-semibold text-blue-700 hover:underline">
                                        + {{ $dayEvents->count() - 3 }} more
                                    </a>
                                @endif
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    @else

        <!-- AGENDA / LIST VIEW -->
        @if($eventsByDate->isEmpty())
            <div class="rounded-xl border bg-white p-10 text-center">
                <h3 class="font-semibold text-slate-800">No events for this month</h3>
                <p class="mt-2 text-sm text-slate-500">
                    Hearings and task deadlines will appear here when recorded.
                </p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($eventsByDate as $date => $dayEvents)
                    <section class="overflow-hidden rounded-xl border bg-white shadow-sm">

                        <div class="border-b bg-slate-50 px-5 py-3">
                            <h3 class="font-bold text-slate-900">
                                {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
                            </h3>
                        </div>

                        <div class="divide-y">
                            @foreach($dayEvents as $event)
                                <div class="flex flex-wrap items-start justify-between gap-4 p-5">

                                    <div class="min-w-0 flex-1">
                                        <div class="mb-2 flex flex-wrap items-center gap-2">

                                            @if($event['type'] === 'hearing')
                                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
                                                    Court Hearing
                                                </span>
                                            @elseif($event['type'] === 'next_hearing')
                                                <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-800">
                                                    Next Hearing
                                                </span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                                                    Task Deadline
                                                </span>
                                            @endif

                                            @if(!empty($event['time']))
                                                <span class="text-xs text-slate-500">{{ $event['time'] }}</span>
                                            @endif
                                        </div>

                                        <h4 class="font-semibold text-slate-900">
                                            {{ $event['title'] }}
                                        </h4>

                                        @if(!empty($event['case_number']))
                                            <p class="mt-1 text-sm text-slate-600">
                                                Case No.: {{ $event['case_number'] }}
                                            </p>
                                        @endif

                                        @if(!empty($event['client_name']))
                                            <p class="mt-1 text-sm text-slate-600">
                                                Client: {{ $event['client_name'] }}
                                            </p>
                                        @endif

                                        @if(!empty($event['details']))
                                            <p class="mt-1 text-sm text-slate-500">
                                                {{ $event['details'] }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap gap-3 text-sm">
                                        @if($event['type'] === 'task')
                                            <a href="{{ route('tasks.show', $event['id']) }}"
                                               class="font-semibold text-blue-700 hover:underline">
                                                View Task
                                            </a>
                                        @elseif(!empty($event['case_id']))
                                            <a href="{{ route('cases.show', $event['case_id']) }}"
                                               class="font-semibold text-blue-700 hover:underline">
                                                View Case
                                            </a>
                                        @endif
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif

    @endif

</div>
@endsection