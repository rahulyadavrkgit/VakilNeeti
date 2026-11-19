@extends('layouts.app')

@section('title', 'Create Invoice')
@section('page-heading', 'Create Invoice')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">

    <div>
        <a href="{{ route('invoices.index') }}" class="text-blue-600 hover:underline">
            &larr; Back to Invoices
        </a>
        <p class="mt-2 text-sm text-slate-500">
            Create an invoice for a client.
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

    <form method="POST" action="{{ route('invoices.store') }}"
          class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="client_id" class="mb-2 block font-medium">Client *</label>
            <select id="client_id" name="client_id" required
                    class="w-full rounded-lg border border-slate-300 p-3">
                <option value="">Select Client</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}"
                        @selected((string) old('client_id', $selectedClientId) === (string) $client->id)>
                        {{ $client->display_name ?: trim($client->first_name.' '.$client->last_name) }}
                        (ID: {{ $client->id }})
                    </option>
                @endforeach
            </select>
            @error('client_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="invoice_date" class="mb-2 block font-medium">Invoice Date *</label>
                <input id="invoice_date" type="date" name="invoice_date"
                       value="{{ old('invoice_date', now()->format('Y-m-d')) }}"
                       required class="w-full rounded-lg border border-slate-300 p-3">
                @error('invoice_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="due_date" class="mb-2 block font-medium">Due Date</label>
                <input id="due_date" type="date" name="due_date"
                       value="{{ old('due_date') }}"
                       class="w-full rounded-lg border border-slate-300 p-3">
                @error('due_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
            <div>
                <label for="subtotal" class="mb-2 block font-medium">Subtotal (₹) *</label>
                <input id="subtotal" type="number" name="subtotal" min="0" step="0.01"
                       value="{{ old('subtotal', '0.00') }}" required
                       class="w-full rounded-lg border border-slate-300 p-3">
                @error('subtotal')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="discount" class="mb-2 block font-medium">Discount (₹)</label>
                <input id="discount" type="number" name="discount" min="0" step="0.01"
                       value="{{ old('discount', '0.00') }}"
                       class="w-full rounded-lg border border-slate-300 p-3">
                @error('discount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="tax_amount" class="mb-2 block font-medium">Tax Amount (₹)</label>
                <input id="tax_amount" type="number" name="tax_amount" min="0" step="0.01"
                       value="{{ old('tax_amount', '0.00') }}"
                       class="w-full rounded-lg border border-slate-300 p-3">
                @error('tax_amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="description" class="mb-2 block font-medium">Description / Particulars</label>
            <textarea id="description" name="description" rows="4"
                      class="w-full rounded-lg border border-slate-300 p-3">{{ old('description') }}</textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="notes" class="mb-2 block font-medium">Internal Notes</label>
            <textarea id="notes" name="notes" rows="3"
                      class="w-full rounded-lg border border-slate-300 p-3">{{ old('notes') }}</textarea>
            @error('notes')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit"
                    class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">
                Create Invoice
            </button>
            <a href="{{ route('invoices.index') }}"
               class="rounded-lg border border-slate-300 px-6 py-3">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection