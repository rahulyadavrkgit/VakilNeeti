@extends('layouts.app')

@section('title', 'Time sheet')
@section('page-heading', 'Time sheet')

@section('content')
@php $field = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2'; @endphp
<div class="space-y-6">
    @include('billing.partials.tabs')

    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Add time</h2>
        <form method="POST" action="{{ route('billing.time.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_2fr_1fr_80px_80px_1fr_auto] lg:items-end">
            @csrf
            <label class="text-sm font-medium text-slate-700">Case
                <select name="case_id" required class="{{ $field }}">
                    <option value="">Select case</option>
                    @foreach($cases as $caseOption)
                        <option value="{{ $caseOption->id }}" @selected((string) old('case_id') === (string) $caseOption->id)>{{ $caseOption->case_title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-slate-700">Description
                <input name="description" required maxlength="500" value="{{ old('description') }}" placeholder="e.g. Drafting reply" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Date
                <input type="date" name="entry_date" required value="{{ old('entry_date', today()->format('Y-m-d')) }}" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Hours
                <input type="number" name="hours" min="0" max="24" required value="{{ old('hours', 0) }}" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Mins
                <input type="number" name="minutes" min="0" max="59" required value="{{ old('minutes', 30) }}" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Rate / hour
                <input type="number" name="hourly_rate" min="0" step="0.01" value="{{ old('hourly_rate') }}" placeholder="Optional" class="{{ $field }}">
            </label>
            <button type="submit" class="rounded-lg bg-[#238b80] px-4 py-2 text-sm font-semibold text-white hover:bg-[#16776e]">Add</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <form method="GET" action="{{ route('billing.time') }}" class="flex flex-wrap items-end gap-2 text-sm">
                <label class="font-medium text-slate-700">Case
                    <select name="case_id" class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-2">
                        <option value="">All cases</option>
                        @foreach($cases as $caseOption)
                            <option value="{{ $caseOption->id }}" @selected((string) request('case_id') === (string) $caseOption->id)>{{ $caseOption->case_title }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="font-medium text-slate-700">From <input type="date" name="from" value="{{ request('from') }}" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2"></label>
                <label class="font-medium text-slate-700">To <input type="date" name="to" value="{{ request('to') }}" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2"></label>
                <button type="submit" class="rounded-lg bg-[#142235] px-4 py-2 font-semibold text-white">Filter</button>
            </form>
            <div class="flex items-center gap-3 text-sm">
                <span class="font-semibold text-slate-700">Total: {{ intdiv($totalMinutes, 60) }}h {{ $totalMinutes % 60 }}m</span>
                <a href="{{ route('billing.time.export', request()->query()) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 font-semibold text-slate-700 hover:bg-slate-50">Export CSV</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr><th class="p-3">Date</th><th class="p-3">Case</th><th class="p-3">Description</th><th class="p-3 text-right">Time</th><th class="p-3 text-right">Value</th><th class="p-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $entry)
                        <tr>
                            <td class="p-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($entry->entry_date)->format('d M Y') }}</td>
                            <td class="p-3"><a href="{{ route('cases.show', $entry->case_id) }}#case-time" class="text-[#16776e] hover:underline">{{ $entry->legalCase?->case_title }}</a></td>
                            <td class="p-3">{{ $entry->description }}</td>
                            <td class="p-3 text-right whitespace-nowrap">{{ $entry->hours }}h {{ $entry->minutes }}m</td>
                            <td class="p-3 text-right">{{ $entry->hourly_rate ? \App\Enums\Currency::current()->format(($entry->hours + $entry->minutes / 60) * $entry->hourly_rate) : '—' }}</td>
                            <td class="p-3 text-right">@if($entry->invoice_id)<a href="{{ route('invoices.show', $entry->invoice_id) }}" class="rounded-full bg-[#e8f4f2] px-2 py-0.5 text-xs font-semibold text-[#16776e]">Billed</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-slate-500">No time entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 p-4">{{ $entries->links() }}</div>
    </section>
</div>
@endsection
