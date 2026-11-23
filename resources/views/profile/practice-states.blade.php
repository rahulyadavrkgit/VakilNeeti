@extends('layouts.app')

@section('title', 'Practice States & Jurisdictions')
@section('page-heading', 'Practice States & Jurisdictions')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">

    <x-page-header title="Practice States & Jurisdictions" subtitle="The States, High Courts and districts where you practise." />

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif


        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @include('profile.partials.setup-progress', [
            'step' => 'practice-states',
            'continueUrl' => route('profile.setup.next', ['after' => 'practice-states']),
            'continueLabel' => 'Your choices save as you go; press Continue when you are done.',
        ])

        <form method="POST"
              action="{{ route('profile.practice-states.store') }}"
              id="practiceJurisdictionForm">
            @csrf

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-slate-800">1. Practice States / Union Territories</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            The State's High Court is included automatically.
                        </p>
                    </div>
                    <div id="practiceSaveStatus"
                         class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600"
                         role="status"
                         aria-live="polite">
                        Changes save automatically
                    </div>
                </div>

                <div class="mt-4">
                    <label for="stateSearch" class="mb-1 block text-sm font-medium text-slate-700">
                        Search State / Union Territory
                    </label>
                    <input type="search"
                           id="stateSearch"
                           placeholder="Search State or Union Territory..."
                           autocomplete="off"
                           class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <p id="stateSearchCount" class="mt-2 text-xs text-slate-500"></p>
                </div>

                <div id="stateCheckboxList"
                     class="mt-3 grid max-h-80 grid-cols-1 gap-2 overflow-y-auto overscroll-contain rounded-lg border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2 lg:grid-cols-3"
                     tabindex="0"
                     aria-label="Scrollable State and Union Territory list">
                    @foreach($states as $stateName)
                        @php
                            $stateRecord = $stateRecordsByName[$stateName] ?? \App\Models\State::where('name', $stateName)->first();
                            $stateId = $stateRecord?->id;
                            $isSelected = in_array($stateName, old('states', $selectedStates), true);
                        @endphp

                        <label class="state-search-item flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-white p-3 hover:border-indigo-300 hover:bg-indigo-50"
                               data-search-name="{{ strtolower($stateName) }}">
                            <input type="checkbox"
                                   name="states[]"
                                   value="{{ $stateName }}"
                                   data-state-id="{{ $stateId ?? '' }}"
                                   class="practice-state-checkbox h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   {{ $isSelected ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-slate-700">{{ $stateName }}</span>
                        </label>
                    @endforeach
                </div>

                <p id="noStateSearchResults" class="mt-3 hidden text-sm text-amber-700">
                    No matching State or Union Territory found.
                </p>

            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-800">2. District Practice (Optional)</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Only if you also practise in district courts.
                </p>

                <div id="stateDistrictSettings" class="mt-4 space-y-3">
                    @foreach($states as $stateName)
                        @php
                            $stateRecord = $stateRecordsByName[$stateName] ?? \App\Models\State::where('name', $stateName)->first();
                            $stateId = $stateRecord?->id;
                            $isSelected = in_array($stateName, old('states', $selectedStates), true);
                            $mode = $stateId ? ($districtSelectionModes[$stateId] ?? 'all') : 'none';
                            $districtIds = $stateId ? ($selectedDistrictIds[$stateId] ?? []) : [];
                        @endphp

                        @if($isSelected && $stateId)
                            <div class="state-district-setting flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-4"
                                 data-state-id="{{ $stateId }}"
                                 data-state-name="{{ $stateName }}">
                                <div>
                                    <div class="font-semibold text-slate-800">{{ $stateName }}</div>
                                    <div class="text-xs text-slate-500 setting-summary">
                                        @if($mode === 'none')
                                            High Court only · No districts selected
                                        @elseif($mode === 'all')
                                            All districts selected
                                        @else
                                            {{ count($districtIds) }} districts selected
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            class="open-district-modal rounded-lg border border-indigo-200 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                                        Select Districts
                                    </button>

                                    <div class="district-hidden-fields">
                                        <input type="hidden"
                                               name="district_selection_mode[{{ $stateId }}]"
                                               value="{{ $mode }}">
                                        @if($mode === 'selected')
                                            @foreach($districtIds as $districtId)
                                                <input type="hidden"
                                                       name="districts[{{ $stateId }}][]"
                                                       value="{{ $districtId }}">
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <p id="districtEmptyMessage"
                   class="mt-3 text-sm text-slate-500"
                   @if(count($selectedStates)) hidden @endif>
                    Select a State/UT above.
                </p>
            </div>
        </form>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-800">3. Saved Practice Jurisdictions</h2>

            @forelse($selectedStates as $stateName)
                @php
                    $stateRecord = $stateRecordsByName[$stateName] ?? null;
                    $stateId = $stateRecord?->id;
                    $mode = $stateId ? ($districtSelectionModes[$stateId] ?? 'all') : 'none';
                    $districtIds = $stateId ? ($selectedDistrictIds[$stateId] ?? []) : [];
                    $districtNames = $mode === 'selected' && count($districtIds)
                        ? \App\Models\JudicialDistrict::whereIn('id', $districtIds)->orderBy('district_name')->pluck('district_name')->all()
                        : [];
                @endphp

                <div class="mt-3 rounded-lg bg-slate-50 p-3">
                    <div class="font-medium text-slate-800">{{ $stateName }}</div>
                    @if($mode === 'none')
                        <p class="mt-1 text-sm text-indigo-700">High Court only · No districts selected</p>
                    @elseif($mode === 'all')
                        <p class="mt-1 text-sm text-indigo-700">All districts</p>
                    @elseif(count($districtNames))
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($districtNames as $districtName)
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs text-indigo-700">
                                    {{ $districtName }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-1 text-sm text-slate-500">No district selection.</p>
                    @endif
                </div>
            @empty
                <p class="mt-2 text-sm text-slate-500">No practice jurisdictions have been saved yet.</p>
            @endforelse
        </div>

        @if(auth()->user()?->is_admin)
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[.16em] text-slate-500">Admin · data coverage</p>
            <h2 class="mt-1 font-semibold text-slate-800">Court Catalogue Coverage</h2>
            <p class="mt-1 text-sm text-slate-500">Counts show catalogue records, not all courts.</p>

            @foreach($selectedStates as $stateName)
                <div class="flex items-center justify-between border-b border-slate-100 py-3">
                    <span class="text-sm text-slate-700">{{ $stateName }}</span>
                    <span class="text-sm font-semibold text-slate-800">
                        {{ $courtCounts[$stateName] ?? 0 }} catalogue entries
                    </span>
                </div>
            @endforeach
        </div>
        @endif

        <!-- District selection popup -->
        <div id="districtSelectionModal"
             class="fixed inset-0 z-[100] hidden items-center justify-center overflow-y-auto bg-slate-900/60 p-3 sm:p-5"
             role="dialog"
             aria-modal="true"
             aria-labelledby="districtModalTitle">

            <div id="districtModalPanel"
                 class="my-auto flex w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
                 style="max-height: calc(100dvh - 24px);">

                <div class="flex shrink-0 items-center justify-between border-b border-slate-200 p-4 sm:p-5">
                    <div class="min-w-0">
                        <h2 id="districtModalTitle" class="text-lg font-bold text-slate-800">
                            Select Practice Districts
                        </h2>
                        <p id="districtModalState" class="mt-1 truncate text-sm text-slate-500"></p>
                    </div>

                    <button type="button" id="closeDistrictModal"
                            class="ml-3 shrink-0 rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-100"
                            aria-label="Close">
                        &#10005;
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden p-4 sm:p-5">
                    <div class="shrink-0">
                        <label for="districtSearch" class="mb-1 block text-sm font-medium text-slate-700">
                            Search Districts
                        </label>
                        <input type="search"
                               id="districtSearch"
                               placeholder="Type district name to search..."
                               autocomplete="off"
                               class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <p id="districtSearchCount" class="text-xs text-slate-500"></p>
                            <button type="button" id="selectAllDistricts"
                                    class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                                Select All
                            </button>
                        </div>
                    </div>

                    <div id="districtListScroll"
                         class="min-h-0 flex-1 overflow-y-auto overscroll-contain rounded-lg border border-slate-200 p-3"
                         style="max-height: 45vh;">
                        <div id="districtCheckboxList"
                             class="grid grid-cols-1 gap-2 sm:grid-cols-2"></div>
                        <p id="noDistrictSearchResults" class="mt-3 hidden text-sm text-amber-700">
                            No matching district found.
                        </p>
                    </div>

                    <p id="districtModalMessage"
                       class="shrink-0 text-xs text-slate-500"
                       aria-live="polite">
                        Loading district catalogue...
                    </p>
                </div>

                <div class="flex shrink-0 justify-end border-t border-slate-200 p-4 sm:p-5">
                    <button type="button" id="cancelDistrictModal"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Close
                    </button>
                </div>
            </div>
        </div>

</div>
@endsection

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('practiceJurisdictionForm');
    if (!form) return;

    const modal = document.getElementById('districtSelectionModal');
    const modalPanel = document.getElementById('districtModalPanel');
    const modalState = document.getElementById('districtModalState');
    const modalMessage = document.getElementById('districtModalMessage');
    const checkboxList = document.getElementById('districtCheckboxList');
    const selectAllButton = document.getElementById('selectAllDistricts');
    const districtSearch = document.getElementById('districtSearch');
    const districtSearchCount = document.getElementById('districtSearchCount');
    const noDistrictResults = document.getElementById('noDistrictSearchResults');
    const stateSearch = document.getElementById('stateSearch');
    const stateSearchCount = document.getElementById('stateSearchCount');
    const noStateResults = document.getElementById('noStateSearchResults');
    const settingsContainer = document.getElementById('stateDistrictSettings');
    const emptyMessage = document.getElementById('districtEmptyMessage');
    const saveStatus = document.getElementById('practiceSaveStatus');

    let activeStateId = null;
    let availableDistricts = [];
    let requestSequence = 0;
    let loadingDistricts = false;
    let saveQueue = Promise.resolve();

    function setSaveStatus(message, type) {
        saveStatus.textContent = message;
        saveStatus.className = 'rounded-full px-3 py-1.5 text-xs font-medium';

        if (type === 'success') {
            saveStatus.classList.add('bg-green-100', 'text-green-700');
        } else if (type === 'error') {
            saveStatus.classList.add('bg-red-100', 'text-red-700');
        } else if (type === 'saving') {
            saveStatus.classList.add('bg-amber-100', 'text-amber-700');
        } else {
            saveStatus.classList.add('bg-slate-100', 'text-slate-600');
        }
    }

    function getCard(stateId) {
        return Array.from(settingsContainer.querySelectorAll('.state-district-setting'))
            .find(card => String(card.dataset.stateId) === String(stateId));
    }

    function getCurrentCardSelection(card, stateId) {
        const modeInput = card.querySelector(
            'input[name="district_selection_mode[' + stateId + ']"]'
        );

        const ids = Array.from(
            card.querySelectorAll('input[name="districts[' + stateId + '][]"]')
        ).map(input => String(input.value));

        return { mode: modeInput?.value || 'none', ids: ids };
    }

    function buildSnapshot() {
        const data = new FormData();
        data.append('_token', form.querySelector('input[name="_token"]').value);

        form.querySelectorAll('.practice-state-checkbox:checked').forEach(function (checkbox) {
            data.append('states[]', checkbox.value);
        });

        settingsContainer.querySelectorAll('.state-district-setting').forEach(function (card) {
            const stateId = card.dataset.stateId;
            const checkbox = Array.from(form.querySelectorAll('.practice-state-checkbox'))
                .find(item => String(item.dataset.stateId) === String(stateId));

            if (!checkbox || !checkbox.checked) return;

            const selection = getCurrentCardSelection(card, stateId);
            data.append('district_selection_mode[' + stateId + ']', selection.mode);

            if (selection.mode === 'selected') {
                selection.ids.forEach(function (id) {
                    data.append('districts[' + stateId + '][]', id);
                });
            }
        });

        return data;
    }

    function saveChanges() {
        const snapshot = buildSnapshot();
        setSaveStatus('Saving changes...', 'saving');

        // Queue requests so an older click cannot overwrite a newer selection.
        saveQueue = saveQueue.then(async function () {
            const response = await fetch(form.action, {
                method: 'POST',
                body: snapshot,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            let result = {};
            try {
                result = await response.json();
            } catch (error) {
                throw new Error('The server returned an unreadable response.');
            }

            if (!response.ok || result.success !== true) {
                const validationMessage = result.errors
                    ? Object.values(result.errors).flat().join(' ')
                    : '';
                throw new Error(validationMessage || result.message || 'Unable to save this change.');
            }

            setSaveStatus('All changes saved', 'success');
            return result;
        }).catch(function (error) {
            setSaveStatus('Save failed', 'error');
            modalMessage.textContent = 'Save error: ' + error.message;
            return null;
        });

        return saveQueue;
    }

    function updateSummary(card) {
        const stateId = card.dataset.stateId;
        const selection = getCurrentCardSelection(card, stateId);
        const summary = card.querySelector('.setting-summary');

        if (!summary) return;

        if (selection.mode === 'none') {
            summary.textContent = 'High Court only · No districts selected';
        } else if (selection.mode === 'all') {
            summary.textContent = 'All districts selected';
        } else {
            summary.textContent = selection.ids.length + ' districts selected';
        }
    }

    function getDistrictCheckboxes() {
        return Array.from(checkboxList.querySelectorAll('.modal-district-checkbox'));
    }

    function updateDistrictCount() {
        const boxes = getDistrictCheckboxes();
        const selected = boxes.filter(box => box.checked).length;
        const visible = Array.from(
            checkboxList.querySelectorAll('.district-search-item')
        ).filter(item => !item.hidden).length;

        districtSearchCount.textContent =
            selected + ' selected · ' + visible +
            ' of ' + availableDistricts.length + ' districts shown';
    }

    function filterDistricts() {
        const query = districtSearch.value.trim().toLocaleLowerCase();
        let visible = 0;

        checkboxList.querySelectorAll('.district-search-item').forEach(function (item) {
            const matches = item.dataset.searchName.includes(query);
            item.hidden = !matches;
            if (matches) visible++;
        });

        noDistrictResults.classList.toggle('hidden', visible !== 0);
        updateDistrictCount();
    }

    function addDistrictCheckbox(district, checked) {
        const label = document.createElement('label');
        label.className =
            'district-search-item flex cursor-pointer items-center gap-2 rounded-md bg-slate-50 p-3 hover:bg-indigo-50';
        label.dataset.searchName = String(district.district_name || '').toLocaleLowerCase();

        const input = document.createElement('input');
        input.type = 'checkbox';
        input.value = district.id;
        input.checked = checked;
        input.className =
            'modal-district-checkbox h-4 w-4 shrink-0 rounded border-slate-300 text-indigo-600';

        input.addEventListener('change', function () {
            persistModalSelection();
        });

        const name = document.createElement('span');
        name.className = 'min-w-0 flex-1 text-sm text-slate-700';
        name.textContent = district.district_name || 'Unnamed district';

        label.appendChild(input);
        label.appendChild(name);

        if (district.source_status) {
            const sourceStatus = document.createElement('span');
            sourceStatus.className = 'shrink-0 text-xs text-slate-400';
            sourceStatus.textContent = district.source_status;
            label.appendChild(sourceStatus);
        }

        checkboxList.appendChild(label);
    }

    function persistModalSelection() {
        if (!activeStateId) return;

        const card = getCard(activeStateId);
        if (!card) return;

        const boxes = getDistrictCheckboxes();
        const selectedIds = boxes.filter(box => box.checked).map(box => String(box.value));

        let mode = 'none';
        if (boxes.length > 0 && selectedIds.length === boxes.length) {
            mode = 'all';
        } else if (selectedIds.length > 0) {
            mode = 'selected';
        }

        const hiddenFields = card.querySelector('.district-hidden-fields');
        hiddenFields.innerHTML = '';

        const modeInput = document.createElement('input');
        modeInput.type = 'hidden';
        modeInput.name = 'district_selection_mode[' + activeStateId + ']';
        modeInput.value = mode;
        hiddenFields.appendChild(modeInput);

        if (mode === 'selected') {
            selectedIds.forEach(function (districtId) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'districts[' + activeStateId + '][]';
                input.value = districtId;
                hiddenFields.appendChild(input);
            });
        }

        updateSummary(card);
        updateDistrictCount();
        saveChanges();
    }

    function createStateSetting(stateId, stateName) {
        const card = document.createElement('div');
        card.className =
            'state-district-setting flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-4';
        card.dataset.stateId = stateId;
        card.dataset.stateName = stateName;

        const info = document.createElement('div');

        const title = document.createElement('div');
        title.className = 'font-semibold text-slate-800';
        title.textContent = stateName;

        const summary = document.createElement('div');
        summary.className = 'text-xs text-slate-500 setting-summary';
        summary.textContent = 'High Court only · No districts selected';

        info.appendChild(title);
        info.appendChild(summary);

        const actions = document.createElement('div');
        actions.className = 'flex items-center gap-2';

        const button = document.createElement('button');
        button.type = 'button';
        button.className =
            'open-district-modal rounded-lg border border-indigo-200 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50';
        button.textContent = 'Select Districts';

        const hiddenFields = document.createElement('div');
        hiddenFields.className = 'district-hidden-fields';

        const modeInput = document.createElement('input');
        modeInput.type = 'hidden';
        modeInput.name = 'district_selection_mode[' + stateId + ']';
        modeInput.value = 'none';

        hiddenFields.appendChild(modeInput);
        actions.appendChild(button);
        actions.appendChild(hiddenFields);
        card.appendChild(info);
        card.appendChild(actions);

        return card;
    }

    async function openModal(stateId, stateName, card) {
        if (!stateId || !card) return;

        const thisRequest = ++requestSequence;
        activeStateId = String(stateId);
        loadingDistricts = true;

        const currentSelection = getCurrentCardSelection(card, stateId);

        modalState.textContent = stateName;
        modalMessage.textContent = 'Loading districts for ' + stateName + '...';
        checkboxList.innerHTML = '';
        districtSearch.value = '';
        districtSearchCount.textContent = '';
        noDistrictResults.classList.add('hidden');
        availableDistricts = [];
        selectAllButton.disabled = true;
        selectAllButton.classList.add('opacity-50', 'cursor-not-allowed');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';

        try {
            const response = await fetch(
                '/api/court-directory/states/' + encodeURIComponent(stateId) + '/districts',
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }
            );

            const result = await response.json();

            if (thisRequest !== requestSequence) return;

            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Unable to load district records.');
            }

            availableDistricts = Array.isArray(result.data)
                ? result.data
                : Array.isArray(result.districts)
                    ? result.districts
                    : Array.isArray(result)
                        ? result
                        : [];

            if (availableDistricts.length === 0) {
                modalMessage.textContent =
                    'No district records were returned for ' + stateName + '.';
                loadingDistricts = false;
                return;
            }

            availableDistricts.forEach(function (district) {
                const isChecked = currentSelection.mode === 'all' ||
                    currentSelection.ids.includes(String(district.id));

                addDistrictCheckbox(district, isChecked);
            });

            selectAllButton.disabled = false;
            selectAllButton.classList.remove('opacity-50', 'cursor-not-allowed');
            filterDistricts();

            modalMessage.textContent =
                availableDistricts.length + ' district records loaded for ' + stateName + '.';

            loadingDistricts = false;

            setTimeout(function () {
                if (thisRequest === requestSequence) districtSearch.focus();
            }, 50);

        } catch (error) {
            if (thisRequest !== requestSequence) return;
            modalMessage.textContent = 'District loading failed: ' + error.message;
            loadingDistricts = false;
        }
    }

    function closeModal() {
        requestSequence++;
        activeStateId = null;
        loadingDistricts = false;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function updateStateSettings() {
        const checkedStates = Array.from(
            form.querySelectorAll('.practice-state-checkbox:checked')
        );

        checkedStates.forEach(function (checkbox) {
            const stateId = checkbox.dataset.stateId;
            if (!stateId || getCard(stateId)) return;

            settingsContainer.appendChild(createStateSetting(stateId, checkbox.value));
        });

        Array.from(settingsContainer.querySelectorAll('.state-district-setting'))
            .forEach(function (card) {
                const stateId = card.dataset.stateId;
                const checkbox = Array.from(
                    form.querySelectorAll('.practice-state-checkbox')
                ).find(item => String(item.dataset.stateId) === String(stateId));

                if (!checkbox || !checkbox.checked) {
                    if (activeStateId === String(stateId)) closeModal();
                    card.remove();
                }
            });

        emptyMessage.hidden =
            settingsContainer.querySelectorAll('.state-district-setting').length > 0;
    }

    function filterStates() {
        const query = stateSearch.value.trim().toLocaleLowerCase();
        let visible = 0;

        document.querySelectorAll('.state-search-item').forEach(function (item) {
            const matches = item.dataset.searchName.includes(query);
            item.hidden = !matches;
            if (matches) visible++;
        });

        stateSearchCount.textContent =
            visible + ' of {{ count($states) }} States / UTs shown';

        noStateResults.classList.toggle('hidden', visible !== 0);
    }

    stateSearch.addEventListener('input', filterStates);
    districtSearch.addEventListener('input', filterDistricts);

    // Save state changes immediately; open the district popup only for newly selected states.
    form.querySelectorAll('.practice-state-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            updateStateSettings();

            if (!checkbox.checked) {
                saveChanges();
                return;
            }

            const stateId = checkbox.dataset.stateId;
            const card = getCard(stateId);

            if (!stateId || !card) {
                setSaveStatus('State catalogue mapping unavailable', 'error');
                return;
            }

            saveChanges();
            openModal(stateId, checkbox.value, card);
        });
    });

    settingsContainer.addEventListener('click', function (event) {
        const button = event.target.closest('.open-district-modal');
        if (!button) return;

        const card = button.closest('.state-district-setting');
        if (card) {
            openModal(card.dataset.stateId, card.dataset.stateName, card);
        }
    });

    // Select All always selects every district, including districts hidden by search.
    selectAllButton.addEventListener('click', function () {
        if (loadingDistricts || !getDistrictCheckboxes().length) return;

        getDistrictCheckboxes().forEach(function (box) {
            box.checked = true;
        });

        persistModalSelection();
    });

    document.getElementById('closeDistrictModal').addEventListener('click', closeModal);
    document.getElementById('cancelDistrictModal').addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    modalPanel.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // This page saves each change immediately; prevent accidental legacy form submission.
    form.addEventListener('submit', function (event) {
        event.preventDefault();
    });

    filterStates();
});
</script>
@endpush