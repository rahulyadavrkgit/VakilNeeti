@props(['href', 'icon', 'title', 'description' => null, 'badge' => null, 'external' => false])

<a href="{{ $href }}" data-filter-item
   @if($external) target="_blank" rel="noopener noreferrer" data-direct-share @endif
   {{ $attributes->merge(['class' => 'vn-tile group relative flex flex-col items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-[#8fc9c0] hover:shadow-md focus-visible:outline-offset-2']) }}>
    @if($badge)
        <span data-tile-badge class="absolute right-2.5 top-2.5 rounded-full bg-[#16776e] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">{{ $badge }}</span>
    @endif
    <span data-tile-icon class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-teal-50 text-[#16776e] transition group-hover:bg-[#16776e] group-hover:text-white">
        <x-icon :name="$icon" :size="26" />
    </span>
    <span data-tile-text class="flex min-w-0 flex-col gap-1">
        <span class="text-sm font-bold leading-snug text-slate-900 sm:text-base">{{ $title }}</span>
        @if($description)
            <span data-tile-description class="hidden text-xs leading-snug text-slate-500 sm:block">{{ $description }}</span>
        @endif
    </span>
</a>
