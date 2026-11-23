@extends('layouts.app')

@section('title', 'Delhi District Courts')
@section('page-heading', 'Delhi District Courts')
@section('meta_description', 'Delhi District Courts by court complex: Rohini, Karkardooma, Tis Hazari, Dwarka, Saket, Patiala House and Rouse Avenue, with district websites, case status, cause lists and e-filing.')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    @include('courts.partials.breadcrumb', ['current' => 'Delhi District Courts'])

    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Delhi District Courts</h1>
        <p class="mt-1 text-sm text-slate-600">Eleven district courts in seven court complexes.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <a href="{{ $services['efiling'] }}" rel="noopener noreferrer"
           class="flex items-center justify-center gap-2 rounded-2xl bg-[#16776e] px-5 py-4 text-base font-bold text-white shadow-sm hover:bg-[#125f58]">
            <x-icon name="upload" /> E-Courts filing portal <x-icon name="external" :size="16" />
        </a>
        <label class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 shadow-sm focus-within:border-[#16776e]">
            <x-icon name="search" class="text-slate-400" />
            <span class="sr-only">Search courts</span>
            <input type="search" data-filter-list="delhi-complexes" placeholder="Search by complex or district" class="min-h-12 w-full border-0 bg-transparent text-sm focus:ring-0">
        </label>
    </div>

    <div id="delhi-complexes" class="space-y-3">
        @foreach($complexes as $complex => $districts)
            <details data-filter-item class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" @if($loop->first) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 bg-[#142235] px-5 py-4 text-white">
                    <span class="text-lg font-bold uppercase tracking-wide">{{ $complex }}</span>
                    <span class="flex items-center gap-3 text-sm text-slate-300">
                        {{ collect($districts)->pluck('district')->implode(', ') }}
                        <x-icon name="chevron" class="transition group-open:rotate-180" />
                    </span>
                </summary>
                <div class="space-y-4 p-4">
                    @foreach($districts as $district)
                        <section>
                            <h2 class="rounded-lg bg-teal-50 px-3 py-2 text-center text-sm font-bold uppercase tracking-wide text-teal-900">{{ $district['district'] }}</h2>
                            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <a href="{{ route('courts.delhi-district-courts.judges', \Illuminate\Support\Str::slug($district['district'])) }}"
                                   class="flex min-h-11 items-center justify-center gap-1.5 rounded-full bg-[#16776e] px-4 text-center text-sm font-semibold text-white hover:bg-[#125f58]">
                                    Judges list &amp; VC
                                </a>
                                <a href="{{ $district['url'] }}" rel="noopener noreferrer"
                                   class="flex min-h-11 items-center justify-center gap-1.5 rounded-full bg-slate-100 px-4 text-center text-sm font-semibold text-slate-800 hover:bg-slate-200">
                                    Leave &amp; rosters <x-icon name="external" :size="14" />
                                </a>
                                <a href="{{ $services['case_status'] }}" rel="noopener noreferrer"
                                   class="flex min-h-11 items-center justify-center gap-1.5 rounded-full bg-slate-100 px-4 text-center text-sm font-semibold text-slate-800 hover:bg-slate-200">
                                    Case status / order <x-icon name="external" :size="14" />
                                </a>
                                <a href="{{ $services['cause_list'] }}" rel="noopener noreferrer"
                                   class="flex min-h-11 items-center justify-center gap-1.5 rounded-full bg-slate-100 px-4 text-center text-sm font-semibold text-slate-800 hover:bg-slate-200">
                                    Cause list <x-icon name="external" :size="14" />
                                </a>
                            </div>
                        </section>
                    @endforeach
                </div>
            </details>
        @endforeach
        <p data-filter-empty hidden class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500">No court complex or district matches your search.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">More for all Delhi district courts</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach([
                'virtual_court' => 'Virtual courts',
                'vc_links' => 'VC hearing links',
                'evening_court' => 'Evening (traffic) court',
                'statistics' => 'Case statistics (NJDG)',
                'website' => 'Delhi District Courts website',
            ] as $key => $label)
                <a href="{{ $services[$key] }}" rel="noopener noreferrer"
                   class="inline-flex min-h-10 items-center gap-1.5 rounded-lg border border-[#16776e] px-3 text-sm font-semibold text-[#16776e] hover:bg-teal-50">
                    {{ $label }} <x-icon name="external" :size="14" />
                </a>
            @endforeach
        </div>
    </section>
</div>
@endsection
