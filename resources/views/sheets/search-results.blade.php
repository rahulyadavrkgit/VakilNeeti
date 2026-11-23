{{-- Search pop-up results (filled as the person types): cases, clients and court rooms. --}}
@php
    $row = 'flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus-visible:bg-slate-50';
    $total = $results['cases']->count() + $results['clients']->count() + $results['rooms']->count();
@endphp
@if(mb_strlen($query) < 2)
    <p class="px-3 py-6 text-center text-sm text-slate-500">Type a case, party, number, CNR, client, judge or room.</p>
@elseif($total === 0)
    <p class="px-3 py-6 text-center text-sm text-slate-500">Nothing found for "{{ $query }}".</p>
@else
    @if($results['cases']->isNotEmpty())
        <p class="px-3 pt-2 text-xs font-bold uppercase tracking-wide text-slate-500">Cases</p>
        @foreach($results['cases'] as $case)
            <a href="{{ route('cases.show', $case) }}" data-sheet="{{ route('sheets.case-view', $case) }}" class="{{ $row }}">
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
            <a href="{{ route('clients.show', $client) }}" data-sheet="{{ route('sheets.client-card', $client) }}" class="{{ $row }}">
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
            <a href="{{ route('sheets.court', ['bench' => $bench->id]) }}" data-sheet="{{ route('sheets.court', ['bench' => $bench->id]) }}" class="{{ $row }}">
                <span class="min-w-0">
                    <span class="block truncate font-semibold text-slate-900">{{ $bench->judgeList()[0]['name'] ?? 'Court' }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ collect([$bench->court_room ? 'Room '.$bench->court_room : null, \App\Models\CourtBench::courts()[$bench->court_key]['court'] ?? null])->filter()->implode(' · ') }}</span>
                </span>
                <x-icon name="gavel" :size="16" class="shrink-0 text-slate-400" />
            </a>
        @endforeach
    @endif
    <a href="{{ route('search', ['q' => $query]) }}" class="mt-2 block border-t border-slate-100 px-3 pt-3 text-center text-sm font-semibold text-[#16776e] hover:underline">All results for "{{ $query }}" &rarr;</a>
@endif
