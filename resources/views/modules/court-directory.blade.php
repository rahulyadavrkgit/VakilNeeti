@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Courts & Judicial Directory</h1>
            <p class="mt-1 text-sm text-gray-600">Supreme Court, High Courts, benches and district court complexes.</p>
        </div>
        <div class="mt-3 sm:mt-0">
            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-800">
                eCourts & DoJ Verified Sources
            </span>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('court-directory.index') }}">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div>
                    <label for="state_id" class="mb-2 block text-sm font-semibold text-gray-700">
                        State / Union Territory
                    </label>
                    <select name="state_id" id="state_id" data-autosubmit
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All States & Supreme Court</option>
                        @foreach($availableStates as $state)
                            <option value="{{ $state->id }}" @selected(request('state_id') == $state->id)>
                                {{ $state->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="court_type" class="mb-2 block text-sm font-semibold text-gray-700">
                        Court Tier
                    </label>
                    <select name="court_type" id="court_type" data-autosubmit
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Court Tiers</option>
                        <option value="Supreme Court" @selected(request('court_type') === 'Supreme Court')>Supreme Court</option>
                        <option value="High Court" @selected(request('court_type') === 'High Court')>High Court (Principal Seat)</option>
                        <option value="High Court Bench" @selected(request('court_type') === 'High Court Bench')>High Court Bench</option>
                        <option value="District Court" @selected(request('court_type') === 'District Court')>District Court Complexes</option>
                    </select>
                </div>

                <div>
                    <label for="q" class="mb-2 block text-sm font-semibold text-gray-700">
                        Search Court, District or City
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="q" id="q" value="{{ $search ?? '' }}"
                               placeholder="Saket, Allahabad, Gautam Buddh Nagar..."
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
                    <span>Showing filtered judicial records.</span>
                    <a href="{{ route('court-directory.index') }}" class="text-indigo-600 hover:underline">Reset Filters</a>
                </div>
            @endif
        </form>
    </div>

    <!-- Section 1: Supreme Court & High Courts -->
    @if($highCourts->count() > 0 && request('court_type') !== 'District Court')
        <div class="mt-8">
            <h2 class="text-xl font-bold text-gray-900">Constitutional Courts (Supreme Court & High Courts)</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($highCourts as $court)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-base font-bold text-gray-900">{{ $court->court_name }}</h3>
                            <span class="shrink-0 rounded bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                {{ $court->court_type }}
                            </span>
                        </div>

                        <p class="mt-1 text-xs font-medium text-indigo-700">
                            {{ $court->seat_type }}{{ $court->city ? ' — ' . $court->city : '' }}
                        </p>

                        @if($court->jurisdiction_states)
                            <p class="mt-2 text-xs text-gray-500">
                                <strong>Jurisdiction:</strong> {{ $court->jurisdiction_states }}
                            </p>
                        @endif

                        <p class="mt-2 text-sm text-gray-600">
                            {{ $court->address ?: 'Address not published.' }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-3 text-sm">
                            @if($court->official_url)
                                <a href="{{ $court->official_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-indigo-600 hover:underline">
                                    Official Website &nearr;
                                </a>
                            @endif
                            @if($court->ecourts_url)
                                <a href="{{ $court->ecourts_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-emerald-700 hover:underline">
                                    eCourts Portal &nearr;
                                </a>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-xs text-gray-500">
                            <span>{{ $court->source_name }}</span>
                            <span class="rounded bg-green-100 px-2 py-0.5 font-semibold text-green-800">Verified</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Section 2: District & Subordinate Court Complexes -->
    @if(request('court_type') === null || request('court_type') === '' || request('court_type') === 'District Court')
        <div class="mt-10">
            <h2 class="text-xl font-bold text-gray-900">District & Subordinate Court Complexes</h2>
            <p class="mt-1 text-sm text-gray-600">Judicial districts are maintained separately from police districts.</p>

            @if($courtComplexes->count() > 0)
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($courtComplexes as $complex)
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-base font-bold text-gray-900">{{ $complex->complex_name }}</h3>
                                <span class="shrink-0 rounded bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">
                                    District Court
                                </span>
                            </div>

                            <p class="mt-1 text-xs font-medium text-gray-600">
                                Judicial District: <strong>{{ $complex->judicial_district_name }}</strong>
                            </p>

                            @if($complex->highCourt)
                                <p class="mt-1 text-xs text-indigo-700">
                                    Under: {{ $complex->highCourt->court_name }}
                                </p>
                            @endif

                            <p class="mt-3 text-sm text-gray-600">
                                {{ $complex->address ?: 'Address not published.' }}
                            </p>

                            <div class="mt-3 flex flex-wrap gap-3 text-sm">
                                @if($complex->official_url)
                                    <a href="{{ $complex->official_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-indigo-600 hover:underline">
                                        District Court Portal &nearr;
                                    </a>
                                @endif
                                @if($complex->ecourts_url)
                                    <a href="{{ $complex->ecourts_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-emerald-700 hover:underline">
                                        eCourts Case Status &nearr;
                                    </a>
                                @endif
                            </div>

                            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-xs text-gray-500">
                                <span>State: {{ $complex->state->name ?? '' }}</span>
                                <span class="rounded bg-green-100 px-2 py-0.5 font-semibold text-green-800">Verified</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $courtComplexes->links() }}
                </div>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                    No district court complexes found matching your filter criteria.
                </div>
            @endif
        </div>
    @endif

</div>
@endsection