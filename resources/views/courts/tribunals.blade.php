@extends('layouts.app')

@section('title', 'Tribunals')
@section('page-heading', 'Tribunals')
@section('meta_description', 'National tribunals in India with their official websites and e-filing: NCLT, NCLAT, DRT, ITAT, CESTAT, GSTAT, CAT, AFT, NGT, APTEL, TDSAT, consumer commissions and the Railway Claims Tribunal.')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <x-page-header title="Tribunals" subtitle="Official websites and e-filing." />

    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 shadow-sm focus-within:border-[#16776e] sm:max-w-sm">
        <x-icon name="search" :size="16" class="text-slate-400" />
        <span class="sr-only">Search tribunals</span>
        <input type="search" data-filter-list="tribunal-list" placeholder="Search: NCLT, tax, consumer…" class="min-h-11 w-full border-0 bg-transparent px-0 text-sm focus:ring-0">
    </label>

    <div id="tribunal-list" class="space-y-6">
        @foreach($groups as $group => $tribunals)
            <section aria-labelledby="tribunal-{{ \Illuminate\Support\Str::slug($group) }}">
                <h2 id="tribunal-{{ \Illuminate\Support\Str::slug($group) }}" class="mb-2 text-sm font-bold uppercase tracking-wide text-slate-500">{{ $group }}</h2>
                <ul class="grid gap-3 sm:grid-cols-2">
                    @foreach($tribunals as $tribunal)
                        <li data-filter-item class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#16776e]">{{ $tribunal['short'] }}</p>
                                <h3 class="mt-0.5 font-semibold text-slate-900">{{ $tribunal['name'] }}</h3>
                                <p class="mt-1 text-sm text-slate-600">{{ $tribunal['hears'] }}</p>
                            </div>
                            <div class="mt-auto flex flex-wrap gap-2">
                                <a href="{{ $tribunal['url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex min-h-10 flex-1 items-center justify-center gap-1.5 rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58] sm:flex-none">
                                    Website
                                </a>
                                @isset($tribunal['efiling'])
                                    <a href="{{ $tribunal['efiling'] }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex min-h-10 flex-1 items-center justify-center gap-1.5 rounded-xl border border-[#16776e] px-4 text-sm font-semibold text-[#16776e] hover:bg-teal-50 sm:flex-none">
                                        e-Filing
                                    </a>
                                @endisset
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
        <p data-filter-empty hidden class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-500">No tribunal matches.</p>
    </div>

    <a href="{{ route('courts.delhi-district-courts') }}" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm hover:border-[#16776e]">
        <span><strong class="text-slate-900">MACT, Family, Labour courts and Industrial tribunals</strong> <span class="text-slate-600">sit in the Delhi district courts.</span></span>
        <span class="shrink-0 font-semibold text-[#16776e]">Open &rarr;</span>
    </a>
</div>
@endsection
