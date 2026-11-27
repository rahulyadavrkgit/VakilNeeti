@extends('layouts.app')

@section('title', 'Delhi District Courts')
@section('page-heading', 'Delhi District Courts')
@section('meta_description', 'Delhi District Courts by court complex: Rohini, Karkardooma, Tis Hazari, Dwarka, Saket, Patiala House and Rouse Avenue. Judges list, judges on leave, bail roster, duty magistrate, case status and cause list for every district.')

@php
    $tile = 'flex min-h-20 flex-col items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-2.5 text-center text-sm font-semibold text-slate-800 transition hover:border-teal-400 hover:text-[#16776e]';
    $tileIcon = 'flex size-9 items-center justify-center rounded-lg bg-teal-50 text-[#16776e]';
    $complexCourts = fn (array $districts): int => collect($districts)->sum(fn ($district) => $stats['delhi-district-'.\Illuminate\Support\Str::slug($district['district'])]['courts'] ?? 0);
@endphp

@section('content')
{{-- Pick a complex (remembered on this device); its districts with their services, and your matters there this week. --}}
<div class="mx-auto max-w-6xl space-y-4">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Delhi District Courts</h1>
        <p class="mt-0.5 text-sm text-slate-600">{{ $totals['courts'] }} courts in {{ $totals['districts'] }} districts · pick a complex</p>
    </div>

    <form method="GET" action="{{ route('courts.delhi-district-courts') }}" role="search">
        <label class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 focus-within:border-[#16776e]">
            <x-icon name="search" class="text-slate-400" />
            <span class="sr-only">Search by judge's name or room number</span>
            <input type="search" name="q" value="{{ $query }}" placeholder="Judge or room, e.g. Akash Jain or 612" class="min-h-12 w-full border-0 bg-transparent px-0 text-base focus:ring-0">
        </label>
    </form>

    @if($query !== '')
        <section aria-labelledby="search-results-heading" class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between gap-2">
                <h2 id="search-results-heading" class="text-sm font-bold text-slate-900">{{ $results->count() }} {{ \Illuminate\Support\Str::plural('court', $results->count()) }} for "{{ $query }}"</h2>
                <a href="{{ route('courts.delhi-district-courts') }}" class="text-sm font-semibold text-[#16776e]">Clear</a>
            </div>
            <ul class="mt-2 divide-y divide-slate-100">
                @forelse($results as $bench)
                    @php $judge = $bench->judgeList()[0] ?? ['name' => null, 'designation' => null]; @endphp
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2.5">
                        <a href="{{ $bench->pageUrl() }}" class="min-w-0 hover:text-[#16776e]">
                            <span class="block font-semibold text-slate-900">{{ $judge['name'] }}</span>
                            <span class="block text-sm text-slate-600">{{ collect([$judge['designation'], $bench->court_room ? 'Room '.$bench->court_room : null, $districtNames[$bench->court_key] ?? null])->filter()->implode(' · ') }}</span>
                        </a>
                        @if($bench->vc_link)
                            <a href="{{ $bench->vc_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center gap-1.5 rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">
                                <x-icon name="screen" :size="16" /> Join VC
                            </a>
                        @endif
                    </li>
                @empty
                    <li class="py-4 text-sm text-slate-500">No judge or room matches. Try a surname or just the room number.</li>
                @endforelse
            </ul>
        </section>
    @endif

    <nav aria-label="Court complexes" class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1" data-complex-picker>
        @foreach($complexes as $complex => $districts)
            @php $slug = \Illuminate\Support\Str::slug($complex); @endphp
            <button type="button" data-complex-pick="{{ $slug }}" aria-pressed="{{ $slug === $selectedComplex ? 'true' : 'false' }}" aria-controls="complex-{{ $slug }}"
                    class="inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-full border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 aria-pressed:border-[#142235] aria-pressed:bg-[#142235] aria-pressed:text-white">
                {{ $complex }}
                <span class="text-xs font-medium tabular-nums opacity-60">{{ $complexCourts($districts) }}</span>
                @if(($matters[$slug] ?? collect())->isNotEmpty())
                    <span class="size-2 rounded-full bg-[#16776e]" title="Your matters this week" aria-label="Your matters this week"></span>
                @endif
            </button>
        @endforeach
    </nav>

    @foreach($complexes as $complex => $districts)
        @php
            $complexSlug = \Illuminate\Support\Str::slug($complex);
            $here = $matters[$complexSlug] ?? collect();
        @endphp
        <section id="complex-{{ $complexSlug }}" data-complex-panel="{{ $complexSlug }}" @if($complexSlug !== $selectedComplex) hidden @endif
                 class="grid items-start gap-3 lg:grid-cols-[minmax(0,1fr)_19rem]" aria-label="{{ $complex }}">
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($districts as $district)
                    @php
                        $slug = \Illuminate\Support\Str::slug($district['district']);
                        $districtStats = $stats['delhi-district-'.$slug] ?? ['courts' => 0, 'vacant' => 0];
                    @endphp
                    <article class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex items-baseline justify-between gap-2">
                            <h2 class="text-lg font-bold text-slate-900">{{ $district['district'] }}</h2>
                            <span class="text-xs text-slate-500">{{ $complex }}</span>
                        </div>
                        <p class="mt-0.5 text-sm text-slate-600"><strong class="tabular-nums text-slate-900">{{ $districtStats['courts'] }}</strong> courts @if($districtStats['vacant'])· <strong class="tabular-nums text-slate-900">{{ $districtStats['vacant'] }}</strong> vacant @endif</p>

                        <a href="{{ route('courts.delhi-district-courts.judges', $slug) }}"
                           class="mt-3 flex min-h-14 items-center gap-3 rounded-xl border border-teal-300 bg-teal-50 px-3 py-2 font-semibold text-teal-900 hover:border-teal-500">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-[#16776e] text-white"><x-icon name="people" :size="18" /></span>
                            <span class="min-w-0">Judges, rooms &amp; VC links<span class="block text-xs font-medium text-teal-800/80">Search a judge or room</span></span>
                        </a>

                        <div class="mt-2 grid grid-cols-3 gap-2">
                            <a href="{{ $district['url'].$pages['Judges on leave'] }}" target="_blank" rel="noopener noreferrer" class="{{ $tile }}"><span class="{{ $tileIcon }}"><x-icon name="calendar" :size="18" /></span>On leave</a>
                            <a href="{{ route('courts.delhi-district-courts.document', [$slug, 'bail-roster']) }}" class="{{ $tile }}"><span class="{{ $tileIcon }}"><x-icon name="scale" :size="18" /></span>Bail roster</a>
                            <a href="{{ route('courts.delhi-district-courts.document', [$slug, 'duty-magistrate']) }}" class="{{ $tile }}"><span class="{{ $tileIcon }}"><x-icon name="shield" :size="18" /></span>Duty magistrate</a>
                            <a href="{{ $district['url'].$pages['Case status / Order'] }}" target="_blank" rel="noopener noreferrer" class="{{ $tile }}"><span class="{{ $tileIcon }}"><x-icon name="search" :size="18" /></span>Case status</a>
                            <a href="{{ $district['url'].$pages['Cause list'] }}" target="_blank" rel="noopener noreferrer" class="{{ $tile }}"><span class="{{ $tileIcon }}"><x-icon name="list" :size="18" /></span>Cause list</a>
                            <a href="{{ $services['efiling'] }}" target="_blank" rel="noopener noreferrer" class="{{ $tile }}"><span class="{{ $tileIcon }}"><x-icon name="upload" :size="18" /></span>e-Filing</a>
                        </div>
                    </article>
                @endforeach
            </div>

            @auth
                <aside class="rounded-xl border border-slate-200 bg-white p-4 max-lg:order-first" aria-label="Your matters in {{ $complex }}">
                    <h2 class="font-bold text-slate-900">Your matters in {{ $complex }}</h2>
                    <p class="text-xs text-slate-500">Next 7 days</p>
                    @forelse($here as $event)
                        @php
                            $day = \Illuminate\Support\Carbon::parse($event['date']);
                            $facts = $event['facts'];
                        @endphp
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <a href="{{ $event['url'] }}" class="block text-sm font-semibold text-slate-900 hover:text-[#16776e]">{{ $event['title'] }}</a>
                            <div class="mt-1.5 flex flex-wrap gap-1.5 text-xs font-semibold">
                                <span class="rounded-full px-2 py-0.5 {{ $day->isToday() ? 'bg-red-600 text-white' : 'bg-teal-50 text-teal-800' }}">{{ $day->isToday() ? 'Today' : ($day->isTomorrow() ? 'Tomorrow' : $day->format('D, d M')) }}</span>
                                @if(filled($facts['room']))<span class="rounded-full bg-[#142235] px-2 py-0.5 text-white">Room {{ preg_replace('/^(court|room)\s*(no\.?)?\s*/i', '', $facts['room']) }}</span>@endif
                                @if(filled($facts['judge']))<span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-amber-900">{{ $facts['judge'] }}</span>@endif
                            </div>
                            <x-vc-buttons :link="$facts['vc_link']" class="mt-2" />
                        </div>
                    @empty
                        <p class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-500">No hearings here in the next 7 days.</p>
                    @endforelse
                </aside>
            @endauth
        </section>
    @endforeach

    <p class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
        <span>Court counts from each district's judges &amp; VC list.</span>
        <a href="{{ $services['virtual_court'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#16776e] hover:underline">Virtual courts</a>
        <a href="{{ $services['evening_court'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#16776e] hover:underline">Evening courts</a>
        <a href="{{ $services['statistics'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#16776e] hover:underline">Pending cases (NJDG)</a>
    </p>
</div>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        // One complex at a time; the choice is kept on this device and in the address (so Back returns to it).
        const buttons = [...document.querySelectorAll('[data-complex-pick]')];
        const panels = [...document.querySelectorAll('[data-complex-panel]')];
        const show = (slug, remember) => {
            if (!panels.some((panel) => panel.dataset.complexPanel === slug)) {
                return;
            }
            buttons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.complexPick === slug)));
            panels.forEach((panel) => { panel.hidden = panel.dataset.complexPanel !== slug; });
            const url = new URL(window.location.href);
            url.searchParams.set('complex', slug);
            window.history.replaceState(window.history.state, '', url);
            if (remember) {
                try { localStorage.setItem('vn-district-complex', slug); } catch (error) {}
            }
        };
        buttons.forEach((button) => button.addEventListener('click', () => {
            show(button.dataset.complexPick, true);
            button.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }));
        if (!new URL(window.location.href).searchParams.get('complex')) {
            try {
                const saved = localStorage.getItem('vn-district-complex');
                if (saved) {
                    show(saved, false);
                }
            } catch (error) {}
        }
        buttons.find((button) => button.getAttribute('aria-pressed') === 'true')?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    })();
</script>
@endsection
