@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">

    <div class="mb-6">

        <h1 class="text-2xl font-bold text-slate-800 mt-3">
            {{ $district->district_name }}
        </h1>

        <p class="text-sm text-slate-500 mt-1">
            District &amp; Subordinate Courts
            @if($district->state)
                · {{ $district->state->name }}
            @endif
        </p>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">
            District Information
        </h2>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <dt class="text-xs text-slate-500">District Name</dt>
                <dd class="font-medium text-slate-800 mt-1">
                    {{ $district->district_name }}
                </dd>
            </div>

            <div>
                <dt class="text-xs text-slate-500">District Code</dt>
                <dd class="font-medium text-slate-800 mt-1">
                    {{ $district->district_code ?: 'Not available in the current catalogue' }}
                </dd>
            </div>

            <div>
                <dt class="text-xs text-slate-500">State / UT</dt>
                <dd class="font-medium text-slate-800 mt-1">
                    {{ $district->state?->name ?? 'Not available' }}
                </dd>
            </div>

            <div>
                <dt class="text-xs text-slate-500">Catalogue Status</dt>
                <dd class="font-medium text-slate-800 mt-1">
                    {{ str($district->source_status)->replace('_', ' ')->title() }}
                </dd>
            </div>

            <div>
                <dt class="text-xs text-slate-500">Data Source</dt>
                <dd class="font-medium text-slate-800 mt-1">
                    {{ $district->source_name ?: 'Not specified' }}
                </dd>
            </div>

            <div>
                <dt class="text-xs text-slate-500">Last Verified</dt>
                <dd class="font-medium text-slate-800 mt-1">
                    {{ $district->last_verified_at?->format('d M Y, h:i A') ?? 'Not verified' }}
                </dd>
            </div>
        </dl>

        @if($district->source_url)
            <div class="mt-5">
                <a href="{{ $district->source_url }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="text-sm text-blue-600 hover:underline">
                    View Data Source ↗
                </a>
            </div>
        @endif
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">
            Linked Court Establishments
        </h2>

        @forelse($establishments as $establishment)
            <div class="border-b border-slate-100 last:border-0 py-4">
                <h3 class="font-medium text-slate-800">
                    {{ $establishment->name ?: 'Court establishment' }}
                </h3>

                @if($establishment->court)
                    <p class="text-sm text-slate-600 mt-1">
                        Court: {{ $establishment->court->name }}
                    </p>
                @endif

                @if($establishment->address)
                    <p class="text-sm text-slate-500 mt-1">
                        {{ $establishment->address }}
                    </p>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-500">
                No court establishments are currently linked to this district
                in the VakilNiti catalogue.
            </p>
        @endforelse
    </div>

    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5">
        <h2 class="font-semibold text-slate-800">
            Official Court Services
        </h2>

        <p class="text-sm text-slate-600 mt-2">Court services are on the official eCourts portal.</p>

        <a href="https://services.ecourts.gov.in/ecourtindia_v6/"
           target="_blank"
           rel="noopener noreferrer"
           class="inline-block mt-3 text-sm text-blue-600 hover:underline">
            Open Official eCourts Services ↗
        </a>
    </div>

</div>
@endsection