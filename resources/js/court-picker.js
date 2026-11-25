/*
 * Court picker on case forms (resources/views/cases/partials/court-picker.blade.php):
 * court level → State → district, with "Your courts" filling all three at one tap.
 * Listeners sit on the document, so pickers inside pop-up sheets work without starting anything.
 */
const districtCache = new Map();

async function loadDistricts(picker, keepSelected = true) {
    const select = picker.querySelector('[data-court-district]');
    const state = picker.querySelector('[data-court-state]')?.value;

    if (!select) {
        return;
    }

    const wanted = keepSelected ? select.dataset.selected || select.value : '';

    if (!state) {
        select.innerHTML = '<option value="">Choose the State first</option>';

        return;
    }

    if (!districtCache.has(state)) {
        select.innerHTML = '<option value="">Loading…</option>';

        try {
            const response = await fetch(`${picker.dataset.districtsUrl}/${encodeURIComponent(state)}`, { headers: { Accept: 'application/json' } });
            districtCache.set(state, response.ok ? (await response.json()).data || [] : []);
        } catch {
            districtCache.set(state, []);
        }
    }

    const names = districtCache.get(state);
    select.innerHTML = '<option value="">Choose…</option>';
    names.forEach((name) => select.add(new Option(name, name, false, name === wanted)));

    // A district from an older entry that is not in the official list still shows.
    if (wanted && !names.includes(wanted)) {
        select.add(new Option(wanted, wanted, true, true));
    }

    select.dataset.loadedFor = state;
}

function showForLevel(picker) {
    const level = picker.querySelector('[data-court-level]:checked')?.value || '';
    const bench = ['supreme', 'high'].includes(level);

    picker.querySelectorAll('[data-for-level]').forEach((element) => {
        element.hidden = !element.dataset.forLevel.split(' ').includes(level);
        element.classList.remove('hidden');
    });

    const label = picker.querySelector('[data-court-name-label]');
    const hint = picker.querySelector('[data-court-name-hint]');

    if (label) {
        label.textContent = bench ? 'Bench / court no.' : 'Court / bench';
    }

    if (hint) {
        hint.textContent = bench ? '(optional)' : '';
    }
}

document.addEventListener('change', (event) => {
    const picker = event.target.closest('[data-court-picker]');

    if (!picker) {
        return;
    }

    if (event.target.matches('[data-court-level]')) {
        showForLevel(picker);
    }

    if (event.target.matches('[data-court-state]')) {
        loadDistricts(picker, false);
    }

    picker.querySelectorAll('[data-court-choice]').forEach((chip) => chip.setAttribute('aria-pressed', 'false'));
});

// The district list loads when it is first opened (the State was already chosen, e.g. when editing).
document.addEventListener('focusin', (event) => {
    const select = event.target.closest('[data-court-district]');
    const picker = select?.closest('[data-court-picker]');
    const state = picker?.querySelector('[data-court-state]')?.value;

    if (picker && state && select.dataset.loadedFor !== state) {
        loadDistricts(picker);
    }
});

document.addEventListener('click', async (event) => {
    const chip = event.target.closest('[data-court-choice]');
    const picker = chip?.closest('[data-court-picker]');

    if (!picker) {
        return;
    }

    const levelInput = picker.querySelector(`[data-court-level][value="${chip.dataset.level}"]`);

    if (levelInput) {
        levelInput.checked = true;
    }

    const state = picker.querySelector('[data-court-state]');

    if (state) {
        state.value = chip.dataset.state || '';
    }

    const district = picker.querySelector('[data-court-district]');

    if (district) {
        district.dataset.selected = chip.dataset.district || '';
    }

    showForLevel(picker);
    await loadDistricts(picker);

    picker.querySelectorAll('[data-court-choice]').forEach((other) => other.setAttribute('aria-pressed', other === chip ? 'true' : 'false'));
    picker.querySelector('input[name="court_name"]')?.focus();
});
