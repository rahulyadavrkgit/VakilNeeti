@extends('layouts.app')

@section('title', 'Bar Associations')
@section('meta_description', 'National, State, High Court and District Bar Associations and Bar Councils of India, with official links.')
@section('page-heading', 'Bar Associations')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <x-page-header id="national" title="Bar Councils & Associations" subtitle="Bar Councils and court bar associations.">
        <a href="{{ route('bar-directory.index') }}" class="inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">Open full directory</a>
    </x-page-header>

    <div role="note" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        Directory entries may change. Confirm current office holders, contact details, membership requirements and official status directly with the relevant organization.
    </div>

    <section id="bar-councils" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Statutory Bar Councils</h2>
        <p class="mt-2 text-sm text-slate-600">Statutory regulators under the Advocates Act, 1961.</p>
        <a href="{{ route('bar-directory.index') }}"
           class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">
            Browse Bar Councils and directory records &rarr;
        </a>
    </section>

    <div class="grid gap-6 md:grid-cols-2">
        <section id="state-high-court" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">State &amp; High Court Associations</h2>
            <p class="mt-2 text-sm text-slate-600">Associations for State and High Court practice.</p>
            <a href="{{ route('bar-directory.index', ['court_type' => 'High Court']) }}"
               class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">
                View High Court associations &rarr;
            </a>
        </section>

        <section id="district" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">District Bar Associations</h2>
            <p class="mt-2 text-sm text-slate-600">District and court-level associations.</p>
            <a href="{{ route('bar-directory.index', ['court_type' => 'District Court']) }}"
               class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">
                View District Court associations &rarr;
            </a>
        </section>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Search the directory</h2>
        <a href="{{ route('bar-directory.index') }}"
           class="mt-4 inline-flex rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Search all organizations
        </a>
    </section>
</div>
@endsection

