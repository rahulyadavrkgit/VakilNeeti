{{-- Pop-up: a client's contact card, with a ready hearing update for each open case. --}}
@php
    $button = 'inline-flex min-h-11 flex-1 items-center justify-center gap-1.5 rounded-xl px-4 text-sm font-semibold';
@endphp
<h2 data-sheet-heading class="text-lg font-semibold text-slate-900">{{ $client->displayName() }}</h2>
<p class="mt-0.5 text-sm text-slate-600">{{ collect([$client->mobile, $client->email])->filter()->implode(' · ') ?: 'No phone or email saved' }}</p>

<div class="mt-4 flex flex-wrap gap-2">
    @if($client->mobile)
        <a href="tel:{{ $client->mobile }}" class="{{ $button }} border border-slate-300 text-slate-800 hover:border-[#16776e]"><x-icon name="phone" :size="16" /> Call</a>
    @endif
    @if($client->whatsappNumber())
        <a href="https://wa.me/{{ $client->whatsappNumber() }}" target="_blank" rel="noopener noreferrer" data-direct-share class="{{ $button }} bg-[#25d366] text-white">WhatsApp</a>
    @endif
    @if($client->email)
        <a href="mailto:{{ $client->email }}" class="{{ $button }} border border-slate-300 text-slate-800 hover:border-[#16776e]"><x-icon name="mail" :size="16" /> Email</a>
    @endif
</div>

@if($cases->isNotEmpty())
    <h3 class="mt-5 text-sm font-bold uppercase tracking-wide text-slate-500">Send hearing update</h3>
    <ul class="mt-2 space-y-2">
        @foreach($cases as $case)
            @php $message = $case->clientUpdateMessage($client->displayName(), $advocateName); @endphp
            <li class="rounded-xl border border-slate-200 p-3">
                <p class="font-semibold text-slate-900">{{ $case->case_title }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @if($client->whatsappNumber())
                        <a href="https://wa.me/{{ $client->whatsappNumber() }}?text={{ rawurlencode($message) }}" target="_blank" rel="noopener noreferrer" data-direct-share
                           class="inline-flex min-h-9 items-center rounded-full bg-[#25d366] px-3 text-sm font-semibold text-white">WhatsApp update</a>
                    @endif
                    <button type="button" data-copy="{{ $message }}" class="inline-flex min-h-9 items-center gap-1 rounded-full border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:border-[#16776e]">
                        <x-icon name="copy" :size="14" /><span data-copy-label>Copy</span>
                    </button>
                </div>
            </li>
        @endforeach
    </ul>
@endif

<a href="{{ route('clients.show', $client) }}" class="mt-5 flex min-h-11 items-center justify-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Open client</a>
