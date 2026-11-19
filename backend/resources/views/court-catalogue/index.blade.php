@extends('layouts.app')
@section('title', 'Court Catalogue')
@section('page-heading', 'Court Catalogue')
@section('content')
<div class="space-y-6">

        <div class="mb-7">
            <p class="text-sm text-slate-500">Judicial Information System</p>

            <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mt-1">
                Court Data Catalogue
            </h2>

            <p class="text-sm text-slate-500 mt-2">
                Select a jurisdiction and retrieve the available court information.
            </p>
        </div>

        <!-- Selection Card -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-5 md:p-7">

            <div class="flex items-center justify-between gap-3 mb-6">
                <div>
                    <h3 class="text-lg font-semibold">Find a Court</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Use the available selections to locate a court.
                    </p>
                </div>

                <span class="text-xs bg-blue-50 text-blue-700 rounded-full px-3 py-2">
                    Selection First
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <!-- Step 1: Court Type -->
                <div>
                    <label for="courtType" class="block text-sm font-medium mb-2">
                        1. Court Category *
                    </label>

                    <select id="courtType"
                            class="w-full border border-slate-300 rounded-lg px-3 py-3 bg-white">
                        <option value="">Select Court Category</option>

                        @foreach($courtTypes as $type)
                            <option value="{{ $type->id }}"
                                    data-slug="{{ $type->slug }}"
                                    data-requires-state="{{ $type->requires_state ? '1' : '0' }}">
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Step 2: State -->
                <div id="stateWrapper" hidden>
                    <label for="state" class="block text-sm font-medium mb-2">
                        2. State / Union Territory *
                    </label>

                    <select id="state"
                            disabled
                            class="w-full border border-slate-300 rounded-lg px-3 py-3 bg-white disabled:bg-slate-100">
                        <option value="">Select State / UT</option>

                        @foreach($states as $state)
                            <option value="{{ $state->id }}">
                                {{ $state->name }}
                                {{ $state->is_union_territory ? '(UT)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Step 3: Court -->
                <div>
                    <label for="court" class="block text-sm font-medium mb-2">
                        3. Court / Institution *
                    </label>

                    <select id="court"
                            disabled
                            class="w-full border border-slate-300 rounded-lg px-3 py-3 bg-white disabled:bg-slate-100">
                        <option value="">Select Court Category First</option>
                    </select>
                </div>

                <!-- Step 4: Judicial District -->
                <div id="districtWrapper" hidden>
                    <label for="district" class="block text-sm font-medium mb-2">
                        4. Judicial District
                    </label>

                    <select id="district"
                            disabled
                            class="w-full border border-slate-300 rounded-lg px-3 py-3 bg-white disabled:bg-slate-100">
                        <option value="">Select Judicial District</option>
                    </select>
                </div>

                <!-- Step 5: Establishment -->
                <div id="establishmentWrapper" hidden>
                    <label for="establishment" class="block text-sm font-medium mb-2">
                        5. Complex / Bench / Establishment
                    </label>

                    <select id="establishment"
                            disabled
                            class="w-full border border-slate-300 rounded-lg px-3 py-3 bg-white disabled:bg-slate-100">
                        <option value="">Select Establishment</option>
                    </select>
                </div>

                <!-- Supreme Court Services -->
                <div id="supremeServicesWrapper" hidden>
                    <label for="supremeService" class="block text-sm font-medium mb-2">
                        Available Supreme Court Services
                    </label>

                    <select id="supremeService"
                            class="w-full border border-slate-300 rounded-lg px-3 py-3 bg-white">
                        <option value="">Select a Service</option>
                        <option value="display-board">Display Board</option>
                        <option value="cause-list">Daily Cause List</option>
                        <option value="case-status">Case Status</option>
                        <option value="orders">Daily Orders</option>
                        <option value="judgments">Judgments</option>
                        <option value="judges-roster">Judges and Roster</option>
                        <option value="calendar">Court Calendar</option>
                        <option value="live-streaming">Live Streaming</option>
                        <option value="e-gate-pass">E-Gate Pass</option>
                    </select>
                </div>

            </div>

            <!-- Selection status -->
            <div id="selectionStatus"
                 class="mt-6 rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600"
                 aria-live="polite">
                Begin by selecting a court category.
            </div>

            <div id="sourceNotice" class="mt-4 text-xs text-slate-500">
                Court options are loaded from VakilNeeti's local catalogue.
                Records will appear here as authorised sources are synchronised.
            </div>

        </div>

        <!-- Results -->
        <div class="mt-6 bg-white border border-slate-200 rounded-xl shadow-sm p-5 md:p-7">

            <h3 class="font-semibold text-lg">Selected Jurisdiction</h3>

            <div id="selectedSummary"
                 class="mt-4 text-sm text-slate-500">
                No court selected.
            </div>

        </div>

    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const courtType = document.getElementById('courtType');
    const state = document.getElementById('state');
    const court = document.getElementById('court');
    const district = document.getElementById('district');
    const establishment = document.getElementById('establishment');
    const supremeService = document.getElementById('supremeService');

    const stateWrapper = document.getElementById('stateWrapper');
    const districtWrapper = document.getElementById('districtWrapper');
    const establishmentWrapper = document.getElementById('establishmentWrapper');
    const supremeServicesWrapper = document.getElementById('supremeServicesWrapper');

    const status = document.getElementById('selectionStatus');
    const summary = document.getElementById('selectedSummary');

    const urls = {
        courts: @json(route('court-catalogue.data.courts')),
        districts: @json(route('court-catalogue.data.districts')),
        establishments: @json(route('court-catalogue.data.establishments'))
    };

    let selectedCourtType = null;
    let selectedCourt = null;
    let selectedDistrict = null;
    let selectedEstablishment = null;

    function clearSelect(element, placeholder) {
        element.innerHTML = '';

        const option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;

        element.appendChild(option);
        element.value = '';
        element.disabled = true;
    }

    function fillSelect(element, records, placeholder) {
        clearSelect(element, placeholder);

        records.forEach(function (record) {
            const option = document.createElement('option');
            option.value = record.id;
            option.textContent = record.label;

            element.appendChild(option);
        });

        element.disabled = records.length === 0;
    }

    async function requestData(url) {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'Unable to load records.');
        }

        return result.data || [];
    }

    function showStatus(message, isError = false) {
        status.textContent = message;
        status.className = isError
            ? 'mt-6 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700'
            : 'mt-6 rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600';
    }

    function resetAfterCourtType() {
        clearSelect(court, 'Select Court');
        clearSelect(district, 'Select Judicial District');
        clearSelect(establishment, 'Select Establishment');

        districtWrapper.hidden = true;
        establishmentWrapper.hidden = true;
        supremeServicesWrapper.hidden = true;

        selectedCourt = null;
        selectedDistrict = null;
        selectedEstablishment = null;
        summary.textContent = 'No court selected.';
    }

    async function loadCourts() {
        resetAfterCourtType();

        if (!selectedCourtType) {
            return;
        }

        const params = new URLSearchParams({
            court_type_id: selectedCourtType.id
        });

        if (state.value) {
            params.append('state_id', state.value);
        }

        if (selectedCourtType.requiresState && !state.value) {
            showStatus('Please select a state or Union Territory.');
            return;
        }

        showStatus('Loading available courts...');

        try {
            const records = await requestData(
                urls.courts + '?' + params.toString()
            );

            fillSelect(court, records, 'Select Court / Institution');

            if (records.length === 0) {
                showStatus(
                    'No synchronised court records are available for this selection yet. They will appear after authorised source synchronisation.'
                );
                return;
            }

            showStatus('Select a court from the available records.');

        } catch (error) {
            showStatus(error.message, true);
        }
    }

    async function loadDistricts() {
        clearSelect(district, 'Loading Judicial Districts...');
        clearSelect(establishment, 'Select Establishment');

        establishmentWrapper.hidden = true;
        selectedDistrict = null;
        selectedEstablishment = null;
        districtWrapper.hidden = false;

        const params = new URLSearchParams({
            state_id: state.value,
            court_id: court.value
        });

        try {
            const records = await requestData(
                urls.districts + '?' + params.toString()
            );

            fillSelect(district, records, 'Select Judicial District');

            if (records.length === 0) {
                showStatus(
                    'Judicial district records are not synchronised for this court yet.'
                );
            } else {
                showStatus('Select the relevant judicial district.');
            }

        } catch (error) {
            showStatus(error.message, true);
        }
    }

    async function loadEstablishments() {
        clearSelect(establishment, 'Loading Establishments...');
        selectedEstablishment = null;

        const params = new URLSearchParams({
            court_id: court.value
        });

        if (district.value) {
            params.append('district_id', district.value);
        }

        try {
            const records = await requestData(
                urls.establishments + '?' + params.toString()
            );

            fillSelect(
                establishment,
                records,
                'Select Complex / Bench'
            );

            if (records.length === 0) {
                showStatus(
                    'No linked establishments are currently available. They will appear after synchronisation and relationship mapping.'
                );
            } else {
                showStatus('Select a complex, bench or establishment.');
            }

        } catch (error) {
            showStatus(error.message, true);
        }
    }

    function updateSummary() {
        const typeLabel = courtType.selectedOptions[0]?.textContent.trim() || '';
        const stateLabel = stateWrapper.hidden
            ? 'Not applicable'
            : state.selectedOptions[0]?.textContent.trim();

        const courtLabel = court.selectedOptions[0]?.textContent.trim() || '';
        const districtLabel = district.value
            ? district.selectedOptions[0].textContent.trim()
            : 'Not selected';

        const establishmentLabel = establishment.value
            ? establishment.selectedOptions[0].textContent.trim()
            : 'Not selected';

        summary.textContent =
            'Category: ' + typeLabel +
            ' | State: ' + stateLabel +
            ' | Court: ' + courtLabel +
            ' | Judicial District: ' + districtLabel +
            ' | Establishment: ' + establishmentLabel;
    }

    courtType.addEventListener('change', function () {

        resetAfterCourtType();

        const option = courtType.selectedOptions[0];

        selectedCourtType = option && option.value
            ? {
                id: option.value,
                slug: option.dataset.slug,
                requiresState: option.dataset.requiresState === '1'
            }
            : null;

        state.value = '';
        state.disabled = true;
        stateWrapper.hidden = !selectedCourtType?.requiresState;

        supremeServicesWrapper.hidden = true;

        if (!selectedCourtType) {
            showStatus('Begin by selecting a court category.');
            return;
        }

        if (selectedCourtType.requiresState) {
            state.disabled = false;
            showStatus('Select a state or Union Territory.');
        } else {
            loadCourts();
        }
    });

    state.addEventListener('change', function () {
        if (selectedCourtType) {
            loadCourts();
        }
    });

    court.addEventListener('change', async function () {

        selectedCourt = court.value || null;
        selectedDistrict = null;
        selectedEstablishment = null;

        clearSelect(district, 'Select Judicial District');
        clearSelect(establishment, 'Select Establishment');

        districtWrapper.hidden = true;
        establishmentWrapper.hidden = true;
        supremeServicesWrapper.hidden = true;

        if (!selectedCourt) {
            summary.textContent = 'No court selected.';
            return;
        }

        if (selectedCourtType.slug === 'supreme-court') {
            supremeServicesWrapper.hidden = false;
            showStatus(
                'Supreme Court selected. Choose a service to view its module when the connector is enabled.'
            );
        } else if (selectedCourtType.slug === 'district-courts') {
            await loadDistricts();
        } else {
            establishmentWrapper.hidden = false;
            await loadEstablishments();
        }

        updateSummary();
    });

    district.addEventListener('change', async function () {
        selectedDistrict = district.value || null;
        selectedEstablishment = null;

        establishmentWrapper.hidden = !district.value;

        if (district.value) {
            await loadEstablishments();
        }

        updateSummary();
    });

    establishment.addEventListener('change', function () {
        selectedEstablishment = establishment.value || null;

        updateSummary();

        if (establishment.value) {
            showStatus(
                'Establishment selected. Courtroom-level and service-level options will be connected as their source records are synchronised.'
            );
        }
    });

    supremeService.addEventListener('change', function () {
        if (supremeService.value) {
            showStatus(
                'Supreme Court service selected. Its live source connector will be enabled in the relevant integration phase.'
            );
        }
    });

});
</script>
@endpush
