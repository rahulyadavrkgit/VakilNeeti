@extends('layouts.app')

@section('title', 'Official Court Directory')
@section('page-heading', 'Official Court Directory')

@section('content')

@php
    $level = request('level');
    $state = request('state');

    $directoryHeading = match ($level) {
        'supreme' => 'Supreme Court of India',
        'high-court' => 'High Court Directory',
        'district' => 'District & Subordinate Court Directory',
        default => 'Official eCourts Directory',
    };
@endphp

<div class="mx-auto max-w-7xl space-y-6">

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-blue-700">
                    Courts
                </p>

                <h2 class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $directoryHeading }}
                </h2>

                @if($state)
                    <p class="mt-2 text-sm font-medium text-slate-700">
                        Selected practice jurisdiction: {{ $state }}
                    </p>
                @endif

                <p class="mt-2 max-w-3xl text-sm text-slate-600">
                    Use the official eCourts portal to select the relevant
                    State, District, Court Complex and Establishment.
                    The jurisdiction shown above is navigation context;
                    it does not automatically filter the official portal.
                </p>
            </div>

            <a href="{{ route('profile.practice-states.index') }}"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Manage Practice States
            </a>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold text-slate-900">
                    Official eCourts Services
                </h3>
                <p class="mt-1 text-sm text-slate-600">
                    Court selection and any required verification take place
                    on the official government portal.
                </p>
            </div>

            <a href="https://services.ecourts.gov.in/ecourtindia_v6/?p=location%2Findex"
               target="_blank"
               rel="noopener noreferrer"
               class="rounded-lg bg-blue-700 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800">
                Open Official eCourts Portal
            </a>
        </div>

        <div class="mt-5 overflow-hidden rounded-lg border border-slate-300 bg-slate-50">
            <iframe
                class="h-[750px] w-full bg-white"
                src="https://services.ecourts.gov.in/ecourtindia_v6/?p=location%2Findex"
                title="Official eCourts Location Directory"
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin">
            </iframe>
        </div>

        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
            <h3 class="font-semibold text-amber-900">
                Portal display limitation
            </h3>
            <p class="mt-1 text-sm text-amber-900">
                The official website may prevent display inside an embedded
                frame. If the portal does not load, use the Open Official
                eCourts Portal button above.
            </p>
        </div>
    </section>

    <p class="text-xs text-slate-600">
        Source: eCourts Services, e-Committee, Supreme Court of India.
        VakilNeeti does not claim an independent live API connection or
        automatic retrieval of protected official records on this page.
    </p>

</div>

@endsection