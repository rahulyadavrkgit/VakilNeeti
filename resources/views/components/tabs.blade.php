@props([
    'tabs',
    'label',
    'counts' => [],
])

{{--
    Tabs for a page with several sections. Each key is the id of a panel on the page marked
    data-tab-panel; resources/js/ui-actions.js shows one panel at a time and remembers the
    choice in the address (#id), so a link, a reload or a form redirect opens the same tab.
    Without JavaScript the tabs work as links to the sections.
--}}
<div data-tabs role="tablist" aria-label="{{ $label }}"
     {{ $attributes->merge(['class' => 'vn-tabs sticky top-[var(--vn-header-height)] z-20 -mx-1 flex gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white/95 p-1.5 shadow-sm backdrop-blur print:hidden']) }}>
    @foreach($tabs as $panelId => $tabLabel)
        <a href="#{{ $panelId }}" id="tab-{{ $panelId }}" role="tab" data-tab="{{ $panelId }}" aria-controls="{{ $panelId }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
           class="group inline-flex min-h-10 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg px-3.5 text-sm font-semibold text-[#475467] transition hover:bg-slate-100 hover:text-[#142235] aria-selected:bg-[#16776e] aria-selected:text-white! aria-selected:shadow-sm">
            {{ $tabLabel }}
            @if(($counts[$panelId] ?? 0) > 0)
                <span class="rounded-full bg-slate-100 px-1.5 text-xs font-bold leading-5 text-[#475467] group-aria-selected:bg-white/20 group-aria-selected:text-white">{{ $counts[$panelId] }}</span>
            @endif
        </a>
    @endforeach
</div>
