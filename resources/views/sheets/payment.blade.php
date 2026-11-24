{{-- Pop-up: record a payment against an invoice (full or part). Saves through payments.store. --}}
@php
    $field = 'mt-1 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2.5 text-base sm:text-sm';
    $label = 'block text-sm font-semibold text-slate-700';
@endphp
<h2 data-sheet-heading class="text-lg font-semibold text-slate-900">{{ __('Record payment') }}</h2>
<p class="mt-0.5 text-sm text-slate-600">{{ $invoice->invoice_number }}@if($invoice->client) · {{ $invoice->client->displayName() }}@endif · {{ __('Balance') }} <strong class="tabular-nums text-slate-900">{{ number_format($balance, 2) }}</strong></p>

<form method="POST" action="{{ route('payments.store') }}" data-sheet-form data-sheet-success="{{ __('Payment recorded') }}" class="mt-4 space-y-3">
    @csrf
    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
    <div class="grid grid-cols-2 gap-3">
        <label class="{{ $label }}">
            {{ __('Amount') }}
            <input type="number" name="amount" required min="0.01" step="0.01" value="{{ number_format($balance, 2, '.', '') }}" autofocus class="{{ $field }} tabular-nums">
        </label>
        <label class="{{ $label }}">
            {{ __('Date') }}
            <input type="date" name="payment_date" required value="{{ today()->toDateString() }}" class="{{ $field }}">
        </label>
    </div>
    <label class="{{ $label }}">
        {{ __('Paid by') }}
        <select name="payment_method" required class="{{ $field }}">
            @foreach(['upi' => 'UPI', 'bank_transfer' => 'Bank transfer', 'cash' => 'Cash', 'cheque' => 'Cheque', 'card' => 'Card', 'other' => 'Other'] as $value => $text)
                <option value="{{ $value }}">{{ __($text) }}</option>
            @endforeach
        </select>
    </label>
    <label class="{{ $label }}">
        {{ __('Reference') }} <span class="font-normal text-slate-500">({{ __('UTR, cheque no.') }})</span>
        <input type="text" name="transaction_reference" maxlength="255" class="{{ $field }}">
    </label>
    <div class="flex justify-end pt-1">
        <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Save payment') }}</button>
    </div>
</form>
