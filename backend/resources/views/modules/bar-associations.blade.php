@extends('layouts.app')

@section('title', 'Bar Associations')
@section('page-heading', 'Bar Associations')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <section id="national" class="rounded-2xl bg-slate-900 p-6 text-white shadow-sm">
        <p class="text-xs font-bold uppercase tracking-widest text-blue-300">VakilNeeti Legal Directory</p>
        <h1 class="mt-2 text-2xl font-bold">Bar Councils &amp; Associations</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-300">
            Find statutory Bar Councils and court-affiliated professional associations in the existing searchable directory.
        </p>
        <a href="{{ route('bar-directory.index') }}"
           class="mt-5 inline-flex rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
            Open full directory
        </a>
    </section>

    <div role="note" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        Directory entries may change. Confirm current office holders, contact details, membership requirements and official status directly with the relevant organization.
    </div>

    <section id="bar-councils" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Statutory Bar Councils</h2>
        <p class="mt-2 text-sm text-slate-600">
            Bar Councils are statutory regulatory bodies under the Advocates Act, 1961. Use the directory to browse State Bar Councils and national-level entries.
        </p>
        <a href="{{ route('bar-directory.index') }}"
           class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">
            Browse Bar Councils and directory records &rarr;
        </a>
    </section>

    <div class="grid gap-6 md:grid-cols-2">
        <section id="state-high-court" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">State &amp; High Court Associations</h2>
            <p class="mt-2 text-sm text-slate-600">
                Browse professional associations connected with State and High Court practice. Filter the directory by State and court type.
            </p>
            <a href="{{ route('bar-directory.index', ['court_type' => 'High Court']) }}"
               class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">
                View High Court associations &rarr;
            </a>
        </section>

        <section id="district" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">District Bar Associations</h2>
            <p class="mt-2 text-sm text-slate-600">
                Find court-affiliated association entries and narrow the results by State, court type or a name and location search.
            </p>
            <a href="{{ route('bar-directory.index', ['court_type' => 'District Court']) }}"
               class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">
                View District Court associations &rarr;
            </a>
        </section>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Search the directory</h2>
        <p class="mt-2 text-sm text-slate-600">
            The full directory includes State Bar Councils, national entries and court-affiliated associations, with filters for State, court type and keyword.
        </p>
        <a href="{{ route('bar-directory.index') }}"
           class="mt-4 inline-flex rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Search all organizations
        </a>
    </section>
</div>
@endsection
