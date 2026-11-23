@extends('layouts.app')

@section('title', 'Miscellaneous Forms')
@section('meta_description', 'Court forms, civil and criminal forms, affidavits, applications, police and RTI forms in one place.')
@section('page-heading', 'Miscellaneous Forms')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <x-page-header id="court-forms" title="Forms & Filing Resources" subtitle="Official filing portals and common practice forms in one place." />

    <div role="note" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        Forms and filing requirements vary by court, State, case type and date. This page links to resources and internal workflows; it does not provide legal advice or guarantee that a form is current or suitable for a filing.
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <label for="forms-search" class="mb-1 block text-sm font-medium text-slate-700">Find a form or resource</label>
        <input id="forms-search" type="search" class="w-full rounded-lg border-slate-300 text-sm md:max-w-lg"
               placeholder="Search court, civil, criminal, affidavit, RTI...">
    </section>

    <section id="civil-criminal" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Practice workspace forms</h2>
        <p class="mt-1 text-sm text-slate-600">These create internal VakilNiti records; they are not court-filed legal documents.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article data-form-card data-search="new case civil criminal case record" class="rounded-lg border border-slate-200 p-4">
                <h3 class="font-semibold text-slate-900">New case record</h3>
                <p class="mt-2 text-sm text-slate-600">Add a matter to the private case diary.</p>
                <a href="{{ route('cases.create') }}" class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">Create case &rarr;</a>
            </article>
            <article data-form-card data-search="client intake contact client record" class="rounded-lg border border-slate-200 p-4">
                <h3 class="font-semibold text-slate-900">Client record</h3>
                <p class="mt-2 text-sm text-slate-600">Create an internal client profile.</p>
                <a href="{{ route('clients.create') }}" class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">Add client &rarr;</a>
            </article>
            <article data-form-card data-search="invoice billing payment" class="rounded-lg border border-slate-200 p-4">
                <h3 class="font-semibold text-slate-900">Invoice</h3>
                <p class="mt-2 text-sm text-slate-600">Prepare a practice billing record.</p>
                <a href="{{ route('invoices.create') }}" class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">Create invoice &rarr;</a>
            </article>
            <article data-form-card data-search="task deadline reminder" class="rounded-lg border border-slate-200 p-4">
                <h3 class="font-semibold text-slate-900">Task or reminder</h3>
                <p class="mt-2 text-sm text-slate-600">Record a follow-up or practice deadline.</p>
                <a href="{{ route('tasks.create') }}" class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">Create task &rarr;</a>
            </article>
        </div>
    </section>

    <section id="affidavits" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Affidavits &amp; applications</h2>
        <p class="mt-2 text-sm text-slate-600">Use the court’s current prescribed form; check filing requirements with the registry.</p>
        <a href="{{ route('courts.index') }}"
           class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">
            Find an official court directory entry &rarr;
        </a>
    </section>

    <section id="police-rti" class="grid gap-6 md:grid-cols-2">
        <article data-form-card data-search="police department contacts official directory" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Police and department contacts</h2>
            <p class="mt-2 text-sm text-slate-600">Locate official police department and headquarters information.</p>
            <a href="{{ route('police-administration.index') }}" class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">Open Police Administration &rarr;</a>
        </article>
        <article data-form-card data-search="rti right to information application official portal" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Right to Information (RTI)</h2>
            <p class="mt-2 text-sm text-slate-600">Central authorities: use RTI Online. Others may have State portals.</p>
            <a href="https://rtionline.gov.in/" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:underline">Open RTI Online &rarr;</a>
        </article>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Court e-filing</h2>
        <p class="mt-2 text-sm text-slate-600">Check formats, size limits and instructions on the e-filing portal.</p>
        <a href="https://efiling.ecourts.gov.in/" target="_blank" rel="noopener noreferrer"
           class="mt-4 inline-flex rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">
            Open eCourts e-Filing
        </a>
    </section>

    <p id="forms-search-empty" hidden class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">
        No matching forms or resources. Try another search.
    </p>
</div>
@endsection

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    const formsSearch = document.getElementById('forms-search');
    const formCards = Array.from(document.querySelectorAll('[data-form-card]'));
    const noFormMatches = document.getElementById('forms-search-empty');

    formsSearch.addEventListener('input', function () {
        const query = formsSearch.value.trim().toLowerCase();
        let visibleCount = 0;

        formCards.forEach(function (card) {
            const matches = card.dataset.search.includes(query);
            card.hidden = !matches;
            if (matches) {
                visibleCount++;
            }
        });

        noFormMatches.hidden = visibleCount > 0;
    });
</script>
@endpush

