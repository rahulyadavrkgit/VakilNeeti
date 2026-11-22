@extends('layouts.app')

@section('title', 'Jurisdiction dashboard')
@section('page-heading', 'Jurisdiction dashboard')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <header>
        <h1 class="text-2xl font-bold text-slate-800">{{ $module['title'] }}</h1>
        <p class="text-sm text-slate-600 mt-1">{{ $module['description'] }}</p>
    </header>

    @if(count($practiceScopes))
        <div class="flex flex-wrap gap-2">
            @foreach($practiceScopes as $scope)
                <span class="inline-flex flex-wrap items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                    <strong>{{ $scope['state_name'] }}</strong>

                    @if($scope['mode'] === 'none')
                        <span class="text-slate-500">· State / High Court</span>
                    @elseif($scope['mode'] === 'all')
                        <span class="text-slate-500">· All districts</span>
                    @else
                        @foreach($scope['districts'] as $district)
                            <span class="text-slate-500">· {{ $district }}</span>
                        @endforeach
                    @endif
                </span>
            @endforeach
        </div>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <form method="GET" action="{{ url($module['path']) }}"
              class="grid grid-cols-1 gap-3 md:grid-cols-3 md:items-end">

            <div>
                <label for="explore_state_id" class="mb-1 block text-sm font-medium text-slate-700">
                    Search another State
                </label>
                <select id="explore_state_id" name="explore_state_id"
                        data-autosubmit
                        class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Use saved practice locations</option>
                    @foreach($availableStates as $state)
                        <option value="{{ $state->id }}"
                            @selected($exploreState && (int) $exploreState->id === (int) $state->id)>
                            {{ $state->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="explore_district_id" class="mb-1 block text-sm font-medium text-slate-700">
                    District
                </label>
                <select id="explore_district_id" name="explore_district_id"
                        class="w-full rounded-lg border-slate-300 text-sm"
                        @disabled(!$exploreState)>
                    <option value="">All available locations in this State</option>
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
                        class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                    Search
                </button>
                <a href="{{ url($module['path']) }}"
                   class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-700">
                    Reset
                </a>
            </div>
        </form>

        @if($exploreState)
            <p class="mt-3 text-sm text-slate-600">
                Location: <strong>{{ $exploreState->name }}</strong>
                @if($exploreDistrict)
                    · {{ $exploreDistrict->district_name }}
                @endif
            </p>
        @endif
    </section>

    <section class="space-y-3">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @foreach($module['categories'] as $category)
                <div id="{{ $category['id'] }}"
                     class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold text-slate-800">{{ $category['label'] }}</h2>
                </div>
            @endforeach
        </div>

        <p class="text-xs text-slate-500">
            Only verified official information should be presented as an official record.
        </p>
    </section>

</div>
@endsection