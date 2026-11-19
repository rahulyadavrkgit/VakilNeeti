@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Case Management Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Your cases, hearing schedule and case-related deadlines.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('cases.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                My Cases
            </a>
            <a href="{{ route('cases.calendar') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Open Calendar
            </a>
        </div>
    </div>

    {{-- Hearing counters --}}
    <section>
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Hearing Schedule</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <a href="{{ route('cases.calendar') }}" class="rounded-xl border border-blue-200 bg-white p-5 hover:shadow-sm">
                <p class="text-sm font-medium text-slate-500">Hearings Today</p>
                <p class="mt-2 text-3xl font-bold text-blue-700">{{ $hearingsToday }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ now()->format('d M Y') }}</p>
            </a>

            <a href="{{ route('cases.calendar') }}" class="rounded-xl border border-amber-200 bg-white p-5 hover:shadow-sm">
                <p class="text-sm font-medium text-slate-500">Hearings Tomorrow</p>
                <p class="mt-2 text-3xl font-bold text-amber-700">{{ $hearingsTomorrow }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ now()->addDay()->format('d M Y') }}</p>
            </a>

            <a href="{{ route('cases.calendar') }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm">
                <p class="text-sm font-medium text-slate-500">Hearings in Next 7 Days</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ $hearingsNext7Days }}</p>
                <p class="mt-2 text-xs text-slate-500">Days 2–7 from today</p>
            </a>

            <a href="{{ route('cases.calendar') }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm">
                <p class="text-sm font-medium text-slate-500">Hearings in Next 30 Days</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ $hearingsNext30Days }}</p>
                <p class="mt-2 text-xs text-slate-500">Days 8–30 from today</p>
            </a>

        </div>
    </section>

    {{-- Case overview --}}
    <section>
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Case Overview</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <a href="{{ route('cases.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm">
                <p class="text-sm text-slate-500">Total Cases</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ $totalCases }}</p>
                <p class="mt-2 text-xs text-blue-600">View all cases</p>
            </a>

            <a href="{{ route('cases.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm">
                <p class="text-sm text-slate-500">Pending Cases</p>
                <p class="mt-2 text-3xl font-bold text-amber-700">{{ $pendingCases }}</p>
                <p class="mt-2 text-xs text-blue-600">View case records</p>
            </a>
        </div>
    </section>

    {{-- Upcoming hearings --}}
    <section class="rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <div>
                <h2 class="font-semibold text-slate-800">Upcoming Hearings</h2>
                <p class="mt-1 text-sm text-slate-500">Tomorrow through the next 30 days.</p>
            </div>
            <a href="{{ route('cases.calendar') }}" class="text-sm font-medium text-blue-600 hover:underline">
                Full Calendar
            </a>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($upcomingHearings as $hearing)
                <div class="flex flex-wrap items-start justify-between gap-3 p-4">
                    <div>
                        <a href="{{ route('cases.show', $hearing->case_id) }}" class="font-medium text-blue-700 hover:underline">
                            {{ $hearing->case_title }}
                        </a>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $hearing->case_number ?: 'Case number unavailable' }}
                            @if($hearing->cnr_number)
                                · CNR: {{ $hearing->cnr_number }}
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $hearing->court_name ?: 'Court not specified' }}
                            @if($hearing->court_room)
                                · Court Room: {{ $hearing->court_room }}
                            @endif
                        </p>
                        @if($hearing->hearing_stage)
                            <p class="mt-1 text-xs text-slate-500">Stage: {{ $hearing->hearing_stage }}</p>
                        @endif
                    </div>

                    <div class="text-right">
                        <p class="font-semibold text-slate-800">
                            {{ \Carbon\Carbon::parse($hearing->next_hearing_date)->format('d M Y') }}
                        </p>
                        @if($hearing->hearing_time)
                            <p class="text-xs text-slate-500">{{ $hearing->hearing_time }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="p-6 text-sm text-slate-500">No upcoming hearings recorded for the next 30 days.</p>
            @endforelse
        </div>
    </section>

    {{-- Case-related tasks --}}
    <section class="rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <div>
                <h2 class="font-semibold text-slate-800">Case-related Tasks &amp; Deadlines</h2>
                <p class="mt-1 text-sm text-slate-500">Pending tasks associated with your cases.</p>
            </div>
            <a href="{{ route('tasks.index') }}" class="text-sm font-medium text-blue-600 hover:underline">All Tasks</a>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($pendingTasks as $task)
                <div class="p-4">
                    <p class="font-medium text-slate-800">{{ $task->title }}</p>
                    <a href="{{ route('cases.show', $task->legal_case_id) }}" class="mt-1 inline-block text-sm text-blue-600 hover:underline">
                        {{ $task->case_title }}
                        @if($task->case_number)
                            ({{ $task->case_number }})
                        @endif
                    </a>
                    <div class="mt-2 flex flex-wrap gap-3 text-xs text-slate-500">
                        <span>Priority: {{ ucfirst($task->priority) }}</span>
                        <span>Status: {{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                        <span>Due: {{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('d M Y, h:i A') : 'Not set' }}</span>
                    </div>
                </div>
            @empty
                <p class="p-6 text-sm text-slate-500">No pending case-related tasks.</p>
            @endforelse
        </div>
    </section>

    {{-- Recently updated cases --}}
    <section class="rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <div>
                <h2 class="font-semibold text-slate-800">Recently Updated Cases</h2>
                <p class="mt-1 text-sm text-slate-500">Latest changes to case records.</p>
            </div>
            <a href="{{ route('cases.index') }}" class="text-sm font-medium text-blue-600 hover:underline">All Cases</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Case</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Case Number</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Court</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Last Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentCases as $case)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <a href="{{ route('cases.show', $case->id) }}" class="font-medium text-blue-700 hover:underline">
                                    {{ $case->case_title }}
                                </a>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $case->case_number ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $case->court_name ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $case->case_status ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ $case->updated_at ? \Carbon\Carbon::parse($case->updated_at)->format('d M Y') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">
                                No case records available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>
@endsection