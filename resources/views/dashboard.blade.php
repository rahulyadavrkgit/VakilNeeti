@extends('layouts.app')

@section('title', 'Case Management Dashboard')
@section('page-heading', 'Case Management Dashboard')

@section('content')

<div class="space-y-7">

    {{-- PAGE INTRODUCTION --}}
    <section class="rounded-2xl bg-slate-900 p-6 text-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <div>
                <h2 class="mt-2 text-2xl font-bold text-white">
                    Case Management Dashboard
                </h2>

                <p class="mt-2 text-sm text-slate-300">
                    Monitor your cases, court hearings, deadlines and pending work.
                </p>

                <p class="mt-3 text-sm font-medium text-slate-200">
                    {{ $today->format('l, d F Y') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('court-integrations.ecourts.search') }}"
                   class="rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                    Search Official Court Records
                </a>

                <a href="{{ route('cases.calendar') }}"
                   class="rounded-lg border border-slate-500 bg-slate-800 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-700">
                    Calendar &amp; Hearings
                </a>
            </div>
        </div>
    </section>

    {{-- STATISTICS --}}
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900">Case Overview</h3>
            <span class="text-xs font-medium text-slate-500">Current case diary</span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

            <div class="rounded-xl border border-blue-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-600">Total Cases</p>
                <p class="mt-3 text-3xl font-bold text-blue-800 tabular-nums">
                    {{ $totalCases }}
                </p>
                <a href="{{ route('cases.index') }}"
                   class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">
                    View My Cases &rarr;
                </a>
            </div>

            <div class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-600">Pending Cases</p>
                <p class="mt-3 text-3xl font-bold text-amber-700 tabular-nums">
                    {{ $pendingCases }}
                </p>
                <a href="{{ route('cases.index', ['case_status' => 'pending']) }}"
                   class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">
                    View Pending Cases &rarr;
                </a>
            </div>

            <div class="rounded-xl border border-red-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-600">Pending Tasks</p>
                <p class="mt-3 text-3xl font-bold text-red-700 tabular-nums">
                    {{ $pendingTasksCount }}
                </p>
                <a href="{{ route('tasks.index') }}"
                   class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">
                    View Tasks &rarr;
                </a>
            </div>

        </div>
    </section>

    {{-- TODAY'S HEARINGS --}}
    <section class="rounded-xl border border-blue-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-blue-50 px-5 py-4">
            <div>
                <h3 class="font-bold text-slate-900">Today's Hearings</h3>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $today->format('d M Y') }}
                </p>
            </div>

            <a href="{{ route('cases.calendar') }}"
               class="text-sm font-semibold text-blue-700 hover:underline">
                Open Calendar &rarr;
            </a>
        </div>

        <div class="px-5">
            @forelse($todayHearings as $hearing)
                <div class="border-b border-slate-200 py-4 last:border-0">
                    <a href="{{ route('cases.show', $hearing->legalCase) }}"
                       class="font-semibold text-blue-800 hover:underline">
                        {{ $hearing->legalCase->case_title }}
                    </a>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $hearing->legalCase->case_number ?: 'Case number unavailable' }}
                        @if($hearing->legalCase->cnr_number)
                            &middot; CNR: {{ $hearing->legalCase->cnr_number }}
                        @endif
                    </p>

                    <p class="mt-1 text-sm text-slate-600">
                        {{ $hearing->legalCase->court_name ?: 'Court not specified' }}
                        &middot;
                        {{ $hearing->hearing_time
                            ? \Carbon\Carbon::parse($hearing->hearing_time)->format('h:i A')
                            : 'Time not specified' }}
                    </p>
                </div>
            @empty
                <p class="py-6 text-sm text-slate-600">
                    No hearings are scheduled for today.
                </p>
            @endforelse
        </div>
    </section>

    {{-- UPCOMING HEARINGS --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h3 class="font-bold text-slate-900">Upcoming Hearings</h3>
                <p class="mt-1 text-sm text-slate-600">Next seven days</p>
            </div>

            <a href="{{ route('cases.calendar') }}"
               class="text-sm font-semibold text-blue-700 hover:underline">
                View Calendar &rarr;
            </a>
        </div>

        <div class="px-5">
            @forelse($upcomingHearings as $hearing)
                <div class="flex flex-wrap justify-between gap-3 border-b border-slate-200 py-4 last:border-0">
                    <div>
                        <a href="{{ route('cases.show', $hearing->legalCase) }}"
                           class="font-semibold text-blue-800 hover:underline">
                            {{ $hearing->legalCase->case_title }}
                        </a>

                        <p class="mt-1 text-sm text-slate-600">
                            {{ $hearing->legalCase->case_number ?: 'Case number unavailable' }}
                            &middot;
                            {{ $hearing->legalCase->court_name ?: 'Court not specified' }}
                        </p>
                    </div>

                    <div class="text-sm font-semibold text-slate-800">
                        {{ \Carbon\Carbon::parse($hearing->next_hearing_date)->format('d M Y') }}

                        @if($hearing->hearing_time)
                            &middot; {{ \Carbon\Carbon::parse($hearing->hearing_time)->format('h:i A') }}
                        @endif
                    </div>
                </div>
            @empty
                <p class="py-6 text-sm text-slate-600">
                    No upcoming hearings recorded for the next seven days.
                </p>
            @endforelse
        </div>
    </section>

    {{-- PAST HEARING DATES --}}
    <section class="rounded-xl border border-amber-300 bg-white shadow-sm">
        <div class="border-b border-amber-200 bg-amber-50 px-5 py-4">
            <h3 class="font-bold text-amber-900">
                Past Hearing Dates — Review Required
            </h3>
            <p class="mt-1 text-sm text-amber-900">
                Review the official case status before updating these dates.
            </p>
        </div>

        <div class="px-5">
            @forelse($pastDueHearings as $hearing)
                <div class="flex flex-wrap justify-between gap-3 border-b border-slate-200 py-4 last:border-0">
                    <div>
                        <a href="{{ route('cases.show', $hearing->legalCase) }}"
                           class="font-semibold text-blue-800 hover:underline">
                            {{ $hearing->legalCase->case_title }}
                        </a>

                        <p class="mt-1 text-sm text-slate-600">
                            {{ $hearing->legalCase->case_number ?: 'Case number unavailable' }}
                        </p>
                    </div>

                    <p class="text-sm font-bold text-amber-800">
                        {{ \Carbon\Carbon::parse($hearing->next_hearing_date)->format('d M Y') }}
                    </p>
                </div>
            @empty
                <p class="py-5 text-sm text-slate-600">
                    No past hearing dates require review.
                </p>
            @endforelse
        </div>
    </section>

    {{-- TASK MANAGEMENT --}}
    <section>
        <h3 class="mb-3 text-lg font-bold text-slate-900">Tasks &amp; Deadlines</h3>

        <div class="grid gap-5 lg:grid-cols-2">

            <div class="rounded-xl border border-red-200 bg-white shadow-sm">
                <div class="border-b border-red-200 bg-red-50 px-5 py-4">
                    <h3 class="font-bold text-red-900">Overdue Tasks</h3>
                    <p class="mt-1 text-sm text-red-800">
                        Tasks requiring immediate attention
                    </p>
                </div>

                <div class="px-5">
                    @forelse($overdueTasks as $task)
                        <div class="border-b border-slate-200 py-4 last:border-0">
                            <a href="{{ route('tasks.edit', $task) }}"
                               class="font-semibold text-blue-800 hover:underline">
                                {{ $task->title }}
                            </a>

                            <p class="mt-1 text-sm text-slate-600">
                                {{ $task->client?->display_name ?: 'No client linked' }}
                            </p>

                            <p class="mt-1 text-sm font-semibold text-red-700">
                                Due: {{ $task->due_at?->format('d M Y, h:i A') }}
                            </p>
                        </div>
                    @empty
                        <p class="py-5 text-sm text-slate-600">No overdue tasks.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-blue-200 bg-white shadow-sm">
                <div class="border-b border-blue-200 bg-blue-50 px-5 py-4">
                    <h3 class="font-bold text-slate-900">Today's Tasks</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Work scheduled for today
                    </p>
                </div>

                <div class="px-5">
                    @forelse($todayTasks as $task)
                        <div class="border-b border-slate-200 py-4 last:border-0">
                            <a href="{{ route('tasks.edit', $task) }}"
                               class="font-semibold text-blue-800 hover:underline">
                                {{ $task->title }}
                            </a>

                            <p class="mt-1 text-sm text-slate-600">
                                {{ $task->client?->display_name ?: 'No client linked' }}
                            </p>

                            <p class="mt-1 text-sm text-slate-700">
                                {{ $task->due_at?->format('h:i A') }}
                            </p>
                        </div>
                    @empty
                        <p class="py-5 text-sm text-slate-600">
                            No tasks scheduled for today.
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-900">Upcoming Tasks</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Future deadlines and scheduled work
                    </p>
                </div>

                <div class="px-5">
                    @forelse($upcomingTasks as $task)
                        <div class="flex flex-wrap justify-between gap-3 border-b border-slate-200 py-4 last:border-0">
                            <div>
                                <a href="{{ route('tasks.edit', $task) }}"
                                   class="font-semibold text-blue-800 hover:underline">
                                    {{ $task->title }}
                                </a>

                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $task->client?->display_name ?: 'No client linked' }}
                                </p>
                            </div>

                            <p class="text-sm font-semibold text-slate-700">
                                {{ $task->due_at?->format('d M Y, h:i A') }}
                            </p>
                        </div>
                    @empty
                        <p class="py-5 text-sm text-slate-600">
                            No upcoming tasks.
                        </p>
                    @endforelse
                </div>
            </div>

        </div>
    </section>

    {{-- RECENT CASES --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <h3 class="font-bold text-slate-900">Recently Updated Cases</h3>

            <a href="{{ route('cases.index') }}"
               class="text-sm font-semibold text-blue-700 hover:underline">
                View All Cases &rarr;
            </a>
        </div>

        <div class="px-5">
            @forelse($recentCases as $case)
                <div class="flex flex-wrap justify-between gap-3 border-b border-slate-200 py-4 last:border-0">
                    <div>
                        <a href="{{ route('cases.show', $case) }}"
                           class="font-semibold text-blue-800 hover:underline">
                            {{ $case->case_title }}
                        </a>

                        <p class="mt-1 text-sm text-slate-600">
                            {{ $case->case_number ?: 'Case number unavailable' }}

                            @if($case->cnr_number)
                                &middot; CNR: {{ $case->cnr_number }}
                            @endif
                        </p>

                        <p class="mt-1 text-sm text-slate-600">
                            {{ $case->court_name ?: 'Court not specified' }}
                        </p>
                    </div>

                    <div class="text-right">
                        <span class="inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ ucfirst(str_replace('_', ' ', $case->case_status ?? 'Not specified')) }}
                        </span>

                        <p class="mt-2 text-xs text-slate-600">
                            Updated {{ $case->updated_at?->format('d M Y') }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="py-5 text-sm text-slate-600">
                    No cases are currently available in your case diary.
                </p>
            @endforelse
        </div>
    </section>

    {{-- HEARING HISTORY --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <h3 class="font-bold text-slate-900">Recent Hearing History</h3>

            <a href="{{ route('hearings.history') }}"
               class="text-sm font-semibold text-blue-700 hover:underline">
                View Hearing History &rarr;
            </a>
        </div>

        <div class="px-5">
            @forelse($recentHearings as $hearing)
                <div class="border-b border-slate-200 py-4 last:border-0">
                    <a href="{{ route('cases.show', $hearing->legalCase) }}"
                       class="font-semibold text-blue-800 hover:underline">
                        {{ $hearing->legalCase->case_title }}
                    </a>

                    <p class="mt-1 text-sm text-slate-600">
                        {{ $hearing->legalCase->case_number ?: 'Case number unavailable' }}
                        &middot;
                        {{ $hearing->hearing_date?->format('d M Y') }}
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $hearing->hearing_stage ?: 'Stage not specified' }}
                    </p>
                </div>
            @empty
                <p class="py-5 text-sm text-slate-600">
                    No hearing history is available.
                </p>
            @endforelse
        </div>
    </section>

</div>

@endsection
