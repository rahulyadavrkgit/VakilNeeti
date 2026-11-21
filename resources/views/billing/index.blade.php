@extends('layouts.app')

@section('title', 'Invoices & Payments')
@section('page-heading', 'Invoices & Payments')

@section('content')
<div class="space-y-8">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Invoices & Payments</h2>
            <p class="text-sm text-slate-500">
                Manage client billing, issued invoices and payment receipts.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('invoices.create') }}"
               class="rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white">
                + Create Invoice
            </a>

            <a href="{{ route('payments.create') }}"
               class="rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                + Record Payment
            </a>
        </div>
    </div>

    <!-- INVOICES -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900">Invoices</h3>
            <a href="{{ route('invoices.index') }}"
               class="text-sm font-semibold text-blue-700 hover:underline">
                View All Invoices
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="p-4">Invoice No.</th>
                        <th class="p-4">Client</th>
                        <th class="p-4">Invoice Date</th>
                        <th class="p-4">Due Date</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($invoices as $invoice)
                        <tr class="border-t hover:bg-slate-50">
                            <td class="p-4 font-semibold">
                                {{ $invoice->invoice_number }}
                            </td>

                            <td class="p-4">
                                @if($invoice->client)
                                    <a href="{{ route('clients.show', $invoice->client) }}"
                                       class="text-blue-700 hover:underline">
                                        {{ $invoice->client->display_name ?: trim($invoice->client->first_name.' '.$invoice->client->last_name) }}
                                    </a>
                                @else
                                    Client unavailable
                                @endif
                            </td>

                            <td class="p-4 whitespace-nowrap">
                                {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') : '—' }}
                            </td>

                            <td class="p-4 whitespace-nowrap">
                                {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d-m-Y') : '—' }}
                            </td>

                            <td class="p-4 whitespace-nowrap font-medium">
                                ₹{{ number_format((float)$invoice->total_amount, 2) }}
                            </td>

                            <td class="p-4">
                                {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                            </td>

                            <td class="p-4 whitespace-nowrap">
                                <a href="{{ route('invoices.show', $invoice) }}"
                                   class="font-semibold text-blue-700 hover:underline">
                                    View Invoice
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                No invoices found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $invoices->links() }}
    </section>

    <!-- PAYMENTS -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900">Payments</h3>
            <a href="{{ route('payments.index') }}"
               class="text-sm font-semibold text-blue-700 hover:underline">
                View All Payments
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="p-4">Receipt No.</th>
                        <th class="p-4">Client</th>
                        <th class="p-4">Invoice</th>
                        <th class="p-4">Payment Date</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Method</th>
                        <th class="p-4">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($payments as $payment)
                        <tr class="border-t hover:bg-slate-50">
                            <td class="p-4 font-semibold">
                                {{ $payment->receipt_number }}
                            </td>

                            <td class="p-4">
                                @if($payment->client)
                                    <a href="{{ route('clients.show', $payment->client) }}"
                                       class="text-blue-700 hover:underline">
                                        {{ $payment->client->display_name ?: trim($payment->client->first_name.' '.$payment->client->last_name) }}
                                    </a>
                                @else
                                    Client unavailable
                                @endif
                            </td>

                            <td class="p-4">
                                {{ $payment->invoice->invoice_number ?? '—' }}
                            </td>

                            <td class="p-4 whitespace-nowrap">
                                {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : '—' }}
                            </td>

                            <td class="p-4 whitespace-nowrap font-medium">
                                ₹{{ number_format((float)$payment->amount, 2) }}
                            </td>

                            <td class="p-4">
                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                            </td>

                            <td class="p-4 whitespace-nowrap">
                                <a href="{{ route('payments.show', $payment) }}"
                                   class="font-semibold text-blue-700 hover:underline">
                                    View Receipt
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                No payments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $payments->links() }}
    </section>

</div>
@endsection