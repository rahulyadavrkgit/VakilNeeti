@extends('layouts.app')

@section('title', 'Bare Acts')
@section('page-heading', 'Bare Acts')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <section id="overview" class="rounded-2xl bg-slate-900 p-6 text-white shadow-sm">
        <p class="text-xs font-bold uppercase tracking-widest text-blue-300">VakilNeeti Legal Resources</p>
        <h1 class="mt-2 text-2xl font-bold">Bare Acts Library</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-300">
            Browse a starter index of commonly used Central statutes and open the official India Code portal to locate the current text.
        </p>
    </section>

    <div role="note" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        This is an index, not the text of the Acts and not a complete or authoritative catalogue. Always verify the latest amendments,
        commencement dates, repeal and savings provisions, and the law applicable to the facts of a matter.
    </div>

    <section id="central-acts" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Central legislation index</h2>
                <p class="mt-1 text-sm text-slate-600">Search by Act title or legal topic.</p>
            </div>
            <div class="w-full md:max-w-sm">
                <label for="act-search" class="mb-1 block text-sm font-medium text-slate-700">Search Acts</label>
                <input id="act-search" type="search" class="w-full rounded-lg border-slate-300 text-sm"
                       placeholder="e.g. evidence, contracts, procedure">
            </div>
        </div>
    </section>

    @foreach($actGroups as $group)
        <section id="{{ $group['id'] }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">{{ $group['title'] }}</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($group['acts'] as $act)
                    @php
                        $legacy = !empty($act['legacy']);
                        $searchText = strtolower($act['title'] . ' ' . $act['topic'] . ' ' . ($act['year'] ?? ''));
                    @endphp
                    <article data-act-card data-search="{{ $searchText }}"
                             class="rounded-lg border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-semibold text-slate-900">{{ $act['title'] }}</h3>
                            @if($act['year'])
                                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ $act['year'] }}</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm text-slate-600">{{ $act['topic'] }}</p>
                        @if($legacy)
                            <p class="mt-3 text-xs font-medium text-amber-800">Legacy reference — check applicable transition and savings provisions.</p>
                        @else
                            <p class="mt-3 text-xs text-slate-500">Check the official text for amendments and commencement.</p>
                        @endif
                        <a href="https://www.indiacode.nic.in/" target="_blank" rel="noopener noreferrer"
                           class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">
                            Find official text on India Code
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach

    <p id="act-search-empty" hidden class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">
        No Acts match that search. Try another title or topic.
    </p>

    <section id="sources" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Official source</h2>
        <p class="mt-1 text-sm text-slate-600">
            India Code is the Government of India's repository for Central Acts. Search there for the official text and confirm its current status.
        </p>
        <a href="https://www.indiacode.nic.in/" target="_blank" rel="noopener noreferrer"
           class="mt-4 inline-flex rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">
            Open India Code
        </a>
    </section>
</div>
@endsection

@push('scripts')
<script>
    const actSearch = document.getElementById('act-search');
    const actCards = Array.from(document.querySelectorAll('[data-act-card]'));
    const noActMatches = document.getElementById('act-search-empty');

    actSearch.addEventListener('input', function () {
        const query = actSearch.value.trim().toLowerCase();
        let visibleCount = 0;

        actCards.forEach(function (card) {
            const matches = card.dataset.search.includes(query);
            card.hidden = !matches;
            if (matches) {
                visibleCount++;
            }
        });

        noActMatches.hidden = visibleCount > 0;
    });
</script>
@endpush
