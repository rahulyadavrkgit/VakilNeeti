@extends('layouts.app')

@section('title', 'Calendar')
@section('page-heading', 'Calendar')

@php
    $calendarUrl = fn (array $overrides = []) => route('cases.calendar', array_merge($query, ['view' => $viewMode, 'date' => $date->toDateString()], $overrides));
    $groupLabels = \App\Services\CalendarEventService::GROUPS;
    $hasFilters = $filters['court'] || $filters['case_type'] || $selectedGroups;
@endphp

@section('content')
<div class="mx-auto max-w-[1500px] space-y-5">
    @if($printList)
        <div data-autoprint hidden></div>
    @endif

    @if(session('success'))
        <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 print:hidden">{{ session('success') }}</div>
    @endif

    <div class="hidden print:block">
        <h1 class="text-xl font-bold text-slate-900">Calendar · {{ $periodLabel }}</h1>
        <p class="text-sm text-slate-600">
            {{ auth()->user()->name }} · printed {{ now()->format('d M Y') }}
            @if($filters['court']) · Court: {{ $filters['court'] }} @endif
            @if($filters['case_type']) · Case type: {{ $filters['case_type'] }} @endif
        </p>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $calendarUrl(['date' => today()->toDateString()]) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Today</a>
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white">
                <a href="{{ $calendarUrl(['date' => $previousDate]) }}" class="inline-flex min-h-10 items-center px-3 text-slate-700 hover:bg-slate-50" aria-label="Previous">&larr;</a>
                <a href="{{ $calendarUrl(['date' => $nextDate]) }}" class="inline-flex min-h-10 items-center border-l border-slate-200 px-3 text-slate-700 hover:bg-slate-50" aria-label="Next">&rarr;</a>
            </div>
            <h2 class="text-lg font-bold text-slate-900">{{ $periodLabel }}</h2>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <nav class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white" aria-label="Calendar view">
                @foreach(\App\Http\Controllers\CaseCalendarController::VIEWS as $key => $label)
                    <a href="{{ $calendarUrl(['view' => $key]) }}"
                       @if($viewMode === $key) aria-current="page" @endif
                       class="inline-flex min-h-10 items-center px-3 text-sm font-semibold {{ $loop->first ? '' : 'border-l border-slate-200' }} {{ $viewMode === $key ? 'bg-[#16776e] text-white' : 'text-slate-700 hover:bg-slate-50' }}">{{ $label }}</a>
                @endforeach
            </nav>

            <details class="relative">
                <summary class="inline-flex min-h-10 cursor-pointer list-none items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Print ▾</summary>
                <div class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg">
                    <button type="button" data-print class="block w-full px-4 py-2 text-left text-slate-700 hover:bg-slate-50">Print this view</button>
                    <a href="{{ $calendarUrl(['view' => 'list', 'print' => 1]) }}" class="block px-4 py-2 text-slate-700 hover:bg-slate-50">Print as a list (month)</a>
                    <a href="{{ $calendarUrl(['view' => 'list', 'print' => 1, 'types' => ['hearing']]) }}" class="block px-4 py-2 text-slate-700 hover:bg-slate-50">Print hearings only</a>
                </div>
            </details>

            <a href="{{ route('hearings.create') }}" class="inline-flex min-h-10 items-center rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">+ Add hearing</a>
            <a href="{{ route('tasks.create') }}" class="inline-flex min-h-10 items-center rounded-lg border border-[#16776e] px-4 text-sm font-semibold text-[#16776e] hover:bg-teal-50">+ Task</a>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="space-y-4 print:hidden">
            {{-- Small month picker --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <a href="{{ route('cases.calendar', array_merge($query, ['view' => $viewMode, 'date' => $date->copy()->subMonthNoOverflow()->toDateString()])) }}" class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100" aria-label="Previous month">&lsaquo;</a>
                    <p class="text-sm font-bold text-slate-900">{{ $date->format('F Y') }}</p>
                    <a href="{{ route('cases.calendar', array_merge($query, ['view' => $viewMode, 'date' => $date->copy()->addMonthNoOverflow()->toDateString()])) }}" class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100" aria-label="Next month">&rsaquo;</a>
                </div>
                <div class="mt-3 grid grid-cols-7 text-center text-[11px] font-semibold uppercase text-slate-400">
                    @foreach(['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $weekday)
                        <span>{{ $weekday }}</span>
                    @endforeach
                </div>
                <div class="mt-1 grid grid-cols-7 gap-y-1 text-center text-sm">
                    @foreach($miniDays as $miniDay)
                        @php
                            $isSelected = $miniDay->isSameDay($date);
                            $inMonth = $miniDay->month === $date->month;
                        @endphp
                        <a href="{{ route('cases.calendar', array_merge($query, ['view' => 'day', 'date' => $miniDay->toDateString()])) }}"
                           class="relative mx-auto flex h-8 w-8 items-center justify-center rounded-full
                                  {{ $isSelected ? 'bg-[#16776e] font-bold text-white' : ($miniDay->isToday() ? 'font-bold text-[#16776e] ring-1 ring-[#16776e]' : ($inMonth ? 'text-slate-700 hover:bg-slate-100' : 'text-slate-300 hover:bg-slate-50')) }}">
                            {{ $miniDay->day }}
                            @if($miniEventDates->has($miniDay->toDateString()))
                                <span class="absolute bottom-0.5 h-1 w-1 rounded-full {{ $isSelected ? 'bg-white' : 'bg-[#16776e]' }}"></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Filters --}}
            <form method="GET" action="{{ route('cases.calendar') }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <input type="hidden" name="view" value="{{ $viewMode }}">
                <input type="hidden" name="date" value="{{ $date->toDateString() }}">

                <fieldset>
                    <legend class="text-sm font-bold text-slate-900">Show</legend>
                    <div class="mt-2 space-y-1.5">
                        @foreach($groupLabels as $groupKey => $groupLabel)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="types[]" value="{{ $groupKey }}" data-autosubmit
                                       @checked(in_array($groupKey, $filters['groups'], true))
                                       class="rounded border-slate-300 text-[#16776e] focus:ring-[#16776e]">
                                <span class="h-2.5 w-2.5 rounded-full {{ ['hearing' => 'bg-teal-600', 'appointment' => 'bg-sky-500', 'task' => 'bg-amber-500'][$groupKey] }}"></span>
                                {{ $groupLabel }}
                                <span class="ml-auto text-xs text-slate-400">{{ $counts[$groupKey] ?? 0 }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <label for="calendar-court" class="text-sm font-bold text-slate-900">Court</label>
                    <select id="calendar-court" name="court" data-autosubmit class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                        <option value="">All courts</option>
                        @foreach($filterOptions['courts'] as $court)
                            <option value="{{ $court }}" @selected($filters['court'] === $court)>{{ $court }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="calendar-case-type" class="text-sm font-bold text-slate-900">Case type</label>
                    <select id="calendar-case-type" name="case_type" data-autosubmit class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                        <option value="">All case types</option>
                        @foreach($filterOptions['case_types'] as $caseType)
                            <option value="{{ $caseType }}" @selected($filters['case_type'] === $caseType)>{{ $caseType }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <noscript><button type="submit" class="rounded-lg bg-[#16776e] px-3 py-2 text-sm font-semibold text-white">Apply</button></noscript>
                    @if($hasFilters)
                        <a href="{{ route('cases.calendar', ['view' => $viewMode, 'date' => $date->toDateString()]) }}" class="text-sm font-semibold text-[#16776e] hover:underline">Clear filters</a>
                    @endif
                </div>
            </form>
        </aside>

        <section class="min-w-0">
            @if($viewMode === 'month')
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-bold uppercase text-slate-500">
                        @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                            <div class="py-2">{{ $weekday }}</div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-7">
                        @foreach($days as $day)
                            @php
                                $dayEvents = $eventsByDate->get($day->toDateString(), collect());
                                $inMonth = $day->month === $date->month;
                            @endphp
                            <div class="min-h-16 border-b border-r border-slate-100 p-1 sm:min-h-28 sm:p-1.5 {{ $inMonth ? 'bg-white' : 'bg-slate-50/70' }}">
                                <a href="{{ $calendarUrl(['view' => 'day', 'date' => $day->toDateString()]) }}"
                                   class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $day->isToday() ? 'bg-[#16776e] text-white' : ($inMonth ? 'text-slate-800 hover:bg-slate-100' : 'text-slate-400') }}">{{ $day->day }}</a>

                                @if($dayEvents->isNotEmpty())
                                    <a href="{{ $calendarUrl(['view' => 'day', 'date' => $day->toDateString()]) }}" class="mt-1 block rounded bg-teal-50 px-1 text-center text-xs font-semibold text-teal-800 sm:hidden">{{ $dayEvents->count() }}</a>
                                    <div class="mt-1 hidden space-y-1 sm:block">
                                        @foreach($dayEvents->take(3) as $event)
                                            @include('case-calendar.partials.event', ['event' => $event, 'compact' => true])
                                        @endforeach
                                        @if($dayEvents->count() > 3)
                                            <a href="{{ $calendarUrl(['view' => 'day', 'date' => $day->toDateString()]) }}" class="block px-1 text-xs font-semibold text-[#16776e] hover:underline">+ {{ $dayEvents->count() - 3 }} more</a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif($viewMode === 'week')
                <div class="grid gap-3 md:grid-cols-7 md:gap-0 md:overflow-hidden md:rounded-2xl md:border md:border-slate-200 md:bg-white md:shadow-sm">
                    @foreach($days as $day)
                        @php $dayEvents = $eventsByDate->get($day->toDateString(), collect()); @endphp
                        <div class="rounded-2xl border border-slate-200 bg-white p-3 md:min-h-96 md:rounded-none md:border-0 md:border-r md:border-slate-100 md:p-2 {{ $day->isToday() ? 'md:bg-teal-50/40' : '' }}">
                            <a href="{{ $calendarUrl(['view' => 'day', 'date' => $day->toDateString()]) }}" class="flex items-baseline gap-2 md:flex-col md:items-center md:gap-0">
                                <span class="text-xs font-bold uppercase text-slate-500">{{ $day->format('D') }}</span>
                                <span class="text-lg font-bold {{ $day->isToday() ? 'text-[#16776e]' : 'text-slate-900' }}">{{ $day->format('d') }}</span>
                            </a>
                            <div class="mt-2 space-y-1.5">
                                @forelse($dayEvents as $event)
                                    @include('case-calendar.partials.event', ['event' => $event, 'compact' => true])
                                @empty
                                    <p class="text-xs text-slate-400 md:text-center">—</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($viewMode === 'day')
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    @if($events->isEmpty())
                        <p class="text-sm text-slate-500">Nothing scheduled for this day.</p>
                        <a href="{{ route('hearings.create', ['date' => $date->toDateString()]) }}" class="mt-3 inline-flex text-sm font-semibold text-[#16776e] hover:underline">+ Add a hearing on {{ $date->format('d M') }}</a>
                    @else
                        <div class="space-y-2">
                            @foreach($events as $event)
                                @include('case-calendar.partials.event', ['event' => $event])
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm print:border-0 print:shadow-none">
                    @forelse($eventsByDate as $eventDate => $dayEvents)
                        <div class="border-b border-slate-100 p-4 last:border-0 print:break-inside-avoid print:px-0">
                            <h3 class="text-sm font-bold {{ $eventDate === today()->toDateString() ? 'text-[#16776e]' : 'text-slate-900' }}">
                                {{ \Illuminate\Support\Carbon::parse($eventDate)->format('l, d M Y') }}
                            </h3>
                            <table class="mt-2 w-full text-left text-sm">
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($dayEvents as $event)
                                        <tr class="align-top">
                                            <td class="w-16 py-1.5 pr-2 font-semibold text-slate-600">{{ $event['time'] ?? '—' }}</td>
                                            <td class="w-28 py-1.5 pr-2 text-xs font-bold uppercase text-slate-500">{{ $event['label'] }}</td>
                                            <td class="py-1.5">
                                                <a href="{{ $event['url'] }}" class="font-semibold text-slate-900 hover:text-[#16776e] {{ $event['type'] === 'task' && $event['is_done'] ? 'line-through' : '' }}">{{ $event['title'] }}</a>
                                                @if($event['case_number'] || $event['court'])
                                                    <span class="block text-xs text-slate-500">{{ collect([$event['case_number'], $event['court']])->filter()->implode(' · ') }}</span>
                                                @endif
                                                @if($event['details'])
                                                    <span class="block text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($event['details'], 160) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <p class="p-5 text-sm text-slate-500">Nothing scheduled in {{ $periodLabel }}{{ $hasFilters ? ' for these filters' : '' }}.</p>
                    @endforelse
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
