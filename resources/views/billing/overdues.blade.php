@extends('layouts.app')

@section('title', 'Overdue payments')
@section('page-heading', 'Overdue payments')

@section('content')
<div class="space-y-6">
    @include('billing.partials.tabs')

    @foreach([
        ['title' => 'Pending payments', 'rows' => $pending, 'empty' => 'No overdue invoices without payment.', 'partial' => false],
        ['title' => 'Partly paid', 'rows' => $partial, 'empty' => 'No partly paid overdue invoices.', 'partial' => true],
    ] as $group)
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-200 px-5 py-4 text-lg font-semibold text-slate-900">{{ $group['title'] }} <span class="text-sm font-normal text-slate-500">({{ $group['rows']->count() }})</span></h2>
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
                            <tr><td colspan="7" class="p-6 text-center text-slate-500">{{ $group['empty'] }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <h2 class="border-b border-slate-200 px-5 py-4 text-lg font-semibold text-slate-900">Written off <span class="text-sm font-normal text-slate-500">({{ $writtenOff->count() }})</span></h2>
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
    </section>
</div>
@endsection
