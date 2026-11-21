@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Record TDS Deduction</h1>
        <p class="text-sm text-slate-500">Record tax withheld by a client against an invoice.</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('tds.store') }}"
          class="space-y-5 rounded-xl border border-slate-200 bg-white p-6">
        @csrf

        <div>
            <label class="mb-1 block text-sm font-medium">Invoice</label>
            <select name="invoice_id" required class="w-full rounded-lg border p-2">
                <option value="">Select invoice</option>
                @foreach($invoices as $invoice)
                    @php
                        $cash = (float) $invoice->payments->sum('amount');
                        $tds = (float) $invoice->tdsDeductions->sum('amount');
                        $balance = (float) $invoice->total_amount - $cash - $tds;
                    @endphp
                    <option value="{{ $invoice->id }}" @selected(old('invoice_id') == $invoice->id)>
                        {{ $invoice->invoice_number }} — {{ $invoice->client->display_name }}
                        — Balance ₹{{ number_format($balance, 2) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Deduction Date</label>
                <input type="date" name="deduction_date" required
                       value="{{ old('deduction_date', date('Y-m-d')) }}"
                       class="w-full rounded-lg border p-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">TDS Amount (₹)</label>
                <input type="number" name="amount" min="0.01" step="0.01" required
                       value="{{ old('amount') }}" class="w-full rounded-lg border p-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">TDS Section</label>
                <input type="text" name="tds_section" value="{{ old('tds_section') }}"
                       placeholder="Applicable section"
                       class="w-full rounded-lg border p-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Deduction Rate (%)</label>
                <input type="number" name="deduction_rate" min="0" max="100" step="0.001"
                       value="{{ old('deduction_rate') }}"
                       class="w-full rounded-lg border p-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Deduction Base Amount (₹)</label>
                <input type="number" name="deduction_base_amount" min="0" step="0.01"
                       value="{{ old('deduction_base_amount') }}"
                       class="w-full rounded-lg border p-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Financial Year</label>
                <input type="text" name="financial_year" maxlength="10"
                       value="{{ old('financial_year') }}"
                       placeholder="e.g. 2026-27"
                       class="w-full rounded-lg border p-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">TDS Certificate Number</label>
                <input type="text" name="certificate_number"
                       value="{{ old('certificate_number') }}"
                       class="w-full rounded-lg border p-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Certificate Date</label>
                <input type="date" name="certificate_date"
                       value="{{ old('certificate_date') }}"
                       class="w-full rounded-lg border p-2">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Certificate Status</label>
            <select name="certificate_status" required class="w-full rounded-lg border p-2">
                <option value="awaited">Awaited</option>
                <option value="received">Received</option>
                <option value="verified">Verified</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Notes</label>
            <textarea name="notes" rows="3"
                      class="w-full rounded-lg border p-2">{{ old('notes') }}</textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('tds.index') }}"
               class="rounded-lg border px-4 py-2 text-sm">Cancel</a>
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white">
                Save TDS Deduction
            </button>
        </div>
    </form>
</div>
@endsection