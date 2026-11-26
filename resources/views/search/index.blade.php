@extends('layouts.app')

@section('title', 'Search')
@section('page-heading', 'Search')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <form method="GET" action="{{ route('search') }}" role="search" class="flex gap-2">
        <label class="flex flex-1 items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 shadow-sm focus-within:border-[#16776e] focus-within:ring-2 focus-within:ring-[#16776e]/15">
            <x-icon name="search" class="text-slate-400" />
            <span class="sr-only">Search cases, courts, judges, clients and invoices</span>
            <input type="search" name="q" value="{{ $query }}" autofocus maxlength="100" placeholder="Case, CNR, party, court, judge, room, client or invoice"
                   class="min-h-12 w-full border-0 bg-transparent px-0 outline-none focus:ring-0">
        </label>
        <button type="submit" class="rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">Search</button>
    </form>

    @if(mb_strlen($query) < 2)
        <p class="text-sm text-slate-500">Type at least 2 letters. Search finds your cases by title, number, CNR, party, court, judge or court room, the court rooms (judge or room number), your clients and invoices.</p>
    @elseif($total === 0)
        <p class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">Nothing matches "{{ $query }}". Try a case number, CNR, party, judge's name, room number, client name or mobile number.</p>
    @else
        @php $courts = \App\Models\CourtBench::courts(); @endphp
        @foreach([
            'cases' => ['Cases', 'briefcase'],
            'court_rooms' => ['Court rooms', 'gavel'],
            'clients' => ['Clients', 'people'],
            'invoices' => ['Invoices', 'receipt'],
        ] as $key => [$label, $icon])
            @if($results[$key]->isNotEmpty())
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <h2 class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 text-sm font-bold uppercase tracking-wide text-slate-500">
                        <x-icon :name="$icon" :size="16" /> {{ $label }} <span class="font-normal">({{ $results[$key]->count() }})</span>
                    </h2>
                    <ul class="divide-y divide-slate-100">
                        @foreach($results[$key] as $item)
                            <li>
                                @if($key === 'cases')
                                    <div class="px-4 py-3 hover:bg-slate-50">
                                        <a href="{{ route('cases.show', $item) }}" data-sheet="{{ route('sheets.case-view', $item) }}" class="flex flex-wrap items-center gap-1.5 font-semibold text-slate-900 hover:text-[#16776e]">
                                            {{ $item->case_title }}
                                            <x-case-side :side="$item->appearing_for" :parties="$item->appearing_for_parties" />
                                        </a>
                                        <span class="block text-xs text-slate-500">{{ collect([$item->case_number, $item->cnr_number])->filter()->implode(' · ') }}</span>
                                        <x-court-info class="mt-1" :court="$item->court_name" :complex="$item->court_complex" :room="$item->court_room" :judge="$item->judge_name" :vc-link="$item->vc_link" />
                                    </div>
                                @elseif($key === 'court_rooms')
                                    @php $courtList = $courts[$item->court_key] ?? null; @endphp
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 px-4 py-3">
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-semibold text-slate-900">{{ collect($item->judgeList())->pluck('name')->implode(', ') ?: 'Court '.$item->court_room }}</span>
                                            <span class="block text-xs text-slate-500">{{ collect([collect($item->judgeList())->pluck('designation')->filter()->first(), $item->court_room ? 'Room '.$item->court_room : null, $courtList['court'] ?? null])->filter()->implode(' · ') }}</span>
                                        </span>
                                        @if($item->vc_link)
                                            <a href="{{ $item->vc_link }}" target="_blank" rel="noopener noreferrer" data-direct-share class="inline-flex min-h-9 items-center gap-1 rounded-lg bg-[#16776e] px-3 text-xs font-semibold text-white hover:bg-[#125f58]"><x-icon name="screen" :size="14" /> Join VC</a>
                                        @endif
                                        @if($courtList)
                                            <a href="{{ \Illuminate\Support\Str::startsWith($item->court_key, 'delhi-district-') ? route('courts.delhi-district-courts.judges', \Illuminate\Support\Str::after($item->court_key, 'delhi-district-')) : ($item->court_key === 'supreme-court' ? route('courts.supreme-court.court-list') : route('courts.delhi-high-court.judges')) }}"
                                               class="text-xs font-semibold text-[#16776e] hover:underline">{{ $courtList['list'] }} &rarr;</a>
                                        @endif
                                    </div>
                                @elseif($key === 'clients')
                                    <a href="{{ route('clients.show', $item) }}" class="block px-4 py-3 hover:bg-slate-50">
                                        <span class="block font-semibold text-slate-900">{{ $item->displayName() }}</span>
                                        <span class="block text-xs text-slate-500">{{ collect([$item->mobile, $item->email])->filter()->implode(' · ') }}</span>
                                    </a>
                                @else
                                    <a href="{{ route('invoices.show', $item) }}" class="block px-4 py-3 hover:bg-slate-50">
                                        <span class="block font-semibold text-slate-900">{{ $item->invoice_number }}</span>
                                        <span class="block text-xs text-slate-500">{{ collect([$item->client?->displayName(), ucfirst(str_replace('_', ' ', (string) $item->status))])->filter()->implode(' · ') }}</span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach
    @endif
</div>
@endsection
