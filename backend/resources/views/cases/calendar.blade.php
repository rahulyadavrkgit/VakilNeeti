@extends('layouts.app')

@section('title', 'Case Calendar')
@section('page-heading', 'Case Calendar')

@section('content')

@include('cases.partials.management-nav')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Case Calendar</h1>
            <p class="mt-1 text-sm text-slate-500">
                Monthly hearing calendar and upcoming court matters.
            </p>
        </div>
        <a href="{{ route('cases.index') }}"
           class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">
            My Case Diary
        </a>
    </div>

    {{-- Hearing summary --}}
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-5">
        @foreach([
            ['label' => "Today's Hearings", 'count' => $todayCount],
            ['label' => "Tomorrow's Hearings", 'count' => $tomorrowCount],
            ['label' => 'Next 7 Days', 'count' => $nextSevenCount],
            ['label' => 'Days 8–30', 'count' => $laterCount],
            ['label' => 'Past Dates to Review', 'count' => $reviewCount],
        ] as $stat)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stat['count'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Distinct cases</p>
            </div>
        @endforeach
    </div>

    {{-- Monthly calendar --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
            <a href="{{ route('cases.calendar', ['month' => $previousMonth->format('Y-m')]) }}"
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">
                ← Previous
            </a>

            <div class="text-center">
                <h2 class="text-lg font-bold text-slate-900">
                    {{ $month->format('F Y') }}
                </h2>
                <a href="{{ route('cases.calendar', ['month' => now()->format('Y-m'), 'selected_date' => now()->format('Y-m-d')]) }}"
                   class="text-xs text-blue-700 hover:underline">
                    Go to today
                </a>
            </div>

            <a href="{{ route('cases.calendar', ['month' => $nextMonth->format('Y-m')]) }}"
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">
                Next →
            </a>
        </div>

        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                <div class="p-2 text-center text-xs font-semibold text-slate-600">
                    {{ $weekday }}
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-7">
            @foreach($calendarDays as $day)
                @php
                    $dateKey = $day->format('Y-m-d');
                    $dayHearings = $calendarHearingsByDate->get($dateKey, collect());
                    $isToday = $day->isSameDay($today);
                    $isSelected = $day->isSameDay($selectedDate);
                    $inMonth = $day->format('Y-m') === $month->format('Y-m');
                @endphp

                <a href="{{ route('cases.calendar', [
                    'month' => $month->format('Y-m'),
                    'selected_date' => $dateKey
                ]) }}"
                   class="min-h-20 border-b border-r border-slate-100 p-1.5 sm:min-h-28 sm:p-2
                   {{ !$inMonth ? 'bg-slate-50 text-slate-400' : 'bg-white' }}
                   {{ $isSelected ? 'ring-2 ring-inset ring-blue-600' : '' }}
                   hover:bg-blue-50">

                    <div class="flex items-center justify-between">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold
                            {{ $isToday ? 'bg-blue-700 text-white' : '' }}">
                            {{ $day->day }}
                        </span>

                        @if($dayHearings->count())
                            <span class="rounded-full bg-blue-100 px-1.5 py-0.5 text-xs font-semibold text-blue-800">
                                {{ $dayHearings->count() }}
                            </span>
                        @endif
                    </div>

                    @foreach($dayHearings->take(2) as $calendarHearing)
                        <div class="mt-1 truncate rounded bg-blue-50 px-1 py-1 text-xs text-blue-800">
                            {{ $calendarHearing->case_number ?: $calendarHearing->case_title }}
                        </div>
                    @endforeach

                    @if($dayHearings->count() > 2)
                        <p class="mt-1 text-xs text-slate-500">
                            +{{ $dayHearings->count() - 2 }} more
                        </p>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="border-t border-slate-200 p-3 text-xs text-slate-500">
            Select any date to see its hearing records below. Numbers indicate hearing records on that date.
        </div>
    </section>

    {{-- Selected date --}}
    <section class="overflow-hidden rounded-xl border border-blue-200 bg-white shadow-sm">
        <div class="border-b border-blue-100 bg-blue-50 p-4">
            <h2 class="font-semibold text-slate-900">
                Hearings on {{ $selectedDate->format('l, d F Y') }}
            </h2>
            <p class="mt-1 text-xs text-slate-600">
                {{ $selectedHearings->count() }} hearing records
            </p>
        </div>

        @forelse($selectedHearings as $hearing)
            @include('cases.partials.calendar-hearing', ['hearing' => $hearing])
        @empty
            <div class="p-5 text-sm text-slate-500">
                No hearing records are currently recorded for this date.
            </div>
        @endforelse
    </section>

    {{-- Tomorrow --}}
    <section class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
        <div class="border-b border-amber-100 bg-amber-50 p-4">
            <h2 class="font-semibold text-slate-900">
                Tomorrow's Hearings — {{ $tomorrow->format('d F Y') }}
            </h2>
            <p class="mt-1 text-xs text-slate-600">{{ $tomorrowCount }} distinct cases</p>
        </div>

        @forelse($tomorrowHearings as $hearing)
            @include('cases.partials.calendar-hearing', ['hearing' => $hearing])
        @empty
            <div class="p-5 text-sm text-slate-500">
                No hearings are currently recorded for tomorrow.
            </div>
        @endforelse
    </section>

    {{-- Today --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-4">
            <h2 class="font-semibold text-slate-900">Today's Hearings</h2>
            <p class="mt-1 text-xs text-slate-500">{{ $todayCount }} distinct cases</p>
        </div>

        @forelse($todayHearings as $hearing)
            @include('cases.partials.calendar-hearing', ['hearing' => $hearing])
        @empty
            <div class="p-5 text-sm text-slate-500">No hearings are currently recorded for today.</div>
        @endforelse
    </section>

    {{-- Next seven days --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-4">
            <h2 class="font-semibold text-slate-900">Upcoming — Next 7 Days</h2>
        </div>

        @forelse($nextSevenHearings as $hearing)
            @include('cases.partials.calendar-hearing', ['hearing' => $hearing])
        @empty
            <div class="p-5 text-sm text-slate-500">No upcoming hearing records in this period.</div>
        @endforelse
    </section>

    {{-- Days 8 to 30 --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-4">
            <h2 class="font-semibold text-slate-900">Upcoming — Days 8 to 30</h2>
        </div>

        @forelse($laterHearings as $hearing)
            @include('cases.partials.calendar-hearing', ['hearing' => $hearing])
        @empty
            <div class="p-5 text-sm text-slate-500">No hearing records in this period.</div>
        @endforelse
    </section>

    {{-- Past dates --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-4">
            <h2 class="font-semibold text-slate-900">Past Dates Requiring Review</h2>
            <p class="mt-1 text-xs text-slate-500">
                These are past next-hearing dates still present in the diary. Check the latest court order or cause list.
            </p>
        </div>

        @forelse($pastDueHearings as $hearing)
            @include('cases.partials.calendar-hearing', ['hearing' => $hearing])
        @empty
            <div class="p-5 text-sm text-slate-500">No past hearing dates requiring review.</div>
        @endforelse
    </section>

    <p class="text-xs text-slate-500">
        Calendar entries are based on next hearing dates recorded in VakilNeeti.
        They are not live court cause-list updates. Verify hearing dates against the relevant official court record.
    </p>

</div>
@endsection