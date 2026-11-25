@extends('layouts.app')

@php
    $isEditing = $invoice->exists;
    $field = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5';
    $items = old('items', $invoice->items->map(fn ($item) => $item->only(['description', 'quantity', 'rate', 'tax_rate', 'tax_mode', 'source_type', 'source_id']))->all());
    if ($items === []) {
        $items = [['description' => '', 'quantity' => 1, 'rate' => '', 'tax_rate' => $gstEnabled ? $defaultTaxRate : 0, 'tax_mode' => $defaultTaxMode]];
    }
    $taxRates = [0, 5, 12, 18, 28];
@endphp

@section('title', $isEditing ? 'Edit invoice' : 'New invoice')
@section('page-heading', $isEditing ? 'Edit invoice '.$invoice->invoice_number : 'New invoice')

@section('content')
<form method="POST" action="{{ $isEditing ? route('invoices.update', $invoice) : route('invoices.store') }}"
      class="mx-auto max-w-5xl space-y-6"
      data-invoice-editor
      data-billables-url="{{ url('/cases') }}"
      data-invoice-id="{{ $invoice->id }}"
      data-firm-state="{{ $firmState }}"
      data-terms='@json(\App\Models\Invoice::PAYMENT_TERMS)'
      data-currency="{{ \App\Enums\Currency::current()->symbol() }}">
    @csrf
    @if($isEditing) @method('PUT') @endif

    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-slate-900">Invoice</h2>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">{{ $isEditing ? $invoice->invoice_number : 'No. '.$nextNumber }}</span>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="text-sm font-medium text-slate-700">
                Client <span class="text-red-600">*</span>
                <select name="client_id" required class="{{ $field }}">
                    <option value="">Select client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected((string) old('client_id', $invoice->client_id) === (string) $client->id)>{{ $client->displayName() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-slate-700">
                Case
                <select name="legal_case_id" data-invoice-case class="{{ $field }}">
                    <option value="">No case</option>
                    @foreach($cases as $caseOption)
                        <option value="{{ $caseOption->id }}" @selected((string) old('legal_case_id', $invoice->legal_case_id) === (string) $caseOption->id)>{{ $caseOption->case_title }}{{ $caseOption->case_number ? ' · '.$caseOption->case_number : '' }}</option>
                    @endforeach
                </select>
            </label>
            @if($isEditing)
                <label class="text-sm font-medium text-slate-700">
                    Status
                    <select name="status" class="{{ $field }}">
                        @foreach(array_diff(\App\Models\Invoice::STATUSES, ['written_off']) as $status)
                            <option value="{{ $status }}" @selected(old('status', $invoice->status) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label class="text-sm font-medium text-slate-700">
                Invoice date <span class="text-red-600">*</span>
                <input type="date" name="invoice_date" required data-invoice-date value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d')) }}" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Payment terms
                <select name="payment_terms" data-invoice-terms class="{{ $field }}">
                    @foreach(array_keys(\App\Models\Invoice::PAYMENT_TERMS) as $term)
                        <option value="{{ $term }}" @selected(old('payment_terms', $invoice->payment_terms) === $term)>{{ \App\Models\Invoice::paymentTermLabel($term) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-slate-700">
                Due date
                <input type="date" name="due_date" data-invoice-due value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" class="{{ $field }}">
            </label>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-lg font-semibold text-slate-900">Client tax details</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <label class="text-sm font-medium text-slate-700">
                Client PAN
                <input name="client_pan" maxlength="10" value="{{ old('client_pan', $invoice->client_pan) }}" placeholder="ABCDE1234F" class="{{ $field }} uppercase">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Client GSTIN
                <input name="client_gstin" maxlength="15" value="{{ old('client_gstin', $invoice->client_gstin) }}" placeholder="15 characters" class="{{ $field }} uppercase">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Place of supply
                <select name="place_of_supply" data-invoice-place class="{{ $field }}">
                    <x-state-options :groups="\App\Models\State::dropdownGroups()" :selected="old('place_of_supply', $invoice->place_of_supply)" placeholder="Select State / UT" />
                </select>
            </label>
            <label class="text-sm font-medium text-slate-700 sm:col-span-3">
                Billing address
                <textarea name="billing_address" rows="2" maxlength="1000" class="{{ $field }}">{{ old('billing_address', $invoice->billing_address) }}</textarea>
            </label>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-slate-900">Line items</h2>
            <button type="button" data-invoice-add-row class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">+ Add line</button>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="pb-2">Description</th>
                        <th class="w-20 pb-2">Qty</th>
                        <th class="w-32 pb-2">Rate (@currency)</th>
                        <th class="w-24 pb-2">GST %</th>
                        <th class="w-32 pb-2">Tax</th>
                        <th class="w-28 pb-2 text-right">Amount</th>
                        <th class="w-10 pb-2"></th>
                    </tr>
                </thead>
                <tbody data-invoice-rows>
                    @foreach($items as $index => $item)
                        <tr data-invoice-row class="align-top">
                            <td class="py-1 pr-2">
                                <input name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" required maxlength="500" placeholder="e.g. Appearance fee" class="w-full rounded-lg border border-slate-300 px-2 py-2">
                                <input type="hidden" name="items[{{ $index }}][source_type]" value="{{ $item['source_type'] ?? '' }}" data-field="source_type">
                                <input type="hidden" name="items[{{ $index }}][source_id]" value="{{ $item['source_id'] ?? '' }}" data-field="source_id">
                            </td>
                            <td class="py-1 pr-2"><input type="number" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" step="0.01" min="0.01" required data-field="quantity" class="w-full rounded-lg border border-slate-300 px-2 py-2"></td>
                            <td class="py-1 pr-2"><input type="number" name="items[{{ $index }}][rate]" value="{{ $item['rate'] ?? '' }}" step="0.01" min="0" required data-field="rate" class="w-full rounded-lg border border-slate-300 px-2 py-2"></td>
                            <td class="py-1 pr-2">
                                <select name="items[{{ $index }}][tax_rate]" data-field="tax_rate" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2">
                                    @foreach($taxRates as $rate)
                                        <option value="{{ $rate }}" @selected((float) ($item['tax_rate'] ?? 0) === (float) $rate)>{{ $rate }}%</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-1 pr-2">
                                <select name="items[{{ $index }}][tax_mode]" data-field="tax_mode" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2">
                                    <option value="exclusive" @selected(($item['tax_mode'] ?? 'exclusive') === 'exclusive')>Tax extra</option>
                                    <option value="inclusive" @selected(($item['tax_mode'] ?? '') === 'inclusive')>Tax included</option>
                                </select>
                            </td>
                            <td class="py-1 pr-2 pt-3 text-right font-medium text-slate-800" data-line-total>—</td>
                            <td class="py-1 pt-2"><button type="button" data-invoice-remove-row aria-label="Remove line" class="rounded-lg px-2 py-1 text-red-600 hover:bg-red-50">✕</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div data-invoice-billables hidden class="mt-4 rounded-lg border border-[#b8dfd9] bg-[#f3faf9] p-3">
            <p class="text-sm font-semibold text-slate-800">Unbilled from this case</p>
            <div data-invoice-billables-list class="mt-2 space-y-1 text-sm"></div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <label class="block text-sm font-medium text-slate-700">
                Payment terms &amp; notes
                <textarea name="notes" rows="3" maxlength="2000" class="{{ $field }}">{{ old('notes', $invoice->notes) }}</textarea>
            </label>
            <label class="mt-4 block text-sm font-medium text-slate-700">
                Description <span class="font-normal text-slate-400">(optional)</span>
                <textarea name="description" rows="2" maxlength="2000" class="{{ $field }}">{{ old('description', $invoice->description) }}</textarea>
            </label>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-sm sm:p-6">
            <dl class="space-y-2">
                <div class="flex justify-between"><dt class="text-slate-600">Subtotal</dt><dd data-total="subtotal" class="font-medium">—</dd></div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-600"><label for="invoice-discount">Discount</label></dt>
                    <dd><input id="invoice-discount" type="number" name="discount" step="0.01" min="0" value="{{ old('discount', $invoice->discount ?: '') }}" data-invoice-discount placeholder="0.00" class="w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-right"></dd>
                </div>
                <div class="flex justify-between" data-tax-row="cgst"><dt class="text-slate-600">CGST</dt><dd data-total="cgst">—</dd></div>
                <div class="flex justify-between" data-tax-row="sgst"><dt class="text-slate-600">SGST</dt><dd data-total="sgst">—</dd></div>
                <div class="flex justify-between" data-tax-row="igst"><dt class="text-slate-600">IGST</dt><dd data-total="igst">—</dd></div>
                <div class="flex justify-between" data-tax-row="tax"><dt class="text-slate-600">Tax</dt><dd data-total="tax">—</dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold"><dt>Grand total</dt><dd data-total="total">—</dd></div>
            </dl>
            <button type="submit" class="mt-5 w-full rounded-xl bg-[#238b80] px-5 py-3 font-semibold text-white hover:bg-[#16776e]">
                {{ $isEditing ? 'Save invoice' : 'Create invoice' }}
            </button>
        </div>
    </section>
</form>
@endsection
