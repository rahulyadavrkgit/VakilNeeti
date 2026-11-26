@props([
    'tabs',
    'label',
    'counts' => [],
    'compact' => false,
])

{{--
    Tabs for a page with several sections. Each key is the id of a panel on the page marked
    data-tab-panel; resources/js/ui-actions.js shows one panel at a time and remembers the
    choice in the address (#id), so a link, a reload or a form redirect opens the same tab.
    Without JavaScript the tabs work as links to the sections.
--}}
@php
    $tabList = $compact
        ? 'rounded-xl border border-slate-200 bg-white/95 px-1.5 [scrollbar-width:none]'
        : '-mx-1 gap-1 rounded-xl border border-slate-200 bg-white/95 p-1.5 shadow-sm';
    $tabStyle = $compact
        ? 'min-h-11 border-b-2 border-transparent px-3 aria-selected:border-[#16776e] aria-selected:font-bold aria-selected:text-slate-900'
        : 'min-h-10 rounded-lg px-3.5 hover:bg-slate-100 hover:text-[#142235] aria-selected:bg-[#16776e] aria-selected:text-white! aria-selected:shadow-sm';
@endphp
<div data-tabs role="tablist" aria-label="{{ $label }}"
     {{ $attributes->merge(['class' => 'vn-tabs sticky top-[var(--vn-header-height)] z-20 flex overflow-x-auto backdrop-blur print:hidden '.$tabList]) }}>
    @foreach($tabs as $panelId => $tabLabel)
        <a href="#{{ $panelId }}" id="tab-{{ $panelId }}" role="tab" data-tab="{{ $panelId }}" aria-controls="{{ $panelId }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
           class="group inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap text-sm font-semibold text-[#475467] transition {{ $tabStyle }}">
            {{ $tabLabel }}
            @if(($counts[$panelId] ?? 0) > 0)
                <span class="rounded-full bg-slate-100 px-1.5 text-xs font-bold leading-5 text-[#475467] {{ $compact ? 'group-aria-selected:bg-teal-50 group-aria-selected:text-teal-800' : 'group-aria-selected:bg-white/20 group-aria-selected:text-white' }}">{{ $counts[$panelId] }}</span>
            @endif
        </a>
    @endforeach
</div>
