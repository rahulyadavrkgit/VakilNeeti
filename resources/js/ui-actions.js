/**
 * Declarative page actions, so views need no inline event handlers (which a strict
 * Content-Security-Policy blocks). Use these data attributes in Blade:
 *
 * - data-confirm="Message"         on a button/link (click) or a form (submit): asks first.
 * - data-autosubmit                on a select/input: submits its form when changed.
 * - data-print                     on a button: opens the print dialog.
 * - data-autoprint                 on any element: opens the print dialog after the page loads.
 * - data-copy="text"               on a button: copies the text to the clipboard.
 * - data-switch="group" (radio) with data-switch-panel="group:value"  shows the matching panel.
 * - data-filter-list="id" on an input: live-filters [data-filter-item] (and data-filter-also="selector") inside #id.
 * - data-toggle-open="id"          toggles the `open` state of a <details>/<dialog> by id.
 * - data-toggle-display="id"       shows/hides an element by id.
 * - data-vn-close-webview          closes the external webview page.
 * - data-filter-dashboard-table="sourceId"  live table filter in Dashboard Studio.
 */
function callGlobal(name, ...args) {
    if (typeof window[name] === 'function') {
        window[name](...args);
    }
}

document.addEventListener('click', (event) => {
    const confirmTrigger = event.target.closest('[data-confirm]:not(form)');

    if (confirmTrigger && !window.confirm(confirmTrigger.dataset.confirm)) {
        event.preventDefault();
        event.stopImmediatePropagation();

        return;
    }

    const action = event.target.closest(
        '[data-print], [data-toggle-open], [data-toggle-display], [data-vn-close-webview]',
    );

    if (!action) {
        return;
    }

    if (action.hasAttribute('data-print')) {
        window.print();
    } else if (action.dataset.toggleOpen) {
        const target = document.getElementById(action.dataset.toggleOpen);

        if (target) {
            target.open = !target.open;
        }
    } else if (action.dataset.toggleDisplay) {
        const target = document.getElementById(action.dataset.toggleDisplay);

        if (target) {
            target.style.display = target.style.display === 'none' ? 'block' : 'none';
        }
    } else if (action.hasAttribute('data-vn-close-webview')) {
        callGlobal('vnCloseWebview');
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (form instanceof HTMLFormElement && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

document.addEventListener('change', (event) => {
    const field = event.target.closest('[data-autosubmit]');

    if (field?.form) {
        field.form.requestSubmit ? field.form.requestSubmit() : field.form.submit();
    }
});

// Client form: show person or company fields for the chosen client type.
document.addEventListener('change', (event) => {
    const radio = event.target.closest('[data-client-type]');
    const form = radio?.closest('[data-client-form]');

    if (!form) {
        return;
    }

    const group = ['company', 'organisation'].includes(radio.value) ? 'company' : 'person';

    form.querySelectorAll('[data-for-type]').forEach((section) => {
        section.hidden = section.dataset.forType !== group;
    });
    form.querySelectorAll('[data-required-for]').forEach((input) => {
        input.required = input.dataset.requiredFor === group;
    });
});

// Bulk selection: "select all" and enabling the bulk action button.
document.addEventListener('change', (event) => {
    const box = event.target.closest('[data-select-all], [data-select-item]');
    const form = box?.closest('[data-bulk-form]') ?? (box ? document.getElementById(box.getAttribute('form')) : null);

    if (!form) {
        return;
    }

    const items = [...document.querySelectorAll(`[data-select-item][form="${form.id}"], #${form.id} [data-select-item]`)];

    if (box.hasAttribute('data-select-all')) {
        items.forEach((item) => { item.checked = box.checked; });
    }

    const selected = items.filter((item) => item.checked).length;
    document.querySelectorAll(`[data-bulk-submit][form="${form.id}"], #${form.id} [data-bulk-submit]`).forEach((button) => {
        button.disabled = selected === 0;
        const label = button.dataset.bulkLabel || 'Delete selected';
        button.textContent = selected ? `${label} (${selected})` : label;
    });
});

const UPLOADABLE = /\.(pdf|docx?|xlsx?|jpe?g|png)$/i;
const MAX_UPLOAD_FILES = 50;

// "Upload files": send the chosen files straight away.
document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-upload-files]');

    if (input?.files.length && input.form) {
        input.form.submit();
    }
});

// "Upload folder": keep supported files, send each one's folder path so sub-folders are recreated.
document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-upload-folder]');

    if (!input?.files.length || !input.form) {
        return;
    }

    const chosen = [...input.files];
    const supported = chosen.filter((file) => UPLOADABLE.test(file.name)).slice(0, MAX_UPLOAD_FILES);
    const skipped = chosen.length - supported.length;

    if (supported.length === 0) {
        window.alert('This folder has no PDF, Word, Excel, JPG or PNG files to upload.');
        input.value = '';

        return;
    }

    if (skipped > 0 && !window.confirm(`${supported.length} files will be uploaded. ${skipped} unsupported or extra files will be skipped (max ${MAX_UPLOAD_FILES} per upload). Continue?`)) {
        input.value = '';

        return;
    }

    const transfer = new DataTransfer();
    supported.forEach((file) => transfer.items.add(file));
    input.files = transfer.files;

    input.form.querySelectorAll('input[name="paths[]"]').forEach((hidden) => hidden.remove());
    supported.forEach((file) => {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'paths[]';
        hidden.value = file.webkitRelativePath || file.name;
        input.form.append(hidden);
    });

    input.form.submit();
});

document.addEventListener('input', (event) => {
    const field = event.target.closest('[data-filter-dashboard-table]');

    if (field) {
        callGlobal('filterDashboardTable', Number(field.dataset.filterDashboardTable));
    }
});

// Printable pages: an element with data-autoprint opens the print dialog once the page has loaded.
if (document.querySelector('[data-autoprint]')) {
    window.addEventListener('load', () => window.print(), { once: true });
}

// Bar Council enrolment: choosing the State fills in its usual State code, unless the person typed their own.
document.addEventListener('change', (event) => {
    const stateSelect = event.target.closest('[data-bar-state]');
    const codeInput = stateSelect?.closest('[data-bar-registration]')?.querySelector('[data-bar-code]');

    if (!codeInput) {
        return;
    }

    let codes = {};

    try {
        codes = JSON.parse(stateSelect.dataset.barCodes || '{}');
    } catch {
        return;
    }

    const typedOwnCode = codeInput.value !== '' && codeInput.dataset.autoFilled !== codeInput.value;

    if (!typedOwnCode) {
        codeInput.value = codes[stateSelect.value] || '';
        codeInput.dataset.autoFilled = codeInput.value;
    }
});

// Copy buttons: data-copy="text" puts the text on the clipboard and briefly says "Copied".
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');

    if (!button) {
        return;
    }

    try {
        await navigator.clipboard.writeText(button.dataset.copy);
        const original = button.textContent;
        button.textContent = 'Copied';
        setTimeout(() => { button.textContent = original; }, 1500);
    } catch {
        window.prompt('Copy this:', button.dataset.copy);
    }
});

// Switch panels: a radio with data-switch="group" shows the elements marked data-switch-panel="group:value".
document.addEventListener('change', (event) => {
    const radio = event.target.closest('[data-switch]');

    if (!radio) {
        return;
    }

    document.querySelectorAll(`[data-switch-panel^="${radio.dataset.switch}:"]`).forEach((panel) => {
        panel.hidden = panel.dataset.switchPanel !== `${radio.dataset.switch}:${radio.value}`;
    });
});

// Live list filter: an input with data-filter-list="id" hides [data-filter-item] children of #id that do not match.
document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-filter-list]');
    const list = input ? document.getElementById(input.dataset.filterList) : null;

    if (!list) {
        return;
    }

    const query = input.value.trim().toLowerCase();
    let shown = 0;

    const selector = input.dataset.filterAlso ? `[data-filter-item], ${input.dataset.filterAlso}` : '[data-filter-item]';

    list.querySelectorAll(selector).forEach((item) => {
        const matches = query === '' || item.textContent.toLowerCase().includes(query);
        item.hidden = !matches;

        if (matches) {
            shown++;

            if (query !== '' && item instanceof HTMLDetailsElement) {
                item.open = true;
            }
        }
    });

    list.querySelector('[data-filter-empty]')?.toggleAttribute('hidden', shown > 0);
});
