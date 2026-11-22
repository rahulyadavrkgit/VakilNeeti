@extends('layouts.app')

@section('title', 'Courts of India')
@section('page-heading', 'Courts')
@section('meta_description', 'Courts of India in one place: Supreme Court, High Courts, Delhi District Courts, live display boards, eCourts services, tribunals and the official court directory.')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Courts</h1>
        <p class="mt-1 text-sm text-slate-600">Official court services, display boards and directories.</p>
    </div>

    <x-collection-toolbar target="courts-list" placeholder="Search courts and services" />

    <nav id="courts-list" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" aria-label="Courts">
        <x-module-tile :href="route('courts.supreme-court')" icon="pillars" title="Supreme Court" description="Court list, display boards, e-pass, calendar" />
        <x-module-tile :href="route('courts.delhi-high-court')" icon="building" title="Delhi High Court" description="Display board, roster, cause list, judgements" />
        <x-module-tile :href="route('courts.delhi-district-courts')" icon="gavel" title="Delhi District Courts" description="All seven court complexes" />
        <x-module-tile :href="route('courts.display-boards')" icon="board" title="Display boards" description="Live courtroom boards" />
        <x-module-tile :href="route('courts.ecourts-services')" icon="search" title="eCourts services" description="Case status, ePay, eFiling, NJDG" />
        <x-module-tile :href="route('court-directory.index', ['level' => 'high-court'])" icon="building" title="High Courts" description="All High Courts and benches" />
        <x-module-tile :href="route('court-directory.index', ['level' => 'district'])" icon="map" title="District courts" description="By State and district" />
        <x-module-tile :href="route('court-catalogue.index')" icon="scale" title="Tribunals" description="Appellate courts and tribunals" />
        <x-module-tile :href="route('court-integrations.ecourts.search')" icon="search" title="Official court search" description="Search official portals" />
        <x-module-tile :href="route('court-directory.index')" icon="list" title="Court directory" description="Every court we list" />
        <p data-filter-empty hidden class="col-span-full rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-500">Nothing matches your search.</p>
    </nav>
</div>
@endsection
