@extends('layouts.app')

@section('title', 'Police administration')
@section('page-heading', 'Police administration')
@section('meta_description', 'Police stations, State police departments, police districts and ranges, and official police portals across India.')

@section('content')
<div class="mx-auto max-w-7xl">

    <x-page-header class="mb-5" title="Police Administration" subtitle="Police ranges, districts, sub-divisions and stations across India." />

    @if(!empty($profileStateIds) && count($profileStateIds))
        <div class="mb-5 rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900">
            <strong>Profile jurisdiction:</strong>
            Your Profile practice States are prioritised in the State selection.
            You can still explore all States and Union Territories of India here.
            Police districts are maintained separately from judicial districts.
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('police-administration.index') }}"
              id="policeDirectoryForm">

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                <!-- 1. State Selection -->
                <div>
                    <label for="state_id" class="mb-2 block text-sm font-semibold text-gray-700">
                        1. State / Union Territory
                    </label>
                    <select name="state_id" id="state_id" data-autosubmit
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select State / UT</option>
                        @foreach($availableStates as $state)
                            <option value="{{ $state->id }}"
                                @selected($selectedState && $selectedState->id == $state->id)>
                                {{ $state->name }}
                                @if(in_array($state->id, $profileStateIds ?? []))
                                    — My Profile
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Police Range Selection -->
                <div>
                    <label for="police_range_id" class="mb-2 block text-sm font-semibold text-gray-700">
                        2. Police Range (Optional)
                    </label>
                    <select name="police_range_id" id="police_range_id" data-autosubmit
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            {{ !$selectedState ? 'disabled' : '' }}>
                        <option value="">All Ranges</option>
                        @foreach($policeRanges ?? [] as $range)
                            <option value="{{ $range->id }}" @selected(request('police_range_id') == $range->id)>
                                {{ $range->range_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. District Selection (Reference catalogue) -->
                <div>
                    <label for="district_id" class="mb-2 block text-sm font-semibold text-gray-700">
                        3. District
                    </label>
                    <select name="district_id" id="district_id" data-autosubmit
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            {{ !$selectedState ? 'disabled' : '' }}>
                        <option value="">Select District</option>
                        @foreach($directoryDistricts as $district)
                            <option value="{{ $district->id }}"
                                @selected($selectedDirectoryDistrict && $selectedDirectoryDistrict->id == $district->id)>
                                {{ $district->district_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <!-- Second row for Sub-division, Specialist Units, and Search -->
            <div class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-3">

                <!-- 4. Police Sub-Division -->
                <div>
                    <label for="police_sub_division_id" class="mb-2 block text-sm font-semibold text-gray-700">
                        4. Sub-Division (Optional)
                    </label>
                    <select name="police_sub_division_id" id="police_sub_division_id" data-autosubmit
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            {{ !$selectedState ? 'disabled' : '' }}>
                        <option value="">All Sub-Divisions</option>
                        @foreach($policeSubDivisions ?? [] as $subDivision)
                            <option value="{{ $subDivision->id }}" @selected(request('police_sub_division_id') == $subDivision->id)>
                                {{ $subDivision->sub_division_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 5. Specialist Units Filter -->
                <div>
                    <label for="specialist_category" class="mb-2 block text-sm font-semibold text-gray-700">
                        5. Specialist / Unit Type
                    </label>
                    <select name="specialist_category" id="specialist_category" data-autosubmit
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            {{ !$selectedState ? 'disabled' : '' }}>
                        <option value="">All Units & Stations</option>
                        <option value="all" @selected(request('specialist_category') === 'all')>All Specialist Units</option>
                        <option value="Cyber Crime" @selected(request('specialist_category') === 'Cyber Crime')>Cyber Crime</option>
                        <option value="Crime Branch" @selected(request('specialist_category') === 'Crime Branch')>Crime Branch</option>
                        <option value="Railway" @selected(request('specialist_category') === 'Railway')>Railway Police</option>
                        <option value="Metro" @selected(request('specialist_category') === 'Metro')>Metro Police</option>
                        <option value="Special Cell" @selected(request('specialist_category') === 'Special Cell')>Special Cell</option>
                        <option value="STF" @selected(request('specialist_category') === 'STF')>STF</option>
                    </select>
                </div>

                <!-- 6. Keyword Search -->
                <div>
                    <label for="q" class="mb-2 block text-sm font-semibold text-gray-700">
                        6. Search Station, Pincode or City
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="q" id="q" value="{{ $search ?? '' }}"
                               placeholder="Station name, PIN, City, SHO..."
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="submit"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                            Search
                        </button>
                    </div>
                </div>

            </div>

            @if($selectedState && $search !== '')
                <div class="mt-4 flex items-center justify-between rounded-lg bg-gray-50 px-4 py-2 text-sm text-gray-700">
                    <span>Showing results for search: "<strong>{{ $search }}</strong>" in {{ $selectedState->name }}</span>
                    <a href="{{ route('police-administration.index', ['state_id' => $selectedState->id]) }}" class="text-indigo-600 hover:underline">Clear Search</a>
                </div>
            @endif

        </form>
    </div>

    @if($selectedState)
        <!-- Official State Police Headquarters & Portals Card -->
        @if(isset($departmentOffices) && $departmentOffices->count() > 0)
            <div class="mt-6 rounded-xl border border-indigo-100 bg-indigo-50/50 p-6 shadow-sm">
                <h2 class="text-lg font-bold text-indigo-900">Official Police Headquarters & Portals — {{ $selectedState->name }}</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach($departmentOffices as $office)
                        <div class="rounded-lg border border-indigo-200 bg-white p-4 shadow-sm">
                            <h3 class="text-base font-semibold text-slate-900">{{ $office->office_name }}</h3>
                            <p class="mt-1 text-xs font-medium text-indigo-700">{{ $office->office_type }}</p>
                            @if($office->official_url)
                                <div class="mt-3 text-sm">
                                    <a href="{{ $office->official_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-indigo-600 hover:underline">
                                        Visit Official Portal &nearr;
                                    </a>
                                </div>
                            @endif
                            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 border-t border-gray-100 pt-2">
                                <span>Emergency / Helpline: <strong>{{ $office->phone ?? '112' }}</strong></span>
                                <span class="rounded bg-green-100 px-2 py-0.5 font-semibold text-green-800">Verified Source</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-8">
            <h2 class="text-xl font-bold text-slate-900">
                Police Stations & Units in {{ $selectedState->name }}
            </h2>

            @if(isset($stations) && $stations->count() > 0)
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($stations as $station)
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                            <div class="flex items-start justify-between">
                                <h3 class="text-base font-semibold text-slate-900">
                                    {{ $station->station_name }}
                                </h3>
                                @if($station->is_specialist_unit)
                                    <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-800">
                                        {{ $station->specialist_unit_category ?? 'Specialist' }}
                                    </span>
                                @else
                                    <span class="rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">
                                        Police Station
                                    </span>
                                @endif
                            </div>

                            @if($station->station_code)
                                <p class="mt-1 text-xs text-gray-500">Station Code: {{ $station->station_code }}</p>
                            @endif

                            <p class="mt-3 text-sm text-gray-600">
                                {{ $station->address ?: 'Address not published yet.' }}
                            </p>

                            @if($station->city || $station->pincode)
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $station->city }}{{ $station->pincode ? ' - ' . $station->pincode : '' }}
                                </p>
                            @endif

                            @if($station->phone)
                                <div class="mt-3 text-sm text-gray-700">
                                    <strong>Phone:</strong> <a href="tel:{{ $station->phone }}" class="text-indigo-600 hover:underline">{{ $station->phone }}</a>
                                </div>
                            @endif

                            @if($station->sho_name)
                                <div class="mt-1 text-sm text-gray-700">
                                    <strong>SHO / Officer:</strong> {{ $station->sho_name }}
                                </div>
                            @endif

                            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-xs text-gray-500">
                                <span>Status: {{ ucfirst(str_replace('_', ' ', $station->source_status)) }}</span>
                                <a href="{{ route('police-administration.stations.show', $station->id) }}" class="font-semibold text-indigo-600 hover:underline">
                                    View Details &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $stations->links() }}
                </div>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
                    <p class="text-sm font-medium text-gray-600">No police stations found matching the current selection or search criteria.</p>
                    <p class="mt-1 text-xs text-gray-500">Official verified station records will appear here as they are imported.</p>
                </div>
            @endif
        </div>
    @else
        <div class="mt-8 rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
            <p class="text-base font-semibold text-gray-700">Please select a State or Union Territory above to view police administration details.</p>
        </div>
    @endif

</div>
@endsection