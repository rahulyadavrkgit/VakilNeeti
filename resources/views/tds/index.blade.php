@extends('layouts.app')

@section('title', 'TDS deductions')
@section('page-heading', 'TDS deductions')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    <x-page-header title="TDS Reconciliation Register" subtitle="Deductions, certificates and reconciliation.">
        <a href="{{ route('tds.follow-up-dashboard') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Follow-ups</a>
        <a href="{{ route('tds.follow-ups.due') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Due</a>
        <a href="{{ route('tds.follow-ups.upcoming') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Upcoming</a>
        <a href="{{ route('tds.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">+ Record TDS deduction</a>
    </x-page-header>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border bg-white p-5">
            <p class="text-sm text-slate-500">Filtered TDS Amount</p>
            <p class="mt-2 text-2xl font-bold text-slate-800">&#8377;{{ number_format((float) $totalAmount, 2) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $totalRecords }} records</p>
        </div>

        <div class="rounded-xl border bg-white p-5">
            <p class="text-sm text-slate-500">Matched in 26AS / AIS</p>
            <p class="mt-2 text-2xl font-bold text-green-700">&#8377;{{ number_format((float) $matchedAmount, 2) }}</p>
        </div>

        <div class="rounded-xl border bg-white p-5">
            <p class="text-sm text-slate-500">Not Reflected</p>
            <p class="mt-2 text-2xl font-bold text-red-700">&#8377;{{ number_format((float) $notReflectedAmount, 2) }}</p>
        </div>

        <div class="rounded-xl border bg-white p-5">
            <p class="text-sm text-slate-500">Mismatch</p>
            <p class="mt-2 text-2xl font-bold text-amber-700">&#8377;{{ number_format((float) $mismatchAmount, 2) }}</p>
        </div>

        <div class="rounded-xl border bg-white p-5">
            <p class="text-sm text-slate-500">Not Checked</p>
            <p class="mt-2 text-2xl font-bold text-slate-700">&#8377;{{ number_format((float) $uncheckedAmount, 2) }}</p>
        </div>
    </section>

    <section class="rounded-xl border bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Filter TDS Records</h2>

        <form method="GET" action="{{ route('tds.index') }}"
              class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div>
                <label class="mb-1 block text-sm font-medium">Financial Year</label>
                <select name="financial_year" class="w-full rounded-lg border p-2">
                    <option value="">All financial years</option>
                    @foreach($financialYears as $year)
                        <option value="{{ $year }}" @selected($selectedYear === $year)>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Certificate Status</label>
                <select name="certificate_status" class="w-full rounded-lg border p-2">
                    <option value="">All certificate statuses</option>
                    <option value="awaited" @selected($certificateStatus === 'awaited')>Awaited</option>
                    <option value="received" @selected($certificateStatus === 'received')>Received</option>
                    <option value="verified" @selected($certificateStatus === 'verified')>Verified</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">26AS / AIS Credit Status</label>
                <select name="credit_status" class="w-full rounded-lg border p-2">
                    <option value="">All credit statuses</option>
                    <option value="not_checked" @selected($creditStatus === 'not_checked')>Not checked</option>
                    <option value="matched" @selected($creditStatus === 'matched')>Matched</option>
                    <option value="not_reflected" @selected($creditStatus === 'not_reflected')>Not reflected</option>
                    <option value="mismatch" @selected($creditStatus === 'mismatch')>Mismatch</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Search</label>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Client, invoice or reference"
                       class="w-full rounded-lg border p-2">
            </div>

            <div class="flex flex-wrap items-end gap-2 sm:col-span-2 lg:col-span-4">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white">
                    Apply Filters
                </button>

                <a href="{{ route('tds.index') }}"
                   class="rounded-lg border px-5 py-2 text-sm">
                    Clear Filters
                </a>
            </div>
        </form>
    </section>

    <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="vn-stack-table min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="p-3">Reference</th>
                    <th class="p-3">Date</th>
                    <th class="p-3">Client</th>
                    <th class="p-3">Invoice</th>
                    <th class="p-3">Section</th>
                    <th class="p-3">Amount</th>
                    <th class="p-3">Certificate</th>
                    <th class="p-3">26AS / AIS</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($deductions as $deduction)
                    <tr>
                        <td class="p-3">
                            <a class="text-indigo-600 hover:underline"
                               href="{{ route('tds.show', $deduction) }}">
                                {{ $deduction->deduction_reference }}
                            </a>
                        </td>
                        <td class="p-3">{{ $deduction->deduction_date?->format('d-m-Y') }}</td>
                        <td class="p-3">{{ $deduction->client?->display_name ?? 'Unavailable' }}</td>
                        <td class="p-3">{{ $deduction->invoice?->invoice_number ?? 'Unavailable' }}</td>
                        <td class="p-3">{{ $deduction->tds_section ?: '—' }}</td>
                        <td class="p-3">&#8377;{{ number_format((float) $deduction->amount, 2) }}</td>
                        <td class="p-3">{{ ucfirst($deduction->certificate_status) }}</td>
                        <td class="p-3">
                            @switch($deduction->credit_status ?? 'not_checked')
                                @case('matched')
                                    <span class="font-medium text-green-700">Matched</span>
                                    @break
                                @case('not_reflected')
                                    <span class="font-medium text-red-700">Not reflected</span>
                                    @break
                                @case('mismatch')
                                    <span class="font-medium text-amber-700">Mismatch</span>
                                    @break
                                @default
                                    <span class="text-slate-500">Not checked</span>
                            @endswitch
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-6 text-center text-slate-500">
                            No TDS records match the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    {{ $deductions->links() }}

    <p class="text-xs text-slate-500">Totals follow the filters. Statuses are your own records, not verified by the Income Tax Department.</p>
</div>
@endsection