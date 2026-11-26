@props(['link' => null])

{{--
    Join VC and Copy VC link, always side by side. Without a VC link they stay visible but greyed out,
    so the place for them is the same on every hearing.
--}}
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1.5']) }}>
    @if($link)
        <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" data-direct-share
           class="inline-flex min-h-8 items-center gap-1 rounded-full bg-[#16776e] px-3 text-xs font-semibold text-white hover:bg-[#125f58]">
            <x-icon name="screen" :size="13" /> Join VC
        </a>
        <button type="button" data-copy="{{ $link }}" title="Copy VC link"
                class="inline-flex min-h-8 items-center gap-1 rounded-full border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 hover:border-[#16776e] hover:text-[#16776e] [&.is-copied]:text-[#16776e]">
            <x-icon name="copy" :size="13" /><span data-copy-label>Copy VC link</span>
        </button>
    @else
        <span aria-disabled="true" title="No VC link for this court yet" class="inline-flex min-h-8 cursor-not-allowed items-center gap-1 rounded-full bg-slate-100 px-3 text-xs font-semibold text-slate-400">
            <x-icon name="screen" :size="13" /> Join VC
        </span>
        <span aria-disabled="true" title="No VC link for this court yet" class="inline-flex min-h-8 cursor-not-allowed items-center gap-1 rounded-full border border-slate-200 px-3 text-xs font-semibold text-slate-400">
            <x-icon name="copy" :size="13" /> Copy VC link
        </span>
    @endif
</span>
