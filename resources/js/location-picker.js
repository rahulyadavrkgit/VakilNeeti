/**
 * Country → State → district → city (→ court) picker shared by every address form.
 *
 * Markup contract (all inside one element with [data-location-picker]):
 * - data-districts-url on the container: base URL, "/{stateId}/districts" is appended.
 * - data-cities-url on the container (optional): base URL, "/{districtId}/cities" is appended.
 * - Country (optional): input[data-location-country-input] (submitted, name="country") +
 *   select[data-location-country-select] (unnamed). Any country other than India turns
 *   State / district / city into free text; that needs input[data-location-state-input]
 *   (same name as the State select, disabled by default).
 * - select[data-location-state]: options carry data-state-id, data-state-code, data-gst-code, data-high-court.
 * - [data-location-state-code] / [data-location-gst-code]: inputs or elements that show the codes.
 * - District: input[data-location-district-input] (submitted, name="district") +
 *   select[data-location-district-select] (unnamed list) + optional [data-location-district-hint].
 * - City (optional): input[data-location-city-input] (submitted, name="city") +
 *   select[data-location-city-select] (unnamed list).
 * - Court (optional): input[data-location-court-input] (submitted, name="court") +
 *   select[data-location-court-select] (unnamed list).
 */
const OTHER = '__other__';
const INDIA = 'India';

function setDisplayValue(element, value) {
    if ('value' in element) {
        element.value = value;
    } else {
        element.textContent = value || '—';
    }
}

/**
 * A dropdown backed by a text input. The input is what the form submits; the dropdown fills it,
 * and an "other" option reveals the input for free typing.
 */
function createChoiceField(select, input, { otherLabel, onChange }) {
    if (!select || !input) {
        return null;
    }

    const isRequired = input.required;

    function showInput(show) {
        input.hidden = !show;
        input.required = show && isRequired;
    }

    select.addEventListener('change', () => {
        if (select.value === OTHER) {
            select.required = false;
            input.value = '';
            showInput(true);
            input.focus();
        } else {
            select.required = isRequired;
            input.value = select.value;
            showInput(false);
        }

        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    if (onChange) {
        input.addEventListener('change', onChange);
    }

    return {
        input,

        /** Disabled dropdown with a single message, e.g. "Select a state first". */
        waitFor(message) {
            select.replaceChildren(new Option(message, ''));
            select.disabled = true;
            select.hidden = false;
            select.required = false;
            showInput(false);
        },

        /** Free typing only (no list available). */
        typeOnly() {
            select.hidden = true;
            select.disabled = true;
            select.required = false;
            showInput(true);
        },

        /** Dropdown of choices plus an "other" option; keeps the current value when possible. */
        choose(placeholder, names) {
            const currentValue = input.value.trim();

            select.replaceChildren(
                new Option(placeholder, ''),
                ...names.map((name) => new Option(name, name)),
                new Option(otherLabel, OTHER),
            );
            select.disabled = false;
            select.hidden = false;

            if (currentValue && names.includes(currentValue)) {
                select.value = currentValue;
                select.required = isRequired;
                showInput(false);
            } else if (currentValue) {
                select.value = OTHER;
                select.required = false;
                showInput(true);
            } else {
                select.value = '';
                select.required = isRequired;
                showInput(false);
            }
        },

        clear() {
            input.value = '';
        },
    };
}

async function fetchList(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });

    if (!response.ok) {
        throw new Error(`List request failed (${response.status}): ${url}`);
    }

    const payload = await response.json();

    return Array.isArray(payload.data) ? payload.data : [];
}

function initLocationPicker(container) {
    const stateSelect = container.querySelector('[data-location-state]');

    if (!stateSelect) {
        return;
    }

    const stateTextInput = container.querySelector('[data-location-state-input]');
    const districtInput = container.querySelector('[data-location-district-input]');
    const districtHint = container.querySelector('[data-location-district-hint]');
    const courtInput = container.querySelector('[data-location-court-input]');
    const { districtsUrl, citiesUrl } = container.dataset;
    const districtIdByName = new Map();
    let latestDistrictRequest = 0;
    let latestCityRequest = 0;

    const district = createChoiceField(
        container.querySelector('[data-location-district-select]'),
        districtInput,
        { otherLabel: 'My district is not listed' },
    );
    const city = createChoiceField(
        container.querySelector('[data-location-city-select]'),
        container.querySelector('[data-location-city-input]'),
        { otherLabel: 'Other city / town' },
    );
    const court = createChoiceField(
        container.querySelector('[data-location-court-select]'),
        courtInput,
        { otherLabel: 'Other court / tribunal' },
    );
    const country = createChoiceField(
        container.querySelector('[data-location-country-select]'),
        container.querySelector('[data-location-country-input]'),
        { otherLabel: 'Other country', onChange: () => applyCountry(true) },
    );

    function isIndia() {
        return !country || (country.input.value.trim() || INDIA) === INDIA;
    }

    function setHint(message) {
        if (districtHint) {
            districtHint.textContent = message;
            districtHint.hidden = message === '';
        }
    }

    function refreshCourts() {
        if (!court) {
            return;
        }

        if (!stateSelect.value) {
            court.waitFor('Select a state first');

            return;
        }

        const highCourt = stateSelect.selectedOptions[0]?.dataset.highCourt;
        const districtName = districtInput?.value.trim();
        const courts = ['Supreme Court of India'];

        if (highCourt) {
            courts.push(highCourt);
        }

        if (districtName) {
            courts.push(`District & Sessions Court, ${districtName}`, `Family Court, ${districtName}`);
        }

        court.choose('Select court', courts);
    }

    async function loadCities() {
        if (!city) {
            return;
        }

        const requestNumber = ++latestCityRequest;

        if (!isIndia()) {
            city.typeOnly();

            return;
        }

        if (!stateSelect.value) {
            city.waitFor('Select a state first');

            return;
        }

        const districtName = districtInput?.value.trim();

        if (!districtName) {
            city.waitFor('Select a district first');

            return;
        }

        const districtId = districtIdByName.get(districtName);

        if (!districtId || !citiesUrl) {
            city.typeOnly();

            return;
        }

        city.waitFor('Loading cities…');

        try {
            const names = [...new Set((await fetchList(`${citiesUrl}/${encodeURIComponent(districtId)}/cities`)).map((item) => item.name))]
                .filter(Boolean)
                .sort((first, second) => first.localeCompare(second));

            if (requestNumber !== latestCityRequest) {
                return;
            }

            names.length ? city.choose('Select city / town', names) : city.typeOnly();
        } catch (error) {
            if (requestNumber === latestCityRequest) {
                console.error(error);
                city.typeOnly();
            }
        }
    }

    async function loadDistricts() {
        const requestNumber = ++latestDistrictRequest;
        const stateId = stateSelect.selectedOptions[0]?.dataset.stateId;

        districtIdByName.clear();
        setHint('');

        if (!district) {
            loadCities();

            return;
        }

        if (!stateSelect.value) {
            district.waitFor('Select a state first');
            loadCities();

            return;
        }

        if (!stateId || !districtsUrl) {
            district.typeOnly();
            setHint('Type your district.');
            loadCities();

            return;
        }

        district.waitFor('Loading districts…');

        try {
            const items = await fetchList(`${districtsUrl}/${encodeURIComponent(stateId)}/districts`);

            if (requestNumber !== latestDistrictRequest) {
                return;
            }

            items.forEach((item) => {
                if (item.district_name && !districtIdByName.has(item.district_name)) {
                    districtIdByName.set(item.district_name, item.id);
                }
            });

            const names = [...districtIdByName.keys()].sort((first, second) => first.localeCompare(second));

            if (names.length === 0) {
                district.typeOnly();
                setHint('Type your district.');
            } else {
                district.choose('Select district', names);
            }
        } catch (error) {
            if (requestNumber !== latestDistrictRequest) {
                return;
            }

            console.error(error);
            district.typeOnly();
            setHint('Could not load districts. Type your district.');
        }

        refreshCourts();
        loadCities();
    }

    function updateCodes() {
        const selectedOption = stateSelect.selectedOptions[0];

        container.querySelectorAll('[data-location-state-code]').forEach((element) => {
            setDisplayValue(element, selectedOption?.dataset.stateCode || '');
        });
        container.querySelectorAll('[data-location-gst-code]').forEach((element) => {
            setDisplayValue(element, selectedOption?.dataset.gstCode || '');
        });
    }

    /** India uses the stored lists; any other country uses free-text State / district / city. */
    function applyCountry(countryChanged) {
        const india = isIndia();

        stateSelect.hidden = !india;
        stateSelect.disabled = !india;

        if (stateTextInput) {
            stateTextInput.hidden = india;
            stateTextInput.disabled = india;
        }

        if (countryChanged) {
            stateSelect.value = '';

            if (stateTextInput) {
                stateTextInput.value = '';
            }

            district?.clear();
            city?.clear();
        }

        if (india) {
            updateCodes();
            refreshCourts();
            loadDistricts();
        } else {
            district?.typeOnly();
            city?.typeOnly();
            setHint('');
        }
    }

    stateSelect.addEventListener('change', () => {
        district?.clear();
        city?.clear();
        court?.clear();
        updateCodes();
        refreshCourts();
        loadDistricts();
    });

    districtInput?.addEventListener('change', () => {
        if (courtInput && /^(District & Sessions Court|Family Court), /.test(courtInput.value)) {
            court.clear();
        }

        city?.clear();
        refreshCourts();
        loadCities();
    });

    country?.choose('Select country', [INDIA]);
    applyCountry(false);
}

function initAllLocationPickers() {
    document.querySelectorAll('[data-location-picker]').forEach(initLocationPicker);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllLocationPickers);
} else {
    initAllLocationPickers();
}
