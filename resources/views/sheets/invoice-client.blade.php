{{-- Pop-up: new invoice, step 1 of 2. Picking a client opens step 2 (sheets.invoice with the client). --}}
<p class="text-xs font-bold uppercase tracking-wide text-[#16776e]">{{ __('New invoice · step 1 of 2') }}</p>
<h2 data-sheet-heading class="mt-1 text-lg font-semibold text-slate-900">{{ __('Who is the invoice for?') }}</h2>

@if($clients->isEmpty())
    <div class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
        <p>{{ __('Add your client first; the invoice is made out to them.') }}</p>
        <a href="{{ route('clients.create') }}" data-sheet="{{ route('sheets.client') }}" class="mt-3 inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 font-semibold text-white hover:bg-[#125f58]">{{ __('+ Add a client') }}</a>
    </div>
@else
    <label class="mt-4 flex items-center gap-2 rounded-xl border border-slate-300 px-3 focus-within:border-[#16776e]">
        <x-icon name="search" :size="16" class="text-slate-400" />
        <span class="sr-only">{{ __('Find a client') }}</span>
        <input type="search" data-filter-list="invoice-client-list" autofocus placeholder="{{ __('Client name or mobile') }}" class="min-h-10 w-full border-0 bg-transparent px-0 text-base outline-none focus:ring-0 sm:text-sm">
    </label>
    <div id="invoice-client-list" class="mt-2 max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200">
        @foreach($clients as $client)
            <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}" data-sheet="{{ route('sheets.invoice', ['client' => $client->id]) }}" data-filter-item
               class="flex min-h-12 items-center gap-3 px-4 py-2.5 text-sm hover:bg-teal-50">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-bold text-[#16776e]">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($client->displayName(), 0, 1)) }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-semibold text-slate-900">{{ $client->displayName() }}</span>
                    @if($client->mobile)<span class="block text-xs text-slate-500">{{ $client->mobile }}</span>@endif
                </span>
                <x-icon name="chevron" :size="16" class="-rotate-90 text-slate-400" />
            </a>
        @endforeach
        <p data-filter-empty hidden class="px-4 py-4 text-center text-sm text-slate-500">{{ __('No client matches.') }}</p>
    </div>
    <p class="mt-3 text-sm"><a href="{{ route('clients.create') }}" data-sheet="{{ route('sheets.client') }}" class="font-semibold text-[#16776e] hover:underline">{{ __('+ New client') }}</a></p>
@endif
