@extends('layouts.app')

@section('title', 'My Case & Hearing Dashboard')
@section('page-heading', 'My Case & Hearing Dashboard')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">My Case &amp; Hearing Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Your private cases and hearings.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard-studio.index') }}" class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-medium text-[#16776e] hover:bg-teal-100">
                India Law Data
            </a>
            <a href="{{ route('cases.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                My Cases
            </a>
            <a href="{{ route('cases.calendar', ['view' => 'week']) }}" class="rounded-lg bg-[#16776e] px-4 py-2 text-sm font-medium text-white hover:bg-[#125f58]">
                Open Calendar
            </a>
            <a href="{{ route('tasks.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Tasks
            </a>
        </div>
    </div>

    {{-- Hearing counters --}}
    <section>
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Hearing Schedule</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <a href="{{ route('cases.calendar') }}" class="rounded-xl border border-teal-200 bg-white p-5 hover:shadow-sm">
                <p class="text-sm font-medium text-slate-500">Hearings Today</p>
                <p class="mt-2 text-3xl font-bold text-[#16776e]">{{ $hearingsToday }}</p>
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
                <p class="mt-2 text-xs text-[#16776e]">View all cases</p>
            </a>

            <a href="{{ route('cases.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm">
                <p class="text-sm text-slate-500">Pending Cases</p>
                <p class="mt-2 text-3xl font-bold text-amber-700">{{ $pendingCases }}</p>
                <p class="mt-2 text-xs text-[#16776e]">View case records</p>
            </a>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-teal-200 bg-white">
            <div class="border-b border-teal-100 bg-teal-50 p-5">
                <h2 class="font-semibold text-slate-800">Today's Hearings</h2>
                <p class="mt-1 text-sm text-slate-500">{{ now()->format('d M Y') }}</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($todayHearings as $hearing)
                    <div class="p-4">
                        <a href="{{ route('cases.show', $hearing->case_id) }}" class="font-medium text-[#16776e] hover:underline">
                            {{ $hearing->case_title }}
                        </a>
<x-case-side :side="$hearing->appearing_for ?? null" :parties="$hearing->appearing_for_parties ?? null" class="ml-1 align-middle" />
<x-court-info class="mt-1.5" :court="$hearing->court_name ?? null" :room="($hearing->court_room ?? null) ?: ($hearing->case_court_room ?? null)" :judge="($hearing->judge_name ?? null) ?: ($hearing->case_judge_name ?? null)" :vc-link="($hearing->hearing_vc_link ?? null) ?: ($hearing->case_vc_link ?? null)" />
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $hearing->case_number ?: 'Case number unavailable' }}
                        </p>
                        @if($hearing->hearing_time || $hearing->hearing_stage)
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $hearing->hearing_time ?: '' }}
                                @if($hearing->hearing_time && $hearing->hearing_stage) · @endif
                                {{ $hearing->hearing_stage ?: '' }}
                            </p>
                        @endif
                    </div>
                @empty
                    <p class="p-5 text-sm text-slate-500">No hearings scheduled for today.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-white">
            <div class="border-b border-amber-100 bg-amber-50 p-5">
                <h2 class="font-semibold text-amber-900">Past Hearing Dates — Review Required</h2>
                <p class="mt-1 text-sm text-amber-800">Confirm the official case status before updating a date.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($pastDueHearings as $hearing)
                    <div class="flex flex-wrap justify-between gap-3 p-4">
                        <div>
                            <a href="{{ route('cases.show', $hearing->case_id) }}" class="font-medium text-[#16776e] hover:underline">
                                {{ $hearing->case_title }}
                            </a>
<x-case-side :side="$hearing->appearing_for ?? null" :parties="$hearing->appearing_for_parties ?? null" class="ml-1 align-middle" />
<x-court-info class="mt-1.5" :court="$hearing->court_name ?? null" :room="($hearing->court_room ?? null) ?: ($hearing->case_court_room ?? null)" :judge="($hearing->judge_name ?? null) ?: ($hearing->case_judge_name ?? null)" :vc-link="($hearing->hearing_vc_link ?? null) ?: ($hearing->case_vc_link ?? null)" />
                            <p class="mt-1 text-sm text-slate-600">{{ $hearing->case_number ?: 'Case number unavailable' }}</p>
                        </div>
                        <p class="text-sm font-semibold text-amber-800">
                            {{ \Carbon\Carbon::parse($hearing->next_hearing_date)->format('d M Y') }}
                        </p>
                    </div>
                @empty
                    <p class="p-5 text-sm text-slate-500">No past hearing dates require review.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Upcoming hearings --}}
    <section class="rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <div>
                <h2 class="font-semibold text-slate-800">Upcoming Hearings</h2>
                <p class="mt-1 text-sm text-slate-500">Tomorrow through the next 30 days.</p>
            </div>
            <a href="{{ route('cases.calendar') }}" class="text-sm font-medium text-[#16776e] hover:underline">
                Full Calendar
            </a>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($upcomingHearings as $hearing)
                <div class="flex flex-wrap items-start justify-between gap-3 p-4">
                    <div>
                        <a href="{{ route('cases.show', $hearing->case_id) }}" class="font-medium text-[#16776e] hover:underline">
                            {{ $hearing->case_title }}
                        </a>
<x-case-side :side="$hearing->appearing_for ?? null" :parties="$hearing->appearing_for_parties ?? null" class="ml-1 align-middle" />
<x-court-info class="mt-1.5" :court="$hearing->court_name ?? null" :room="($hearing->court_room ?? null) ?: ($hearing->case_court_room ?? null)" :judge="($hearing->judge_name ?? null) ?: ($hearing->case_judge_name ?? null)" :vc-link="($hearing->hearing_vc_link ?? null) ?: ($hearing->case_vc_link ?? null)" />
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $hearing->case_number ?: 'Case number unavailable' }}
                            @if($hearing->cnr_number)
                                · CNR: {{ $hearing->cnr_number }}
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

    {{-- Recently updated cases --}}
    <section class="rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <div>
                <h2 class="font-semibold text-slate-800">Recently Updated Cases</h2>
                <p class="mt-1 text-sm text-slate-500">Latest changes to case records.</p>
            </div>
            <a href="{{ route('cases.index') }}" class="text-sm font-medium text-[#16776e] hover:underline">All Cases</a>
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
                                <a href="{{ route('cases.show', $case->id) }}" class="font-medium text-[#16776e] hover:underline">
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

    <section class="rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <div>
                <h2 class="font-semibold text-slate-800">Recent Hearing History</h2>
                <p class="mt-1 text-sm text-slate-500">Recorded hearings in your case diary.</p>
            </div>
            <a href="{{ route('hearings.history') }}" class="text-sm font-medium text-[#16776e] hover:underline">Full Hearing History</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recentHearings as $hearing)
                <div class="p-4">
                    <a href="{{ route('cases.show', $hearing->case_id) }}" class="font-medium text-[#16776e] hover:underline">
                        {{ $hearing->case_title }}
                    </a>
<x-case-side :side="$hearing->appearing_for ?? null" :parties="$hearing->appearing_for_parties ?? null" class="ml-1 align-middle" />
<x-court-info class="mt-1.5" :court="$hearing->court_name ?? null" :room="($hearing->court_room ?? null) ?: ($hearing->case_court_room ?? null)" :judge="($hearing->judge_name ?? null) ?: ($hearing->case_judge_name ?? null)" :vc-link="($hearing->hearing_vc_link ?? null) ?: ($hearing->case_vc_link ?? null)" />
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $hearing->case_number ?: 'Case number unavailable' }}
                        · {{ $hearing->hearing_date ? \Carbon\Carbon::parse($hearing->hearing_date)->format('d M Y') : 'Date unavailable' }}
                    </p>
                    @if($hearing->hearing_stage)
                        <p class="mt-1 text-xs text-slate-500">Stage: {{ $hearing->hearing_stage }}</p>
                    @endif
                </div>
            @empty
                <p class="p-5 text-sm text-slate-500">No hearing history is available.</p>
            @endforelse
        </div>
    </section>

</div>
@endsection