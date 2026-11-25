@extends('layouts.app')

@section('title', 'Overdue payments')
@section('page-heading', 'Overdue payments')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Payment overdues</h2>
            <p class="mt-1 text-sm text-slate-600">Review unpaid balances and record payments.</p>
        </div>
        <form method="GET" action="{{ route('billing.overdues') }}" role="search" class="w-full sm:w-auto">
            <label for="overdue-search" class="text-sm font-semibold text-slate-800">Find by client or invoice</label>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <input id="overdue-search" type="search" name="q" maxlength="100" value="{{ $search }}" class="min-h-11 min-w-0 flex-1 rounded-xl border-slate-300">
                <button class="min-h-11 rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white">Search</button>
                @if($search !== '')<a href="{{ route('billing.overdues') }}" class="text-sm font-semibold underline">Clear</a>@endif
            </div>
        </form>
    </div>

    @foreach([
        ['id' => 'pending', 'title' => 'Pending payments', 'rows' => $pending, 'empty' => 'No overdue invoices without payment.', 'partial' => false],
        ['id' => 'partly-paid', 'title' => 'Partly paid', 'rows' => $partial, 'empty' => 'No partly paid overdue invoices.', 'partial' => true],
    ] as $group)
        <section id="{{ $group['id'] }}" class="scroll-mt-24 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-200 px-5 py-4 text-lg font-semibold text-slate-900">{{ $group['title'] }} <span class="text-sm font-normal text-slate-500">({{ $group['rows']->total() }})</span></h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="p-3">Client</th>
                            <th class="p-3">Invoice</th>
                            <th class="p-3">Due date</th>
                            @if($group['partial'])<th class="p-3 text-right">Paid</th>@endif
                            <th class="p-3 text-right">Balance</th>
                            <th class="p-3 text-right">Days overdue</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($group['rows'] as $invoice)
                            <tr>
                                <td class="p-3">{{ $invoice->client?->displayName() }}</td>
                                <td class="p-3"><a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-[#16776e] hover:underline">{{ $invoice->invoice_number }}</a></td>
                                <td class="p-3">{{ $invoice->due_date->format('d M Y') }}</td>
                                @if($group['partial'])<td class="p-3 text-right">@money($invoice->amountPaid())</td>@endif
                                <td class="p-3 text-right font-semibold">@money($invoice->balanceDue())</td>
                                <td class="p-3 text-right"><span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">{{ (int) $invoice->due_date->diffInDays(today()) }}</span></td>
                                <td class="p-3">
                                    <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                                        <a href="{{ route('payments.create', ['invoice_id' => $invoice->id, 'client_id' => $invoice->client_id]) }}" class="text-sm font-semibold text-[#16776e] hover:underline">Record payment</a>
                                        <form method="POST" action="{{ route('invoices.write-off', $invoice) }}" data-confirm="Write off the remaining balance of {{ $invoice->invoice_number }}?">
                                            @csrf
                                            <button type="submit" class="text-sm text-slate-600 hover:underline">Write off</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $group['partial'] ? 7 : 6 }}" class="p-6 text-center text-slate-500">{{ $search === '' ? $group['empty'] : 'No matching invoices.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($group['rows']->hasPages())<div class="border-t border-slate-200 p-3">{{ $group['rows']->links() }}</div>@endif
        </section>
    @endforeach

    <section id="written-off" class="scroll-mt-24 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <h2 class="border-b border-slate-200 px-5 py-4 text-lg font-semibold text-slate-900">Written off <span class="text-sm font-normal text-slate-500">({{ $writtenOff->total() }})</span></h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr><th class="p-3">Client</th><th class="p-3">Invoice</th><th class="p-3">Written off</th><th class="p-3 text-right">Amount written off</th><th class="p-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($writtenOff as $invoice)
                        <tr>
                            <td class="p-3">{{ $invoice->client?->displayName() }}</td>
                            <td class="p-3"><a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-[#16776e] hover:underline">{{ $invoice->invoice_number }}</a></td>
                            <td class="p-3">{{ $invoice->written_off_at->format('d M Y') }}@if($invoice->write_off_reason)<span class="block text-xs text-slate-500">{{ $invoice->write_off_reason }}</span>@endif</td>
                            <td class="p-3 text-right">@money(max(0, (float) $invoice->total_amount - $invoice->amountPaid()))</td>
                            <td class="p-3 text-right">
                                <form method="POST" action="{{ route('invoices.undo-write-off', $invoice) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-semibold text-[#16776e] hover:underline">Undo</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-slate-500">No written-off invoices.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($writtenOff->hasPages())<div class="border-t border-slate-200 p-3">{{ $writtenOff->links() }}</div>@endif
    </section>
</div>
@endsection
