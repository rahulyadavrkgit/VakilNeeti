@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div id="overview" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">
                    VakilNiti Module
                </p>
                <h1 class="text-2xl font-bold text-slate-800 mt-1">
                    {{ $module['title'] }}
                </h1>
                <p class="text-sm text-slate-600 mt-2 max-w-3xl">
                    {{ $module['description'] }}
                </p>
            </div>
            <span class="text-xs rounded-full bg-blue-50 text-blue-700 px-3 py-2">
                India-wide architecture
            </span>
        </div>
    </div>

    <section id="practice-jurisdictions" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">
                    My Practice Jurisdictions
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Automatically taken from Profile Settings. These are your default browsing jurisdictions.
                </p>
            </div>
            <a href="{{ route('profile.practice-states.index') }}"
               class="text-sm text-blue-700 hover:underline">
                Manage in Profile Settings
            </a>
        </div>

        @forelse($practiceScopes as $scope)
            <div class="border border-slate-200 rounded-lg p-4 mb-3">
                <div class="font-semibold text-slate-800">
                    {{ $scope['state_name'] }}
                </div>

                @if($scope['mode'] === 'none')
                    <p class="text-sm text-slate-500 mt-1">
                        State-level / High Court practice. No Districts selected.
                    </p>
                @elseif($scope['districts']->isEmpty())
                    <p class="text-sm text-slate-500 mt-1">
                        No active District entries are currently available for this selection.
                    </p>
                @else
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach($scope['districts'] as $districtName)
                            <span class="text-xs rounded-full bg-slate-100 text-slate-700 px-3 py-1">
                                {{ $districtName }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                No active practice jurisdictions were found. Select your States and Districts in Profile Settings to populate your default jurisdiction view.
            </div>
        @endforelse
    </section>

    <section id="explore-jurisdictions" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-800">
            Explore Other Jurisdictions
        </h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">
            Search outside your saved practice jurisdictions without changing your Profile Settings.
        </p>

        <form method="GET" action="{{ url($module['path']) }}"
              class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">

            <div>
                <label for="explore_state_id" class="block text-sm font-medium text-slate-700 mb-1">
                    State / Union Territory
                </label>
                <select id="explore_state_id" name="explore_state_id"
                        class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">All India</option>
                    @foreach($availableStates as $state)
                        <option value="{{ $state->id }}"
                            @selected($exploreState && (int) $exploreState->id === (int) $state->id)>
                            {{ $state->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="explore_district_id" class="block text-sm font-medium text-slate-700 mb-1">
                    District (Optional)
                </label>
                <select id="explore_district_id" name="explore_district_id"
                        class="w-full rounded-lg border-slate-300 text-sm"
                        @disabled(!$exploreState)>
                    <option value="">All available Districts</option>
                    @foreach($exploreDistricts as $district)
                        <option value="{{ $district->id }}"
                            @selected($exploreDistrict && (int) $exploreDistrict->id === (int) $district->id)>
                            {{ $district->district_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="rounded-lg bg-blue-700 text-white px-5 py-2.5 text-sm font-medium hover:bg-blue-800">
                    Explore
                </button>
                <a href="{{ url($module['path']) }}"
                   class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-700">
                    Reset
                </a>
            </div>
        </form>

        @if($exploreState)
            <div class="mt-4 rounded-lg bg-blue-50 border border-blue-100 p-4">
                <p class="text-sm font-semibold text-blue-900">
                    Exploration scope: {{ $exploreState->name }}
                    @if($exploreDistrict)
                        — {{ $exploreDistrict->district_name }}
                    @elseif(request()->query('explore_district_id'))
                        — District selection could not be matched to this State.
                    @else
                        — All available District entries
                    @endif
                </p>
                <p class="text-xs text-blue-800 mt-1">Temporary filter. Your saved jurisdictions are unchanged.</p>
            </div>
        @elseif(request()->query('explore_state_id'))
            <p class="mt-3 text-sm text-amber-700">
                The selected State could not be found in the current State catalogue.
            </p>
        @else
            <p class="mt-3 text-xs text-slate-500">
                All India is selected when no State is specified.
            </p>
        @endif
    </section>

    <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-3">
            {{ $module['title'] }} — Module Areas
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($module['sections'] as $section)
                <div class="border border-slate-200 rounded-lg p-4 flex items-center gap-3">
                    <span class="text-blue-700">●</span>
                    <span class="text-sm text-slate-700">{{ $section }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-5 rounded-lg bg-slate-50 border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-700">
                Catalogue integration status
            </p>
        </div>

        @if($module['path'] === '/pdf-annotator')
            <div class="mt-4 rounded-lg bg-blue-50 border border-blue-100 p-4">
                <p class="text-sm font-semibold text-blue-900">
                    Local-only storage requirement
                </p>
                <p class="text-xs text-blue-800 mt-1">PDFs are processed in your browser and not uploaded.</p>
            </div>
        @endif
    </section>

</div>
@endsection