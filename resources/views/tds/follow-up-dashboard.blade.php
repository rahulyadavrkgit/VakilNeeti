@extends('layouts.app')

@section('title', 'TDS follow-ups')
@section('page-heading', 'TDS follow-ups')

@section('content')
@include('tds.partials.follow-up-navigation')
<div class="space-y-6">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Total Follow-ups</p>
            <p class="mt-2 text-2xl font-bold text-slate-800">{{ $totalFollowUps }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Completed</p>
            <p class="mt-2 text-2xl font-bold text-green-700">{{ $completedFollowUps }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Pending</p>
            <p class="mt-2 text-2xl font-bold text-amber-700">{{ $pendingFollowUps }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Overdue</p>
            <p class="mt-2 text-2xl font-bold text-red-700">{{ $overdueFollowUps }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Completion Rate</p>
            <p class="mt-2 text-2xl font-bold text-indigo-700">{{ $completionRate }}%</p>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">TDS Follow-up Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">
                Monitor deductor communication and outstanding follow-up actions.
            </p>
        </div>

        <a href="{{ route('tds.index') }}"
           class="rounded-lg border px-4 py-2 text-sm font-medium">
            TDS Register
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        <a href="{{ route('tds.follow-ups.due') }}"
           class="rounded-xl border bg-white p-5 hover:bg-slate-50">
            <p class="text-sm text-slate-500">Overdue Follow-ups</p>
            <p class="mt-2 text-3xl font-bold text-red-700">{{ $overdueCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Pending actions past their due date</p>
        </a>

        <a href="{{ route('tds.follow-ups.due') }}"
           class="rounded-xl border bg-white p-5 hover:bg-slate-50">
            <p class="text-sm text-slate-500">Due Today</p>
            <p class="mt-2 text-3xl font-bold text-amber-700">{{ $dueTodayCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Actions scheduled for today</p>
        </a>

        <a href="{{ route('tds.follow-ups.upcoming') }}"
           class="rounded-xl border bg-white p-5 hover:bg-slate-50">
            <p class="text-sm text-slate-500">Upcoming Follow-ups</p>
            <p class="mt-2 text-3xl font-bold text-indigo-700">{{ $upcomingCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Future scheduled actions</p>
        </a>

    </div>

    <div class="rounded-xl border bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b p-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Next Scheduled Follow-ups</h2>
                <p class="mt-1 text-xs text-slate-500">Showing up to 10 pending entries.</p>
            </div>

            <a href="{{ route('tds.follow-ups.upcoming') }}"
               class="text-sm font-medium text-indigo-600 hover:underline">
                View All Upcoming
            </a>
        </div>

        <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left">Scheduled Date</th>
                <th class="px-4 py-3 text-left">Client</th>
                <th class="px-4 py-3 text-left">Invoice</th>
                <th class="px-4 py-3 text-left">TDS Reference</th>
                <th class="px-4 py-3 text-left">Action</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
            @forelse($upcomingFollowUps as $followUp)
                @php
                    $deduction = $followUp->tdsDeduction;
                @endphp

                <tr>
                    <td class="whitespace-nowrap px-4 py-3 font-medium">
                        {{ $followUp->next_follow_up_date?->format('d M Y') ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $deduction?->client?->display_name ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $deduction?->invoice?->invoice_number ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $deduction?->deduction_reference ?? '—' }}
                    </td>

                    <td class="whitespace-nowrap px-4 py-3">
                        <div class="flex flex-wrap gap-2">
                            @if($deduction)
                                <a href="{{ route('tds.show', $deduction) }}"
                                   class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    View TDS
                                </a>
                            @endif

                            <form method="POST"
                                  action="{{ route('tds.follow-ups.complete', $followUp) }}"
                                 >
                                @csrf
                                @method('PUT')

                                <button type="submit"
                                        class="rounded-lg bg-[#16776e] px-3 py-2 text-xs font-medium text-white hover:bg-[#125f58]">
                                    Complete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                        No upcoming follow-ups scheduled.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('tds.follow-ups.due') }}"
           class="rounded-lg border px-4 py-2 text-sm font-medium">
            Open Due Follow-ups
        </a>

        <a href="{{ route('tds.follow-ups.upcoming') }}"
           class="rounded-lg border px-4 py-2 text-sm font-medium">
            Open Upcoming Follow-ups
        </a>
    </div>

</div>
@endsection