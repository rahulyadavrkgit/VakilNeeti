@props(['link' => null])

@php
    $copyClass = 'inline-flex size-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1.5']) }}>
    @if($link)
        <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" data-direct-share
           class="inline-flex min-h-8 items-center gap-1 rounded-full bg-[#16776e] px-3 text-xs font-semibold text-white hover:bg-[#125f58]">
            <x-icon name="screen" :size="13" /> Join VC
        </a>
        <button type="button" data-copy="{{ $link }}" title="Copy VC link" aria-label="Copy VC link"
                class="{{ $copyClass }} hover:border-teal-500 hover:text-teal-800 [&.is-copied]:border-teal-500 [&.is-copied]:bg-teal-50 [&.is-copied]:text-teal-800">
            <x-icon name="copy" :size="14" /><span data-copy-label class="sr-only" aria-live="polite">Copy VC link</span>
        </button>
    @else
        <span aria-disabled="true" title="No VC link for this court yet" class="inline-flex min-h-8 cursor-not-allowed items-center gap-1 rounded-full bg-slate-100 px-3 text-xs font-semibold text-slate-400">
            <x-icon name="screen" :size="13" /> Join VC
        </span>
        <button type="button" disabled aria-label="Copy VC link" title="No VC link for this court yet" class="{{ $copyClass }} cursor-not-allowed opacity-50">
            <x-icon name="copy" :size="14" />
        </button>
    @endif
</span>
