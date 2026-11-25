@extends('layouts.app')

@section('title', 'Search Official Court Records')
@section('page-heading', 'Court Case Search')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Search Official Court Records</h1>
        <p class="mt-2 text-sm text-slate-600">Find a case on the official court portal, then add it to your diary.</p>

        <a href="{{ route('cases.import') }}"
           class="mt-4 inline-flex rounded-lg bg-[#16776e] px-5 py-3 font-medium text-white hover:bg-[#125f58]">
            Import Selected Official Case Record
        </a>
        <a href="{{ route('cases.find') }}"
           class="ml-2 mt-4 inline-flex rounded-lg border border-[#16776e] px-5 py-3 font-medium text-[#16776e] hover:bg-teal-50">
            Find all my cases by bar code
        </a>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Select Court and Search Criteria</h2>

        <form method="GET" action="{{ route('cases.create') }}" class="space-y-5">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                <div>
                    <label class="block text-sm font-medium mb-2">Court Category</label>
                    <select name="court_source" class="w-full rounded-lg border-slate-300">
                        <option value="district" @selected(request('court_source', 'district') === 'district')>
                            District and Subordinate Courts
                        </option>
                        <option value="high_court" @selected(request('court_source') === 'high_court')>
                            High Court
                        </option>
                        <option value="supreme_court" @selected(request('court_source') === 'supreme_court')>
                            Supreme Court of India
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Search By</label>
                    <select name="search_by" class="w-full rounded-lg border-slate-300">
                        @foreach([
                            'cnr' => 'CNR Number',
                            'case_number' => 'Case Number',
                            'party_name' => 'Party Name',
                            'advocate' => 'Advocate Name',
                            'filing_number' => 'Filing Number',
                            'fir' => 'FIR Number',
                            'act' => 'Act',
                            'case_type' => 'Case Type'
                        ] as $key => $label)
                            <option value="{{ $key }}" @selected(request('search_by', 'cnr') === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Search Value</label>
                    <input
                        type="text"
                        name="search_value"
                        value="{{ request('search_value') }}"
                        placeholder="Enter CNR, party name, advocate or other value"
                        class="w-full rounded-lg border-slate-300"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Case / Filing / FIR Year</label>
                    <input
                        type="number"
                        name="case_year"
                        value="{{ request('case_year') }}"
                        min="1900"
                        max="2100"
                        placeholder="Example: 2026"
                        class="w-full rounded-lg border-slate-300"
                    >
                </div>

                <div class="grid gap-6 md:col-span-2 md:grid-cols-2" data-location-picker data-districts-url="{{ url('/api/court-directory/states') }}">
                    <div>
                        <label for="case-search-state" class="block text-sm font-medium mb-2">State / UT</label>
                        <select id="case-search-state" name="state" data-location-state class="w-full rounded-lg border-slate-300 bg-white">
                            <x-state-options :groups="\App\Models\State::dropdownGroups()" :selected="request('state', 'Uttar Pradesh')" />
                        </select>
                    </div>

                    <div>
                        <label for="case-search-district" class="block text-sm font-medium mb-2">District</label>
                        <select data-location-district-select aria-label="District" hidden class="w-full rounded-lg border-slate-300 bg-white"></select>
                        <input id="case-search-district" type="text" name="district" value="{{ request('district') }}"
                               data-location-district-input placeholder="Type the district"
                               class="w-full rounded-lg border-slate-300">
                        <span data-location-district-hint class="mt-1 block text-xs text-slate-500" hidden></span>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2">
                        Court Establishment / Court Complex
                    </label>
                    <input
                        type="text"
                        name="establishment"
                        value="{{ request('establishment') }}"
                        placeholder="Enter court establishment or complex, if known"
                        class="w-full rounded-lg border-slate-300"
                    >
                </div>

            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit"
                    class="rounded-lg bg-slate-900 px-5 py-3 text-white font-medium hover:bg-slate-700">
                    Keep Search Criteria
                </button>

                <a href="{{ route('cases.create') }}"
                    class="rounded-lg border border-slate-300 px-5 py-3 hover:bg-slate-50">
                    Reset
                </a>
            </div>

        </form>
    </section>

    <section class="rounded-xl border border-teal-200 bg-teal-50 p-5">
        <h2 class="font-semibold text-teal-950">Continue to Official Court Search</h2>

        <p class="mt-2 text-sm text-teal-900">Use these details on the official portal; it shows the results and any CAPTCHA.</p>

        <div class="mt-4 flex flex-wrap gap-3">

            @if(request('court_source', 'district') === 'district')
                <a href="https://services.ecourts.gov.in/ecourtindia_v6/"
                    target="_blank" rel="noopener noreferrer"
                    class="rounded-lg bg-[#16776e] px-5 py-3 text-white hover:bg-[#125f58]">
                    Open District Court Search
                </a>
            @elseif(request('court_source') === 'high_court')
                <a href="https://hcservices.ecourts.gov.in/hcservices/main.php"
                    target="_blank" rel="noopener noreferrer"
                    class="rounded-lg bg-[#16776e] px-5 py-3 text-white hover:bg-[#125f58]">
                    Open High Court Services
                </a>
            @else
                <a href="https://www.sci.gov.in/"
                    target="_blank" rel="noopener noreferrer"
                    class="rounded-lg bg-[#16776e] px-5 py-3 text-white hover:bg-[#125f58]">
                    Open Supreme Court Website
                </a>
            @endif

        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2">

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-semibold text-slate-900">District Court Cause Lists</h2>
            <p class="mt-2 text-sm text-slate-600">
                Check date-wise court listings, daily cause lists and establishment information.
            </p>
            <a href="https://services.ecourts.gov.in/ecourtindia_v6/"
                target="_blank" rel="noopener noreferrer"
                class="inline-block mt-4 text-sm font-medium text-[#16776e] hover:underline">
                Open Official eCourts Services
            </a>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-semibold text-slate-900">High Court Cause Lists</h2>
            <p class="mt-2 text-sm text-slate-600">
                Access High Court services and check the relevant court's official listing facilities.
            </p>
            <a href="https://hcservices.ecourts.gov.in/hcservices/main.php"
                target="_blank" rel="noopener noreferrer"
                class="inline-block mt-4 text-sm font-medium text-[#16776e] hover:underline">
                Open High Court Services
            </a>
        </div>

    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-slate-900">My Case Diary</h2>
        <p class="mt-2 text-sm text-slate-600">
            View cases already saved in VakilNiti, their hearing information and case status.
        </p>
        <a href="{{ route('cases.index') }}"
            class="inline-block mt-4 rounded-lg bg-slate-900 px-5 py-3 text-white hover:bg-slate-700">
            Open My Case Diary
        </a>
    </section>


</div>
@endsection