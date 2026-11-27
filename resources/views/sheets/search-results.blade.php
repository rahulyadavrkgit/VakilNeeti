{{-- Search results as the person types (the phone pop-up and the desktop header): cases, clients, court rooms and
     places (boards, court pages). Each result goes straight to its own page. --}}
@php
    $row = 'flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus-visible:bg-slate-50';
    $places = $results['places'] ?? collect();
    $total = $results['cases']->count() + $results['clients']->count() + $results['rooms']->count() + $places->count();
@endphp
@if(mb_strlen($query) < 2)
    <p class="px-3 py-6 text-center text-sm text-slate-500">Type a case, party, number, CNR, client, judge, room or a court's board.</p>
@elseif($total === 0)
    <p class="px-3 py-6 text-center text-sm text-slate-500">Nothing found for "{{ $query }}".</p>
@else
    @if($results['cases']->isNotEmpty())
        <p class="px-3 pt-2 text-xs font-bold uppercase tracking-wide text-slate-500">Cases</p>
        @foreach($results['cases'] as $case)
            <a href="{{ route('cases.show', $case) }}" data-search-result class="{{ $row }}">
                <span class="min-w-0">
                    <span class="block truncate font-semibold text-slate-900">{{ $case->case_title }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ collect([$case->case_number, $case->court_name])->filter()->implode(' · ') }}</span>
                </span>
                <x-icon name="briefcase" :size="16" class="shrink-0 text-slate-400" />
            </a>
        @endforeach
    @endif
    @if($results['clients']->isNotEmpty())
        <p class="px-3 pt-3 text-xs font-bold uppercase tracking-wide text-slate-500">Clients</p>
        @foreach($results['clients'] as $client)
            <a href="{{ route('clients.show', $client) }}" data-search-result class="{{ $row }}">
                <span class="min-w-0">
                    <span class="block truncate font-semibold text-slate-900">{{ $client->displayName() }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ $client->mobile }}</span>
                </span>
                <x-icon name="people" :size="16" class="shrink-0 text-slate-400" />
            </a>
        @endforeach
    @endif
    @if($results['rooms']->isNotEmpty())
        <p class="px-3 pt-3 text-xs font-bold uppercase tracking-wide text-slate-500">Court rooms</p>
        @foreach($results['rooms'] as $bench)
            <a href="{{ $bench->pageUrl() }}" data-search-result class="{{ $row }}">
                <span class="min-w-0">
                    <span class="block truncate font-semibold text-slate-900">{{ $bench->judgeList()[0]['name'] ?? ($bench->court_room ?: 'Court') }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ collect([$bench->court_room ? (preg_match('/^(court|room)\b/i', $bench->court_room) ? $bench->court_room : 'Room '.$bench->court_room) : null, \App\Models\CourtBench::courts()[$bench->court_key]['court'] ?? null])->filter()->implode(' · ') }}</span>
                </span>
                <x-icon name="gavel" :size="16" class="shrink-0 text-slate-400" />
            </a>
        @endforeach
    @endif
    @if($places->isNotEmpty())
        <p class="px-3 pt-3 text-xs font-bold uppercase tracking-wide text-slate-500">Pages &amp; boards</p>
        @foreach($places as $place)
            <a href="{{ $place['url'] }}" data-search-result class="{{ $row }}">
                <span class="min-w-0">
                    <span class="block truncate font-semibold text-slate-900">{{ $place['title'] }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ $place['detail'] }}</span>
                </span>
                <x-icon :name="$place['icon']" :size="16" class="shrink-0 text-slate-400" />
            </a>
        @endforeach
    @endif
    <a href="{{ route('search', ['q' => $query]) }}" data-search-result class="mt-2 block border-t border-slate-100 px-3 pt-3 text-center text-sm font-semibold text-[#16776e] hover:underline">All results for "{{ $query }}" &rarr;</a>
@endif
