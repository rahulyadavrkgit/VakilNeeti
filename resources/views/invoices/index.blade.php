@extends('layouts.app')
@section('title', 'Invoices')
@section('page-heading', 'Invoices')
@section('content')
<div class="space-y-6">

        @include('billing.partials.tabs')

        <div class="flex justify-end">
            <a href="{{ route('invoices.create') }}"
               class="rounded-xl bg-[#238b80] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#16776e]">
                + New invoice
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
                        <tr class="border-t border-slate-200">
                            <td class="p-4 font-semibold">{{ $invoice->invoice_number }}</td>
                            <td class="p-4">{{ $invoice->client->display_name ?: $invoice->client->first_name.' '.$invoice->client->last_name }}</td>
                            <td class="p-4">{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                            <td class="p-4">{{ $invoice->due_date?->format('d-m-Y') ?? '—' }}</td>
                            <td class="p-4 font-semibold">@money($invoice->total_amount)</td>
                            <td class="p-4">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</td>
                            <td class="p-4">
                                <a href="{{ route('invoices.show', $invoice) }}"
                                   class="font-semibold text-blue-600 hover:underline">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                No invoices created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $invoices->links() }}
        </div>

    </div>
@endsection
