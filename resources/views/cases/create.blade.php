@extends('layouts.app')

@section('title', 'Search Official Court Records')
@section('page-heading', 'Court Case Search')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Search Official Court Records</h1>
        <p class="mt-2 text-sm text-slate-600">
            Find cases through official Supreme Court, High Court and District Court services.
            Add the matching case to your VakilNeeti diary after verification.
        </p>

        <a href="{{ route('cases.import') }}"
           class="mt-4 inline-flex rounded-lg bg-emerald-700 px-5 py-3 font-medium text-white hover:bg-emerald-800">
            Import Selected Official Case Record
        </a>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
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

                <div>
                    <label class="block text-sm font-medium mb-2">State</label>
                    <input
                        type="text"
                        name="state"
                        value="{{ request('state', 'Uttar Pradesh') }}"
                        placeholder="Select or enter state"
                        class="w-full rounded-lg border-slate-300"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">District</label>
                    <input
                        type="text"
                        name="district"
                        value="{{ request('district') }}"
                        placeholder="Example: Gautam Buddha Nagar"
                        class="w-full rounded-lg border-slate-300"
                    >
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

    <section class="rounded-xl border border-blue-200 bg-blue-50 p-5">
        <h2 class="font-semibold text-blue-900">Continue to Official Court Search</h2>

        <p class="mt-2 text-sm text-blue-800">
            Use the search criteria above when searching on the selected official government portal.
            The portal will provide the official results and any required CAPTCHA verification.
        </p>

        <div class="mt-4 flex flex-wrap gap-3">

            @if(request('court_source', 'district') === 'district')
                <a href="https://services.ecourts.gov.in/ecourtindia_v6/"
                    target="_blank" rel="noopener noreferrer"
                    class="rounded-lg bg-blue-700 px-5 py-3 text-white hover:bg-blue-800">
                    Open District Court Search
                </a>
            @elseif(request('court_source') === 'high_court')
                <a href="https://hcservices.ecourts.gov.in/hcservices/main.php"
                    target="_blank" rel="noopener noreferrer"
                    class="rounded-lg bg-blue-700 px-5 py-3 text-white hover:bg-blue-800">
                    Open High Court Services
                </a>
            @else
                <a href="https://www.sci.gov.in/"
                    target="_blank" rel="noopener noreferrer"
                    class="rounded-lg bg-blue-700 px-5 py-3 text-white hover:bg-blue-800">
                    Open Supreme Court Website
                </a>
            @endif

        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2">

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold text-slate-900">District Court Cause Lists</h2>
            <p class="mt-2 text-sm text-slate-600">
                Check date-wise court listings, daily cause lists and establishment information.
            </p>
            <a href="https://services.ecourts.gov.in/ecourtindia_v6/"
                target="_blank" rel="noopener noreferrer"
                class="inline-block mt-4 text-sm font-medium text-blue-700 hover:underline">
                Open Official eCourts Services
            </a>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold text-slate-900">High Court Cause Lists</h2>
            <p class="mt-2 text-sm text-slate-600">
                Access High Court services and check the relevant court's official listing facilities.
            </p>
            <a href="https://hcservices.ecourts.gov.in/hcservices/main.php"
                target="_blank" rel="noopener noreferrer"
                class="inline-block mt-4 text-sm font-medium text-blue-700 hover:underline">
                Open High Court Services
            </a>
        </div>

    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="font-semibold text-slate-900">My Case Diary</h2>
        <p class="mt-2 text-sm text-slate-600">
            View cases already saved in VakilNeeti, their hearing information and case status.
        </p>
        <a href="{{ route('cases.index') }}"
            class="inline-block mt-4 rounded-lg bg-slate-900 px-5 py-3 text-white hover:bg-slate-700">
            Open My Case Diary
        </a>
    </section>

    <p class="text-xs text-slate-500">
        Search criteria are currently retained as a workspace only. Live search results,
        automatic case selection and authorised data synchronisation will be connected
        in subsequent integration stages. No live API search is claimed at this stage.
    </p>

</div>
@endsection