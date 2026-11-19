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
            Agenda Calendar
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

    @if($eventsByDate->isEmpty())
        <div class="rounded-xl border bg-white p-4 text-sm text-slate-500">
            No hearings or task deadlines are recorded for this month.
        </div>
    @endif

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
                        $visibleEvents = $viewMode === 'month'
                            ? $dayEvents->take(3)
                            : $dayEvents;
                        $isCurrentMonth = $day->month === $month->month;
                        $isToday = $day->isToday();
                    @endphp

                    <div class="{{ $viewMode === 'agenda' ? 'min-h-[220px]' : 'min-h-[145px]' }} border-b border-r p-2 {{ $isCurrentMonth ? 'bg-white' : 'bg-slate-50' }}">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold
                                {{ $isToday ? 'bg-blue-600 text-white' : ($isCurrentMonth ? 'text-slate-800' : 'text-slate-400') }}">
                                {{ $day->day }}
                            </span>
                            @if($dayEvents->isNotEmpty())
                                <span class="text-xs text-slate-400">{{ $dayEvents->count() }} event(s)</span>
                            @endif
                        </div>

                        <div class="space-y-1">
                            @foreach($visibleEvents as $event)
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
                                   class="block rounded px-2 py-1 text-xs font-medium {{ $eventClass }} {{ $viewMode === 'month' ? 'truncate' : '' }}">
                                    @if($viewMode === 'agenda')
                                        <span class="block font-bold">
                                            {{ $event['label'] }}@if(!empty($event['time'])) - {{ $event['time'] }}@endif
                                        </span>
                                        <span class="block">{{ $event['title'] }}</span>
                                        @if(!empty($event['case_number']))
                                            <span class="block">Case: {{ $event['case_number'] }}</span>
                                        @endif
                                        @if(!empty($event['client_name']))
                                            <span class="block">Client: {{ $event['client_name'] }}</span>
                                        @endif
                                        @if(!empty($event['details']))
                                            <span class="mt-1 block">{{ $event['details'] }}</span>
                                        @endif
                                    @else
                                        @if(!empty($event['time']))
                                            {{ $event['time'] }} -
                                        @endif
                                        {{ $event['title'] }}
                                    @endif
                                </a>
                            @endforeach

                            @if($viewMode === 'month' && $dayEvents->count() > 3)
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

</div>
@endsection