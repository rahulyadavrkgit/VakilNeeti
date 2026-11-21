@extends('layouts.app')
@section('title', 'Hearing History')
@section('page-heading', 'Hearing History')
@section('content')

@include('cases.partials.management-nav')

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Hearing History</h2>
            <p class="mt-1 text-sm text-slate-500">Chronological record of hearings recorded against your cases.</p>
        </div>
        <a href="{{ route('cases.calendar') }}" class="rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white">Open Calendar</a>
    </div>

    <form method="GET" action="{{ route('cases.hearing-history') }}" class="rounded-xl bg-white p-4 shadow-sm">
        <div class="flex flex-wrap gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search case title, case number or CNR" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-4 py-3">
            <button type="submit" class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white">Search</button>
            <a href="{{ route('cases.hearing-history') }}" class="rounded-lg border border-slate-300 px-5 py-3 font-semibold text-slate-700">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100 text-slate-700">
                <tr>
                    <th class="p-4">Hearing Date</th>
                    <th class="p-4">Case</th>
                    <th class="p-4">Court</th>
                    <th class="p-4">Judge</th>
                    <th class="p-4">Stage</th>
                    <th class="p-4">Proceedings</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hearings as $hearing)
                    <tr class="border-t border-slate-200 align-top">
                        <td class="whitespace-nowrap p-4 font-medium">{{ $hearing->hearing_date?->format('d M Y') ?? '—' }}</td>
                        <td class="p-4">
                            @if($hearing->legalCase)
                                <a href="{{ route('cases.show', $hearing->legalCase) }}" class="font-semibold text-blue-700 hover:underline">{{ $hearing->legalCase->case_title }}</a>
                                <p class="mt-1 text-xs text-slate-500">{{ $hearing->legalCase->case_number ?: 'Case number not recorded' }}</p>
                            @else
                                Case unavailable
                            @endif
                        </td>
                        <td class="p-4">{{ $hearing->legalCase?->court_name ?: '—' }}</td>
                        <td class="p-4">{{ $hearing->judge_name ?: $hearing->legalCase?->judge_name ?: '—' }}</td>
                        <td class="p-4">{{ $hearing->hearing_stage ?: '—' }}</td>
                        <td class="min-w-64 p-4">
                            <p class="whitespace-pre-line">{{ $hearing->proceedings ?: 'No proceedings recorded' }}</p>
                            @if($hearing->next_action)
                                <p class="mt-2 text-xs text-slate-500">Next action: {{ $hearing->next_action }}</p>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-slate-500">No hearing history found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $hearings->links() }}</div>
</div>
@endsection
