@extends('layouts.app')

@section('title', 'Record Payment')
@section('page-heading', 'Record Payment')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">

    <div>
        <p class="mt-2 text-sm text-slate-500">
            Record a payment against an outstanding invoice.
        </p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
            <p class="font-semibold">Please correct the following errors:</p>
            <ul class="mt-2 list-inside list-disc text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($invoices->isEmpty())
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-800">
            <p class="font-semibold">No eligible outstanding invoices found.</p>
            <p class="mt-1 text-sm">
                Create or select an invoice with an outstanding balance before recording a payment.
            </p>
            <a href="{{ route('invoices.create', $selectedClientId ? ['client_id' => $selectedClientId] : []) }}"
               class="mt-3 inline-block font-semibold text-blue-700 hover:underline">
                Create Invoice
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('payments.store') }}"
              class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label for="invoice_id" class="mb-2 block font-medium">Outstanding Invoice *</label>
                <select id="invoice_id" name="invoice_id" required
                        class="w-full rounded-lg border border-slate-300 p-3">
                    <option value="">Select Invoice</option>
                    @foreach ($invoices as $invoice)
                        @php
                            $received = (float) $invoice->payments->sum('amount');
                            $outstanding = (float) $invoice->total_amount - $received;
                        @endphp
                        <option value="{{ $invoice->id }}"
                            @selected((string) old('invoice_id') === (string) $invoice->id)>
                            {{ $invoice->invoice_number }}
                            — {{ $invoice->client->display_name ?: trim($invoice->client->first_name.' '.$invoice->client->last_name) }}
                            — Outstanding: @money($outstanding)
                        </option>
                    @endforeach
                </select>
                @error('invoice_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="payment_date" class="mb-2 block font-medium">Payment Date *</label>
                    <input id="payment_date" type="date" name="payment_date"
                           value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                           required class="w-full rounded-lg border border-slate-300 p-3">
                    @error('payment_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="amount" class="mb-2 block font-medium">Amount Received (@currency) *</label>
                    <input id="amount" type="number" name="amount" min="0.01" step="0.01"
                           value="{{ old('amount') }}" required
                           class="w-full rounded-lg border border-slate-300 p-3">
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="payment_method" class="mb-2 block font-medium">Payment Method *</label>
                <select id="payment_method" name="payment_method" required
                        class="w-full rounded-lg border border-slate-300 p-3">
                    <option value="">Select Method</option>
                    @foreach ([
                        'cash' => 'Cash',
                        'upi' => 'UPI',
                        'bank_transfer' => 'Bank Transfer',
                        'cheque' => 'Cheque',
                        'card' => 'Card',
                        'other' => 'Other'
                    ] as $value => $label)
                        <option value="{{ $value }}"
                            @selected(old('payment_method') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('payment_method')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="transaction_reference" class="mb-2 block font-medium">
                    Transaction Reference
                </label>
                <input id="transaction_reference" type="text" name="transaction_reference"
                       maxlength="255" value="{{ old('transaction_reference') }}"
                       class="w-full rounded-lg border border-slate-300 p-3">
                @error('transaction_reference')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="mb-2 block font-medium">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full rounded-lg border border-slate-300 p-3">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">
                    Record Payment
                </button>
                <a href="{{ route('payments.index') }}"
                   class="rounded-lg border border-slate-300 px-6 py-3">
                    Cancel
                </a>
            </div>
        </form>
    @endif
</div>
@endsection