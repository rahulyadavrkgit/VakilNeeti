@extends('layouts.app')

@section('title', 'Bar directory')
@section('page-heading', 'Bar directory')
@section('meta_description', 'Directory of Bar Councils and Bar Associations across India, with official contact links.')

@section('content')
<div class="mx-auto max-w-7xl">

    <x-page-header class="mb-5" title="Bar Councils & Associations" subtitle="State Bar Councils, the Bar Council of India and court bar associations." />

    <!-- Filter Form -->
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('bar-directory.index') }}" id="barDirectoryForm">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                <!-- 1. State Filter -->
                <div>
                    <label for="state_id" class="mb-2 block text-sm font-semibold text-gray-700">
                        State / Jurisdiction
                    </label>
                    <select name="state_id" id="state_id" data-autosubmit
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All States & National (BCI)</option>
                        @foreach($availableStates as $state)
                            <option value="{{ $state->id }}" @selected(request('state_id') == $state->id)>
                                {{ $state->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Court Type Filter (for Associations) -->
                <div>
                    <label for="court_type" class="mb-2 block text-sm font-semibold text-gray-700">
                        Court Type (Associations)
                    </label>
                    <select name="court_type" id="court_type" data-autosubmit
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Court Types</option>
                        <option value="Supreme Court" @selected(request('court_type') === 'Supreme Court')>Supreme Court</option>
                        <option value="High Court" @selected(request('court_type') === 'High Court')>High Court</option>
                        <option value="District Court" @selected(request('court_type') === 'District Court')>District Court</option>
                        <option value="Tribunal" @selected(request('court_type') === 'Tribunal')>Tribunal</option>
                    </select>
                </div>

                <!-- 3. Keyword Search -->
                <div>
                    <label for="q" class="mb-2 block text-sm font-semibold text-gray-700">
                        Search Council / Association
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="q" id="q" value="{{ $search ?? '' }}"
                               placeholder="Name, city, president..."
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="submit"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                            Search
                        </button>
                    </div>
                </div>

            </div>

            @if($search !== '' || request('state_id') || request('court_type'))
                <div class="mt-4 flex items-center justify-between rounded-lg bg-gray-50 px-4 py-2 text-sm text-gray-700">
                    <span>Filtering directory records.</span>
                    <a href="{{ route('bar-directory.index') }}" class="text-indigo-600 hover:underline">Reset Filters</a>
                </div>
            @endif
        </form>
    </div>

    <!-- Section 1: Bar Councils (Statutory) -->
    <div class="mt-8">
        <h2 class="text-xl font-bold text-slate-900">Statutory Bar Councils</h2>
        <p class="mt-1 text-sm text-gray-600">Regulators under the Advocates Act, 1961 (enrolment and discipline).</p>

        @if(isset($barCouncils) && $barCouncils->count() > 0)
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($barCouncils as $council)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <h3 class="text-base font-semibold text-slate-900">{{ $council->council_name }}</h3>
                            <span class="rounded bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-800">
                                {{ ucfirst($council->jurisdiction_type) }}
                            </span>
                        </div>

                        <p class="mt-3 text-sm text-gray-600">
                            {{ $council->office_address ?: 'Address not published.' }}
                        </p>

                        @if($council->contact_phone)
                            <div class="mt-3 text-sm text-gray-700">
                                <strong>Phone:</strong> <a href="tel:{{ $council->contact_phone }}" class="text-indigo-600 hover:underline">{{ $council->contact_phone }}</a>
                            </div>
                        @endif

                        @if($council->email)
                            <div class="mt-1 text-sm text-gray-700">
                                <strong>Email:</strong> <a href="mailto:{{ $council->email }}" class="text-indigo-600 hover:underline">{{ $council->email }}</a>
                            </div>
                        @endif

                        @if($council->official_url)
                            <div class="mt-3">
                                <a href="{{ $council->official_url }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-indigo-600 hover:underline">
                                    Official Website &nearr;
                                </a>
                            </div>
                        @endif

                        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-xs text-gray-500">
                            <span>Source: {{ $council->source_name }}</span>
                            <span class="rounded bg-green-100 px-2 py-0.5 font-semibold text-green-800">Verified</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="mt-4 rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                No bar councils found matching your criteria.
            </div>
        @endif
    </div>

    <!-- Section 2: Bar Associations (Court-Affiliated) -->
    <div class="mt-10">
        <h2 class="text-xl font-bold text-slate-900">Court-Affiliated Bar Associations</h2>
        <p class="mt-1 text-sm text-gray-600">Voluntary associations of advocates at each court.</p>

        @if(isset($barAssociations) && $barAssociations->count() > 0)
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($barAssociations as $assoc)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <h3 class="text-base font-semibold text-slate-900">{{ $assoc->association_name }}</h3>
                            <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-800">
                                {{ $assoc->court_type }}
                            </span>
                        </div>

                        <p class="mt-3 text-sm text-gray-600">
                            {{ $assoc->office_address ?: 'Address not published.' }}
                        </p>

                        @if($assoc->president_name || $assoc->secretary_name)
                            <div class="mt-3 text-xs text-gray-600 space-y-0.5">
                                @if($assoc->president_name)
                                    <div><strong>President:</strong> {{ $assoc->president_name }}</div>
                                @endif
                                @if($assoc->secretary_name)
                                    <div><strong>Secretary:</strong> {{ $assoc->secretary_name }}</div>
                                @endif
                            </div>
                        @endif

                        @if($assoc->contact_phone)
                            <div class="mt-3 text-sm text-gray-700">
                                <strong>Phone:</strong> <a href="tel:{{ $assoc->contact_phone }}" class="text-indigo-600 hover:underline">{{ $assoc->contact_phone }}</a>
                            </div>
                        @endif

                        @if($assoc->official_url)
                            <div class="mt-3">
                                <a href="{{ $assoc->official_url }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-indigo-600 hover:underline">
                                    Official Portal &nearr;
                                </a>
                            </div>
                        @endif

                        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-xs text-gray-500">
                            <span>State: {{ $assoc->state->name ?? 'National' }}</span>
                            <span class="rounded bg-green-100 px-2 py-0.5 font-semibold text-green-800">Verified</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $barAssociations->links() }}
            </div>
        @else
            <div class="mt-4 rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                No bar associations found matching your criteria.
            </div>
        @endif
    </div>

</div>
@endsection