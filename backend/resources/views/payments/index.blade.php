@extends('layouts.app')
@section('title', 'Payments')
@section('page-heading', 'Payments')
@section('content')
<div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold">Payments</h2>
                <p class="mt-2 text-slate-600">Track client payments and receipts.</p>
            </div>

            <a href="{{ route('payments.create') }}"
               class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">
                + Record Payment
            </a>
        </div>

        @if(session('success'))
            <div class="mt-6 rounded-lg bg-green-100 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mt-6 rounded-lg bg-red-100 p-4 text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="mt-8 overflow-x-auto rounded-xl bg-white shadow-sm">
            <table class="w-full text-left">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="p-4">Receipt No.</th>
                        <th class="p-4">Client</th>
                        <th class="p-4">Invoice</th>
                        <th class="p-4">Date</th>
                        <th class="p-4">Method</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($payments as $payment)
                        <tr class="border-t border-slate-200">
                            <td class="p-4 font-semibold">{{ $payment->receipt_number }}</td>

                            <td class="p-4">
                                {{ $payment->client->display_name ?: trim($payment->client->first_name.' '.$payment->client->last_name) }}
                            </td>

                            <td class="p-4">{{ $payment->invoice->invoice_number }}</td>

                            <td class="p-4">{{ $payment->payment_date->format('d-m-Y') }}</td>

                            <td class="p-4">
                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                            </td>

                            <td class="p-4 font-semibold">
                                â‚¹{{ number_format($payment->amount, 2) }}
                            </td>

                            <td class="p-4">
                                <a href="{{ route('payments.show', $payment) }}"
                                   class="font-semibold text-blue-600 hover:underline">
                                    View Receipt
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                No payments recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $payments->links() }}
        </div>

    </div>
@endsection
