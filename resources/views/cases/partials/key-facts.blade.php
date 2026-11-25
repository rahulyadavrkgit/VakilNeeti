{{-- The facts needed at court, large and at the top: hearing, court, room, VC link and the client's phone. --}}
@php
    $fact = 'min-w-0 rounded-xl bg-slate-50 px-3.5 py-3';
    $factLabel = 'text-xs font-bold uppercase tracking-wide text-slate-500';
    $contactButton = 'inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg px-3 text-sm font-semibold';
@endphp

<details class="mt-4 space-y-2"><summary class="cursor-pointer text-sm font-semibold underline">Court position and client contact</summary><div class="grid gap-2 sm:grid-cols-2">
    {{-- Where we stand: our side plus the court's practice, recorded once per court. --}}
    <div class="{{ $standSide ? 'min-w-0 rounded-xl bg-[#142235] px-3.5 py-3 text-white' : $fact }} col-span-2 lg:col-span-1">
        @if(! $case->appearing_for)
            <p class="{{ $factLabel }}">Where you stand</p>
            <a href="{{ route('cases.edit', $case) }}" class="mt-1.5 inline-flex min-h-9 items-center gap-1 rounded-lg border border-dashed border-amber-400 px-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-50 print:hidden">
                <x-icon name="plus" :size="15" /> Set your side first
            </a>
        @elseif($standSide)
            <p class="text-xs font-bold uppercase tracking-wide text-[#e6b84a]">Stand · {{ $case->appearingForLabel() }}</p>
            <p class="mt-0.5 text-2xl font-extrabold leading-tight">YOUR {{ strtoupper($standSide['label']) }}</p>
            <p class="text-xs text-white/75">As you face the judge (the judge's {{ $standSide['side'] === 'left' ? 'right' : 'left' }})</p>
            <details class="mt-1 text-xs text-white/75 print:hidden">
                <summary class="cursor-pointer list-none underline">Here the petitioner's counsel stands on your {{ $standSide['petitioner_side'] }} · change</summary>
                @include('cases.partials.stand-side-buttons')
            </details>
        @else
            <p class="{{ $factLabel }}">Facing the judge, where does the petitioner's counsel stand in this court?</p>
            @include('cases.partials.stand-side-buttons')
        @endif
    </div>
</div>

@if($linkedClients->isNotEmpty())
    <div class="mt-3 space-y-2 print:hidden">
        @foreach($linkedClients->take(3) as $party)
            @php
                $contactClient = $party->client;
                $contactNumber = $contactClient->whatsappNumber();
            @endphp
            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Client</p>
                    <a href="{{ route('clients.show', $contactClient) }}" class="block truncate font-semibold text-slate-900 hover:text-[#16776e]">{{ $contactClient->displayName() }}</a>
                    <p class="truncate text-xs text-slate-500">{{ $contactClient->mobile ?: 'No mobile number saved' }}</p>
                </div>
                @if($contactNumber)
                    <a href="tel:+{{ $contactNumber }}" data-direct-share aria-label="Call {{ $contactClient->displayName() }}"
                       class="{{ $contactButton }} bg-[#16776e] text-white hover:bg-[#125f58]">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.8 2z"/></svg>
                        Call
                    </a>
                    <a href="https://wa.me/{{ $contactNumber }}?text={{ rawurlencode($case->clientUpdateMessage($contactClient->displayName(), $advocateName)) }}" target="_blank" rel="noopener noreferrer" data-direct-share
                       aria-label="WhatsApp {{ $contactClient->displayName() }}" class="{{ $contactButton }} bg-[#25d366] text-white hover:bg-[#1ebe5b]">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.2-.2-.5-.3Z"/></svg>
                        WhatsApp
                    </a>
                @else
                    <a href="{{ route('clients.edit', $contactClient) }}" class="{{ $contactButton }} border border-dashed border-slate-300 text-slate-600 hover:bg-slate-50">
                        <x-icon name="plus" :size="16" /> Add mobile number
                    </a>
                @endif
            </div>
        @endforeach
    </div>
@else
    <a href="#case-clients" data-tab-link="case-clients" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-[#16776e] hover:underline print:hidden">
        <x-icon name="people" :size="16" /> Link a client to call or WhatsApp them from here
    </a>
@endif

</details>
