@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <div class="rounded-2xl bg-slate-900 p-6 text-white">
        <h1 class="text-2xl font-bold">Court Management Dashboard</h1>
        <p class="mt-2 text-sm text-slate-300">
            Access court directories, jurisdictions, official case search and court information.
        </p>
    </div>

    @if(auth()->user()?->is_admin)
    <p class="text-xs font-bold uppercase tracking-[.16em] text-slate-500">Admin · data coverage</p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Courts in Catalogue</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($courtCount) }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Judicial Districts</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($districtCount) }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Court Establishments</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($establishmentCount) }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">States & Union Territories</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($stateCount) }}</p>
        </div>

    </div>
    <p class="-mt-3 text-xs text-slate-500">Counts show records in VakilNiti, not all courts in India.</p>
    @endif

    <section>
        <h2 class="mb-3 text-lg font-bold text-slate-800">Court Access</h2>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

            <a href="{{ url('/court-directory') }}?level=supreme"
               class="rounded-xl border border-slate-200 bg-white p-5 hover:border-blue-400">
                <h3 class="font-semibold text-slate-800">Supreme Court of India</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Access the official Supreme Court services and information.
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Open Supreme Court &rarr;</p>
            </a>

            <a href="{{ url('/court-directory') }}?level=high-court"
               class="rounded-xl border border-slate-200 bg-white p-5 hover:border-blue-400">
                <h3 class="font-semibold text-slate-800">High Courts</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Access the High Court directory for your selected practice jurisdictions.
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Open High Courts &rarr;</p>
            </a>

            <a href="{{ url('/court-directory') }}?level=district"
               class="rounded-xl border border-slate-200 bg-white p-5 hover:border-blue-400">
                <h3 class="font-semibold text-slate-800">District & Subordinate Courts</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Access district and subordinate court information for selected states.
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Open District Courts &rarr;</p>
            </a>

            <a href="{{ route('court-catalogue.index') }}"
               class="rounded-xl border border-slate-200 bg-white p-5 hover:border-blue-400">
                <h3 class="font-semibold text-slate-800">Court Catalogue</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Browse available court categories, districts and establishments in the catalogue.
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Open Court Catalogue &rarr;</p>
            </a>

            <a href="{{ route('court-integrations.ecourts.search') }}"
               class="rounded-xl border border-slate-200 bg-white p-5 hover:border-blue-400">
                <h3 class="font-semibold text-slate-800">Official Court Search</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Search through the official portal-assisted court search workflow.
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Open Court Search &rarr;</p>
            </a>

        </div>
    </section>

</div>
@endsection