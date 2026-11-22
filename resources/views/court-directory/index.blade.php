@extends('layouts.app')

@section('title', 'Official Court Directory')
@section('meta_description', 'Official Court Directory: Supreme Court, High Courts and district courts of India by State and district.')
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

                <p class="mt-2 max-w-3xl text-sm text-slate-600">Choose the State, District, Court Complex and Establishment on the eCourts portal.</p>
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
            <p class="mt-1 text-sm text-amber-900">If the portal doesn’t load here, use Open Official eCourts Portal.</p>
        </div>
    </section>

    <p class="text-xs text-slate-600">Source: eCourts Services, e-Committee, Supreme Court of India.</p>

</div>

@endsection