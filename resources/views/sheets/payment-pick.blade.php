{{-- Pop-up: which unpaid invoice the payment is for; picking one opens the payment form (sheets.payment). --}}
<h2 data-sheet-heading class="text-lg font-semibold text-slate-900">{{ __('Record payment') }}</h2>
<p class="mt-0.5 text-sm text-slate-600">{{ __('Choose the invoice this payment is for.') }}</p>

@if($invoices->isEmpty())
    <p class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">{{ __('No unpaid invoices.') }}</p>
@else
    <label class="mt-4 flex items-center gap-2 rounded-xl border border-slate-300 px-3 focus-within:border-[#16776e]">
        <x-icon name="search" :size="16" class="text-slate-400" />
        <span class="sr-only">{{ __('Find an invoice') }}</span>
        <input type="search" data-filter-list="payment-pick-list" placeholder="{{ __('Invoice number or client') }}" class="min-h-10 w-full border-0 bg-transparent px-0 text-base outline-none focus:ring-0 sm:text-sm">
    </label>
    <div id="payment-pick-list" class="mt-2 max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200">
        @foreach($invoices as $invoice)
            <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" data-sheet="{{ route('sheets.payment', $invoice) }}" data-filter-item
               class="flex min-h-14 items-center gap-3 px-4 py-2.5 text-sm hover:bg-teal-50">
                <span class="min-w-0 flex-1">
                    <span class="block font-semibold text-slate-900">{{ $invoice->invoice_number }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ $invoice->client?->displayName() ?? __('No client') }} · {{ $invoice->invoice_date?->format('d M Y') }}</span>
                </span>
                <span class="shrink-0 text-right">
                    <span class="block font-semibold tabular-nums text-slate-900">{{ number_format($invoice->balanceDue(), 2) }}</span>
                    <span class="block text-xs text-slate-500">{{ __('due') }}</span>
                </span>
            </a>
        @endforeach
        <p data-filter-empty hidden class="px-4 py-4 text-center text-sm text-slate-500">{{ __('No invoice matches.') }}</p>
    </div>
@endif

<p class="mt-4 text-sm">
    <a href="{{ route('payments.create', array_filter(['client_id' => $clientId])) }}" class="font-semibold text-[#16776e] hover:underline">{{ __('Record a payment without an invoice') }}</a>
</p>
