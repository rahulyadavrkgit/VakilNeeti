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
            <h2 class="text-lg font-semibold text-slate-900">{{ $periodLabel }}</h2>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <nav class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white" aria-label="Calendar view">
                @foreach(\App\Http\Controllers\CaseCalendarController::VIEWS as $key => $label)
                    <a href="{{ $calendarUrl(['view' => $key]) }}"
                       @if($viewMode === $key) aria-current="page" @endif
                       class="inline-flex min-h-10 items-center px-3 text-sm font-semibold {{ $loop->first ? '' : 'border-l border-slate-200' }} {{ $viewMode === $key ? 'bg-[#16776e] text-white' : 'text-slate-700 hover:bg-slate-50' }}">{{ $label }}</a>
                @endforeach
            </nav>

            {{-- Add: a hearing or a task. --}}
            <details class="vn-profile-menu relative">
                <summary class="inline-flex min-h-10 cursor-pointer list-none items-center gap-1.5 rounded-lg bg-[#16776e] px-3.5 text-sm font-semibold text-white hover:bg-[#125f58]">
                    <x-icon name="plus" :size="16" /> Add
                </summary>
                <div class="absolute right-0 z-40 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-xl">
                    <a href="{{ route('hearings.create') }}" data-sheet="{{ route('sheets.next-date') }}" class="flex min-h-11 items-center gap-2.5 px-4 text-slate-700 hover:bg-slate-50"><x-icon name="calendar" :size="16" class="text-[#16776e]" /> Hearing</a>
                    <a href="{{ route('tasks.create') }}" data-sheet="{{ route('sheets.task') }}" class="flex min-h-11 items-center gap-2.5 px-4 text-slate-700 hover:bg-slate-50"><x-icon name="check" :size="16" class="text-[#16776e]" /> Task</a>
                </div>
            </details>

            {{-- Share a day's (or the shown period's) hearings with their details, or print. --}}
            <details class="vn-profile-menu relative">
                <summary aria-label="Share or print" title="Share or print" class="inline-flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50">
                    <x-icon name="share" :size="17" />
                </summary>
                <div class="z-40 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white text-sm shadow-xl max-md:fixed max-md:inset-x-3 max-md:bottom-24 md:absolute md:right-0 md:w-80">
                    <p class="px-4 pb-1 pt-3 text-xs font-bold uppercase tracking-wider text-slate-400">Share hearings</p>
                    @foreach($shareLists as $shareKey => $shareList)
                        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-slate-800">{{ $shareList['label'] }}</span>
                                <span class="text-xs text-slate-500">{{ $shareList['count'] }} {{ \Illuminate\Support\Str::plural('hearing', $shareList['count']) }}</span>
                            </span>
                            @if($shareList['count'] > 0)
                                <button type="button" data-copy="{{ $shareList['text'] }}" title="Copy the list" aria-label="Copy the {{ strtolower($shareList['label']) }} list"
                                        class="inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 px-2.5 text-xs font-semibold text-slate-700 hover:border-[#16776e] hover:text-[#16776e]">
                                    <x-icon name="copy" :size="14" /><span data-copy-label>Copy</span>
                                </button>
                                <a href="https://wa.me/?text={{ rawurlencode($shareList['text']) }}" target="_blank" rel="noopener noreferrer" data-direct-share
                                   data-share-text="{{ $shareList['text'] }}" data-share-title="{{ $shareList['label'] }} hearings"
                                   class="inline-flex h-9 items-center gap-1 rounded-lg bg-[#16776e] px-2.5 text-xs font-semibold text-white hover:bg-[#125f58]">
                                    <x-icon name="share" :size="14" /> Share
                                </a>
                            @else
                                <span class="text-xs text-slate-400">Nothing to send</span>
                            @endif
                        </div>
                    @endforeach
                    <p class="px-4 pb-1 pt-3 text-xs font-bold uppercase tracking-wider text-slate-400">Print</p>
                    <button type="button" data-print class="block min-h-10 w-full px-4 text-left text-slate-700 hover:bg-slate-50">This view</button>
                    <a href="{{ $calendarUrl(['view' => 'list', 'print' => 1]) }}" class="flex min-h-10 items-center px-4 text-slate-700 hover:bg-slate-50">The month as a list</a>
                    <a href="{{ $calendarUrl(['view' => 'list', 'print' => 1, 'types' => ['hearing']]) }}" class="flex min-h-10 items-center px-4 pb-1 text-slate-700 hover:bg-slate-50">Hearings only</a>
                </div>
            </details>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="space-y-4 print:hidden">
            {{-- Small month picker --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between">
                    <a href="{{ route('cases.calendar', array_merge($query, ['view' => $viewMode, 'date' => $date->copy()->subMonthNoOverflow()->toDateString()])) }}" class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100" aria-label="Previous month">&lsaquo;</a>
                    <p class="text-sm font-bold text-slate-900">{{ $date->format('F Y') }}</p>
                    <a href="{{ route('cases.calendar', array_merge($query, ['view' => $viewMode, 'date' => $date->copy()->addMonthNoOverflow()->toDateString()])) }}" class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100" aria-label="Next month">&rsaquo;</a>
                </div>
                <div class="mt-3 grid grid-cols-7 text-center text-xs font-semibold uppercase text-slate-400">
                    @foreach(array_map(fn ($label) => $label[0], $weekdayLabels) as $weekday)
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
                        @foreach($weekdayLabels as $weekday)
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
                {{-- Computers: seven columns; each hearing as a small card (time, title, side, room); point at one for all details. --}}
                <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:grid md:grid-cols-7">
                    @foreach($days as $day)
                        @php
                            $dayEvents = $eventsByDate->get($day->toDateString(), collect());
                            $dayHearings = $dayEvents->where('group', 'hearing');
                        @endphp
                        <div class="flex min-h-[28rem] min-w-0 flex-col border-r border-slate-100 last:border-r-0 {{ $day->isToday() ? 'bg-teal-50/50' : '' }}">
                            <div class="flex items-center justify-between gap-1 border-b border-slate-100 px-2 py-2">
                                <a href="{{ $calendarUrl(['view' => 'day', 'date' => $day->toDateString()]) }}" class="flex items-baseline gap-1.5 rounded-md px-1 hover:bg-slate-100">
                                    <span class="text-xs font-bold uppercase text-slate-500">{{ $day->format('D') }}</span>
                                    <span class="flex h-7 min-w-7 items-center justify-center rounded-full px-1 text-base font-bold {{ $day->isToday() ? 'bg-[#16776e] text-white' : 'text-slate-900' }}">{{ $day->format('d') }}</span>
                                </a>
                                @if($dayHearings->isNotEmpty())
                                    @php $dayText = \App\Services\HearingShareText::forEvents('Hearings · '.$day->format('D d M Y'), $dayHearings); @endphp
                                    <a href="https://wa.me/?text={{ rawurlencode($dayText) }}" target="_blank" rel="noopener noreferrer" data-direct-share data-share-text="{{ $dayText }}"
                                       title="Share this day's {{ $dayHearings->count() }} {{ \Illuminate\Support\Str::plural('hearing', $dayHearings->count()) }}" aria-label="Share {{ $day->format('D d M') }} hearings"
                                       class="inline-flex h-7 items-center gap-1 rounded-full bg-white px-2 text-xs font-semibold text-[#16776e] ring-1 ring-inset ring-teal-200 hover:bg-teal-50">
                                        <x-icon name="share" :size="12" /> {{ $dayHearings->count() }}
                                    </a>
                                @endif
                            </div>
                            <div class="flex-1 space-y-1.5 p-1.5">
                                @forelse($dayEvents as $event)
                                    @include('case-calendar.partials.event', ['event' => $event, 'variant' => 'week'])
                                @empty
                                    <a href="{{ route('hearings.create', ['date' => $day->toDateString()]) }}" class="block rounded-lg px-2 py-3 text-center text-xs text-slate-300 hover:bg-slate-50 hover:text-[#16776e]">+ Add</a>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Phones: the days as a list; busy days show each hearing in full, quiet days take one line. --}}
                <div class="space-y-2.5 md:hidden">
                    @foreach($days as $day)
                        @php
                            $dayEvents = $eventsByDate->get($day->toDateString(), collect());
                            $dayHearings = $dayEvents->where('group', 'hearing');
                            $dayName = $day->isToday() ? 'Today' : ($day->isTomorrow() ? 'Tomorrow' : $day->format('l'));
                        @endphp
                        @if($dayEvents->isEmpty())
                            <div class="flex items-center justify-between rounded-xl border border-dashed border-slate-200 bg-white/60 px-3 py-2 text-sm">
                                <span class="text-slate-500"><span class="font-semibold {{ $day->isToday() ? 'text-[#16776e]' : 'text-slate-700' }}">{{ $dayName }}</span> · {{ $day->format('d M') }} · nothing scheduled</span>
                                <a href="{{ route('hearings.create', ['date' => $day->toDateString()]) }}" class="inline-flex min-h-9 items-center px-2 text-xs font-semibold text-[#16776e]">+ Add</a>
                            </div>
                        @else
                            <section class="rounded-2xl border bg-white p-3 shadow-sm {{ $day->isToday() ? 'border-[#16776e] ring-1 ring-[#16776e]/30' : 'border-slate-200' }}">
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <a href="{{ $calendarUrl(['view' => 'day', 'date' => $day->toDateString()]) }}" class="min-w-0">
                                        <span class="font-bold {{ $day->isToday() ? 'text-[#16776e]' : 'text-slate-900' }}">{{ $dayName }}</span>
                                        <span class="text-sm text-slate-500">· {{ $day->format('d M') }} · {{ $dayEvents->count() }} {{ \Illuminate\Support\Str::plural('item', $dayEvents->count()) }}</span>
                                    </a>
                                    @if($dayHearings->isNotEmpty())
                                        @php $dayText = \App\Services\HearingShareText::forEvents('Hearings · '.$day->format('D d M Y'), $dayHearings); @endphp
                                        <a href="https://wa.me/?text={{ rawurlencode($dayText) }}" target="_blank" rel="noopener noreferrer" data-direct-share data-share-text="{{ $dayText }}"
                                           class="inline-flex min-h-9 shrink-0 items-center gap-1 rounded-lg border border-[#16776e] px-2.5 text-xs font-semibold text-[#16776e]">
                                            <x-icon name="share" :size="14" /> Share day
                                        </a>
                                    @endif
                                </div>
                                <div class="space-y-2">
                                    @foreach($dayEvents as $event)
                                        @include('case-calendar.partials.event', ['event' => $event])
                                    @endforeach
                                </div>
                            </section>
                        @endif
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
                                                <span data-event-peek class="inline-flex flex-wrap items-center gap-1.5">
                                                    <a href="{{ $event['url'] }}" class="font-semibold text-slate-900 hover:text-[#16776e] {{ $event['type'] === 'task' && $event['is_done'] ? 'line-through' : '' }}">{{ $event['title'] }}</a>
                                                    <x-case-side :side="$event['appearing_for'] ?? null" :parties="$event['appearing_for_parties'] ?? null" />
                                                    <template data-event-card>@include('case-calendar.partials.event-card', ['event' => $event])</template>
                                                </span>
                                                @if($event['case_number'])
                                                    <span class="block text-xs text-slate-500">{{ $event['case_number'] }}</span>
                                                @endif
                                                <x-court-info class="mt-1" :live="false" :court="$event['court']" :complex="$event['court_complex'] ?? null" :room="$event['cause_list']['court'] ?? $event['court_room'] ?? null" :judge="$event['cause_list']['judges'] ?? $event['judge_name'] ?? null" :vc-link="$event['cause_list']['vc_link'] ?? $event['vc_link'] ?? null" :share="['title' => $event['title'], 'case_number' => $event['case_number'], 'side' => $event['appearing_for'] ?? null, 'parties' => $event['appearing_for_parties'] ?? null, 'date' => $event['date'], 'time' => $event['time'], 'details' => $event['details']]" />
                                                @if(($event['group'] ?? null) === 'hearing')
                                                    <x-hearing-strip :show-room="false" :date="$event['date']" :court="$event['court']" :room="$event['court_room'] ?? null" :item="$event['item_number'] ?? null" :list-total="$event['list_total'] ?? null" :cause-list="$event['cause_list'] ?? null" :case-number="$event['case_number']" :district="($event['court_level'] ?? null) === 'district'" :order-url="!empty($event['order']) ? route('cases.documents.download', [$event['case_id'], $event['order'], 'preview' => 1]) : null" :order-date="($event['order'] ?? null)?->created_at?->format('d M Y')" />
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
