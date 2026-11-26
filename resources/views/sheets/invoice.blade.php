{{--
    Pop-up: new invoice, step 2 of 2. One line of work and its fee, the date and terms, and "already received",
    which records the full payment with the invoice. Saves through invoices.store and opens the new invoice.
--}}
@php
    $field = 'mt-1 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2.5 text-base sm:text-sm';
    $label = 'block text-sm font-semibold text-slate-700';
    $terms = ['due_on_receipt' => 'On receipt', 'net_7' => '7 days', 'net_15' => '15 days', 'net_30' => '30 days'];
@endphp
<p class="text-xs font-bold uppercase tracking-wide text-[#16776e]">{{ __('New invoice · step 2 of 2') }}</p>
<h2 data-sheet-heading class="mt-1 text-lg font-semibold text-slate-900">{{ $client->displayName() }}</h2>
<a href="{{ route('invoices.create') }}" data-sheet="{{ route('sheets.invoice') }}" class="text-sm font-semibold text-[#16776e] hover:underline">{{ __('Change client') }}</a>

<form method="POST" action="{{ route('invoices.store') }}" data-sheet-form data-sheet-follow data-sheet-success="{{ __('Invoice created') }}" class="mt-4 space-y-3">
    @csrf
    <input type="hidden" name="client_id" value="{{ $client->id }}">
    <input type="hidden" name="items[0][quantity]" value="1">
    <input type="hidden" name="items[0][tax_mode]" value="{{ $defaultTaxMode }}">
    @unless($gstEnabled)
        <input type="hidden" name="items[0][tax_rate]" value="0">
    @endunless

    @if($cases->isNotEmpty())
        <label class="{{ $label }}">
            {{ __('Case') }} <span class="font-normal text-slate-500">({{ __('optional') }})</span>
            <select name="legal_case_id" class="{{ $field }}">
                <option value="">{{ __('Not for one case') }}</option>
                @foreach($cases as $case)
                    <option value="{{ $case->id }}" @selected($loop->first && $cases->count() === 1)>{{ \Illuminate\Support\Str::limit($case->case_title, 50) }}{{ $case->case_number ? ' · '.$case->case_number : '' }}</option>
                @endforeach
            </select>
        </label>
    @endif

    <label class="{{ $label }}">
        {{ __('Work done') }}
        <input type="text" name="items[0][description]" required maxlength="500" autofocus placeholder="{{ __('Appearance on 26 Nov, drafting of reply…') }}" class="{{ $field }}">
    </label>

    <div class="grid grid-cols-2 gap-3">
        <label class="{{ $label }}">
            {{ __('Fee') }} <span class="font-normal text-slate-500">({{ \App\Enums\Currency::current()->value }})</span>
            <input type="number" name="items[0][rate]" required min="0" step="0.01" inputmode="decimal" class="{{ $field }} tabular-nums">
        </label>
        @if($gstEnabled)
            <label class="{{ $label }}">
                {{ __('GST %') }}
                <input type="number" name="items[0][tax_rate]" min="0" max="28" step="0.01" value="{{ $defaultTaxRate + 0 }}" class="{{ $field }} tabular-nums">
            </label>
        @else
            <label class="{{ $label }}">
                {{ __('Date') }}
                <input type="date" name="invoice_date" required value="{{ today()->toDateString() }}" class="{{ $field }}">
            </label>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-3">
        @if($gstEnabled)
            <label class="{{ $label }}">
                {{ __('Date') }}
                <input type="date" name="invoice_date" required value="{{ today()->toDateString() }}" class="{{ $field }}">
            </label>
        @endif
        <label class="{{ $label }} {{ $gstEnabled ? '' : 'col-span-2' }}">
            {{ __('Payment due') }}
            <select name="payment_terms" class="{{ $field }}">
                @foreach($terms as $value => $text)
                    <option value="{{ $value }}" @selected($value === 'net_15')>{{ __($text) }}</option>
                @endforeach
            </select>
        </label>
    </div>

    {{-- Fee already received: the payment is recorded with the invoice, which is marked paid. --}}
    <fieldset class="rounded-xl border border-slate-200 p-3">
        <label class="flex min-h-10 cursor-pointer items-center gap-2.5 text-sm font-semibold text-slate-800">
            <input type="checkbox" name="received_now" value="1" data-toggle-display="invoice-received" class="h-5 w-5 rounded border-slate-300 text-[#16776e] focus:ring-[#16776e]">
            {{ __('Fee already received') }}
        </label>
        <div id="invoice-received" style="display: none" class="mt-2 grid grid-cols-2 gap-3">
            <label class="{{ $label }}">
                {{ __('Paid by') }}
                <select name="received_by" class="{{ $field }}">
                    @foreach(\App\Models\Payment::METHODS as $value => $text)
                        <option value="{{ $value }}">{{ __($text) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="{{ $label }}">
                {{ __('Reference') }}
                <input type="text" name="received_reference" maxlength="255" placeholder="{{ __('UTR, cheque no.') }}" class="{{ $field }}">
            </label>
        </div>
    </fieldset>

    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
        <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}" class="text-sm font-semibold text-[#16776e] hover:underline">{{ __('More lines, discount or GST details') }}</a>
        <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Create invoice') }}</button>
    </div>
</form>
