@extends('layouts.app')

@section('title', 'Delhi District Courts')
@section('page-heading', 'Delhi District Courts')
@section('meta_description', 'Delhi District Courts by court complex: Rohini, Karkardooma, Tis Hazari, Dwarka, Saket, Patiala House and Rouse Avenue. Judges list, judges on leave, bail roster, duty magistrate, case status and cause list for every district.')

@php
    $button = 'flex min-h-11 items-center justify-center rounded-full bg-slate-100 px-3 text-center text-sm font-semibold text-slate-800 hover:bg-slate-200';
@endphp

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <form method="GET" action="{{ route('courts.delhi-district-courts') }}" role="search">
        <label class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 shadow-sm focus-within:border-[#16776e]">
            <x-icon name="search" class="text-slate-400" />
            <span class="sr-only">Search by judge's name or room number</span>
            <input type="search" name="q" value="{{ $query }}" placeholder="Search by name or room no." class="min-h-12 w-full border-0 bg-transparent px-0 text-base focus:ring-0">
        </label>
    </form>

    @if($query !== '')
        <section aria-labelledby="search-results-heading" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-center justify-between gap-2 px-1">
                <h2 id="search-results-heading" class="text-sm font-bold text-slate-900">{{ $results->count() }} {{ \Illuminate\Support\Str::plural('court', $results->count()) }} for "{{ $query }}"</h2>
                <a href="{{ route('courts.delhi-district-courts') }}" class="text-sm font-semibold text-[#16776e]">Clear</a>
            </div>
            <ul class="mt-2 divide-y divide-slate-100">
                @forelse($results as $bench)
                    @php $judge = $bench->judgeList()[0] ?? ['name' => null, 'designation' => null]; @endphp
                    <li class="flex flex-wrap items-center justify-between gap-2 px-1 py-2.5">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $judge['name'] }}</p>
                            <p class="text-sm text-slate-600">{{ collect([$judge['designation'], $bench->court_room ? 'Room '.$bench->court_room : null, $districtNames[$bench->court_key] ?? null])->filter()->implode(' · ') }}</p>
                        </div>
                        @if($bench->vc_link)
                            <a href="{{ $bench->vc_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">
                                <x-icon name="screen" :size="16" /> Join VC
                            </a>
                        @endif
                    </li>
                @empty
                    <li class="px-1 py-4 text-sm text-slate-500">No judge or room matches. Try a surname or just the room number.</li>
                @endforelse
            </ul>
        </section>
    @endif

    <a href="{{ $services['efiling'] }}" target="_blank" rel="noopener noreferrer"
       class="flex min-h-14 items-center justify-center gap-2 rounded-2xl bg-green-600 px-5 text-lg font-bold text-white shadow-sm hover:bg-green-700">
        <span class="rounded bg-red-600 px-1.5 py-0.5 text-xs font-bold uppercase tracking-wide">New</span> E-Courts Filing Portal
    </a>

    {{-- Delhi at a glance, from the judges & VC lists. --}}
    <dl class="grid grid-cols-4 gap-2 text-center">
        @foreach([['Courts', $totals['courts']], ['Districts', $totals['districts']], ['Complexes', $totals['complexes']], ['Vacant', $totals['vacant']]] as [$label, $value])
            <div class="rounded-2xl border border-slate-200 bg-white px-2 py-3 shadow-sm">
                <dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt>
                <dd class="mt-0.5 text-xl font-bold tabular-nums text-slate-900 sm:text-2xl">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="space-y-3">
        @foreach($complexes as $complex => $districts)
            @php
                $complexCourts = collect($districts)->sum(fn ($district) => $stats['delhi-district-'.\Illuminate\Support\Str::slug($district['district'])]['courts'] ?? 0);
            @endphp
            <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" @if($loop->first) open @endif>
                <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-3 bg-[#142235] px-5 text-white">
                    <span class="text-lg font-bold uppercase tracking-wide">{{ $complex }}</span>
                    <span class="flex items-center gap-3 text-sm text-white/70">
                        <span class="tabular-nums">{{ $complexCourts }} courts</span>
                        <x-icon name="chevron" class="text-white transition group-open:rotate-180" />
                    </span>
                </summary>
                <div class="space-y-4 p-3 sm:p-4">
                    @foreach($districts as $district)
                        @php
                            $slug = \Illuminate\Support\Str::slug($district['district']);
                            $districtStats = $stats['delhi-district-'.$slug] ?? ['courts' => 0, 'vacant' => 0];
                        @endphp
                        <section>
                            <h2 class="flex flex-wrap items-center justify-center gap-x-3 rounded-xl bg-[#16776e] px-3 py-2.5 text-center text-white">
                                <span class="text-base font-bold uppercase tracking-wide text-white">{{ $district['district'] }}</span>
                                <span class="text-sm font-medium text-white/85 tabular-nums">{{ $districtStats['courts'] }} courts{{ $districtStats['vacant'] ? ' · '.$districtStats['vacant'].' vacant' : '' }}</span>
                            </h2>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <a href="{{ route('courts.delhi-district-courts.judges', $slug) }}" class="{{ $button }}">Judges List</a>
                                @foreach($pages as $label => $path)
                                    @php $path = $label === 'Bail roster' ? ($district['bail_roster'] ?? $path) : $path; @endphp
                                    <a href="{{ $district['url'].$path }}" target="_blank" rel="noopener noreferrer" class="{{ $button }}">{{ \Illuminate\Support\Str::title($label) }}</a>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </details>
        @endforeach
    </div>

    <p class="text-center text-xs text-slate-500">
        Court counts from each district's judges &amp; VC list ·
        <a href="{{ $services['statistics'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#16776e] hover:underline">Pending cases (NJDG)</a>
    </p>
</div>
@endsection
