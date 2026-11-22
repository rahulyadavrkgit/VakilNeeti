@props(['target', 'placeholder' => 'Search', 'label' => null])

{{-- Search plus grid/list switch for a collection of similar items (#target with [data-filter-item] children). --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    @if($label)
        <h2 class="mr-auto text-lg font-semibold text-slate-900">{{ $label }}</h2>
    @endif
    <label class="flex min-w-0 flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 shadow-sm focus-within:border-[#16776e] sm:max-w-xs">
        <x-icon name="search" :size="16" class="text-slate-400" />
        <span class="sr-only">{{ $placeholder }}</span>
        <input type="search" data-filter-list="{{ $target }}" placeholder="{{ $placeholder }}" class="min-h-11 w-full border-0 bg-transparent px-0 text-sm focus:ring-0">
    </label>
    <div class="inline-flex overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" role="group" aria-label="Layout">
        <button type="button" data-view-toggle="{{ $target }}" data-view="grid" aria-pressed="true" aria-label="Grid view"
                class="flex h-11 w-11 items-center justify-center text-slate-500 aria-pressed:bg-teal-50 aria-pressed:text-[#16776e]">
            <x-icon name="dashboard" :size="18" />
        </button>
        <button type="button" data-view-toggle="{{ $target }}" data-view="list" aria-pressed="false" aria-label="List view"
                class="flex h-11 w-11 items-center justify-center border-l border-slate-200 text-slate-500 aria-pressed:bg-teal-50 aria-pressed:text-[#16776e]">
            <x-icon name="list" :size="18" />
        </button>
    </div>
</div>
