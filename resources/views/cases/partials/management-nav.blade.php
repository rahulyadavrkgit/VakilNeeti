{{-- Cases section tabs: the menu has one "Cases" entry, and these lead to its pages. --}}
@php
    $caseTabs = [
        ['My cases', route('cases.index'), request()->routeIs('cases.index', 'cases.show', 'cases.create', 'cases.edit', 'cases.manual')],
        ['Overview', route('case-dashboard.index'), request()->routeIs('case-dashboard.*')],
        ['Hearing history', route('hearings.history'), request()->routeIs('hearings.history')],
        ['Find & import', route('cases.find'), request()->routeIs('cases.find*', 'cases.import*')],
    ];
@endphp
<nav aria-label="Cases" class="mb-4 -mx-1 flex gap-1.5 overflow-x-auto px-1">
    @foreach($caseTabs as [$label, $url, $current])
        <a href="{{ $url }}" @if($current) aria-current="page" @endif
           class="inline-flex min-h-10 shrink-0 items-center whitespace-nowrap rounded-full border px-4 text-sm font-semibold {{ $current ? 'border-[#16776e] bg-[#16776e] text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-[#16776e] hover:text-[#16776e]' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
