/**
 * Declarative page actions, so views need no inline event handlers (which a strict
 * Content-Security-Policy blocks). Use these data attributes in Blade:
 *
 * - data-confirm="Message"         on a button/link (click) or a form (submit): asks first.
 * - data-autosubmit                on a select/input: submits its form when changed.
 * - data-print                     on a button: opens the print dialog.
 * - data-autoprint                 on any element: opens the print dialog after the page loads.
 * - data-copy="text"               on a button: copies the text to the clipboard.
 * - data-resize-image="1200"       on an image file input: shrinks the photo before upload.
 * - data-switch="group" (radio) with data-switch-panel="group:value"  shows the matching panel.
 * - data-filter-list="id" on an input: live-filters [data-filter-item] (and data-filter-also="selector") inside #id.
 * - data-toggle-open="id"          toggles the `open` state of a <details>/<dialog> by id.
 * - data-toggle-display="id"       shows/hides an element by id.
 * - data-filter-dashboard-table="sourceId"  live table filter in Dashboard Studio.
 */
function callGlobal(name, ...args) {
    if (typeof window[name] === 'function') {
        window[name](...args);
    }
}

/**
 * One in-app confirmation box for the whole site (instead of the browser's own grey pop-up).
 * The button says what will happen ("Delete", "Remove"…) and is red only when something is destroyed.
 */
let confirmDialog = null;

function vnConfirm(message) {
    if (!confirmDialog) {
        confirmDialog = document.createElement('dialog');
        confirmDialog.className = 'vn-centered-dialog w-[min(24rem,calc(100vw-2rem))] rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-black/50';
        confirmDialog.innerHTML = `
            <form method="dialog" class="p-5">
                <p data-confirm-message class="text-base font-semibold leading-snug"></p>
                <div class="mt-5 flex justify-end gap-2">
                    <button value="cancel" class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button value="ok" data-confirm-ok class="min-h-11 rounded-xl px-4 text-sm font-semibold text-white"></button>
                </div>
            </form>`;
        document.body.append(confirmDialog);
    }

    const destroys = /\b(delete|remove|write off|unlink|clear|cover)\b/i.test(message);
    const verb = message.match(/^(Delete|Remove|Write off|Unlink|Close|Clear|Cover|Mark|Add|Send|Cancel)\b/i)?.[0];
    const okButton = confirmDialog.querySelector('[data-confirm-ok]');

    confirmDialog.querySelector('[data-confirm-message]').textContent = message;
    okButton.textContent = verb ? verb.charAt(0).toUpperCase() + verb.slice(1).toLowerCase() : 'Continue';
    okButton.className = `min-h-11 rounded-xl px-4 text-sm font-semibold text-white ${destroys ? 'bg-red-600 hover:bg-red-700' : 'bg-[#16776e] hover:bg-[#125f58]'}`;
    confirmDialog.returnValue = 'cancel';
    confirmDialog.showModal();
    okButton.focus();

    return new Promise((resolve) => {
        confirmDialog.addEventListener('close', () => resolve(confirmDialog.returnValue === 'ok'), { once: true });
    });
}

window.vnConfirm = vnConfirm;

document.addEventListener('click', (event) => {
    const confirmTrigger = event.target.closest('[data-confirm]:not(form)');

    if (confirmTrigger && confirmTrigger.dataset.vnConfirmed !== '1') {
        event.preventDefault();
        event.stopImmediatePropagation();

        vnConfirm(confirmTrigger.dataset.confirm).then((ok) => {
            if (ok) {
                // Run the action as if confirmed: the flag lets this one click through.
                confirmTrigger.dataset.vnConfirmed = '1';
                confirmTrigger.click();
                delete confirmTrigger.dataset.vnConfirmed;
            }
        });

        return;
    }

    const action = event.target.closest(
        '[data-print], [data-toggle-open], [data-toggle-display]',
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
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.vnConfirmed === '1') {
        return;
    }

    event.preventDefault();
    const submitter = event.submitter;

    vnConfirm(form.dataset.confirm).then((ok) => {
        if (ok) {
            form.dataset.vnConfirmed = '1';
            form.requestSubmit ? form.requestSubmit(submitter || undefined) : form.submit();
            delete form.dataset.vnConfirmed;
        }
    });
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
        // Icon buttons keep their icon: the label inside changes, or the whole button shows "Copied" briefly.
        const label = button.querySelector('[data-copy-label]');
        const target = label || button;
        const original = target.innerHTML;
        target.textContent = 'Copied';
        button.classList.add('is-copied');
        setTimeout(() => { target.innerHTML = original; button.classList.remove('is-copied'); }, 1500);
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
    const room = query.match(/^(?:(?:court|room)\s*(?:no\.?\s*)?[:.-]?\s*)?(r?\d+)$/i)?.[1];
    const roomKey = (number) => String(number).toLowerCase().replace(/^(r?)0+(?=\d)/, '$1');
    let shown = 0;

    const selector = input.dataset.filterAlso ? `[data-filter-item], ${input.dataset.filterAlso}` : '[data-filter-item]';

    list.querySelectorAll(selector).forEach((item) => {
        const matches = item.hasAttribute('data-filter-room') && room
            ? roomKey(item.dataset.filterRoom) === roomKey(room)
            : (item.dataset.filterText ?? item.textContent).toLowerCase().includes(query);
        item.hidden = !matches;

        if (matches) {
            shown++;

            if (query !== '' && item instanceof HTMLDetailsElement) {
                item.open = true;
            }
        }
    });

    list.querySelectorAll('[data-filter-group]').forEach((group) => {
        group.hidden = !group.querySelector('[data-filter-item]:not([hidden])');
    });
    const count = list.querySelector('[data-filter-count]');
    if (count) {
        count.textContent = shown === 1 ? '1 room' : `${shown} rooms`;
    }
    list.querySelector('[data-filter-empty]')?.toggleAttribute('hidden', shown > 0);
});

// Image uploads: data-resize-image="1200" shrinks a chosen photo in the browser (longest side 1200 px, JPEG)
// so large phone photos stay under the server's upload limit. data-image-preview="id" shows it in an <img>.
document.addEventListener('change', async (event) => {
    const input = event.target.closest('input[type="file"][data-resize-image]');
    const file = input?.files?.[0];

    if (!file || !file.type.startsWith('image/')) {
        return;
    }

    const maxSide = Number(input.dataset.resizeImage) || 1200;
    const preview = input.dataset.imagePreview ? document.getElementById(input.dataset.imagePreview) : null;

    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));

        if (scale < 1 || file.size > 1024 * 1024) {
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(bitmap.width * scale);
            canvas.height = Math.round(bitmap.height * scale);
            canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.86));

            if (blob && blob.size < file.size) {
                const resized = new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
                const transfer = new DataTransfer();
                transfer.items.add(resized);
                input.files = transfer.files;
            }
        }

        bitmap.close?.();
    } catch {
        // The browser could not read this image; upload it as chosen and let the server check it.
    }

    if (preview && input.files[0]) {
        preview.src = URL.createObjectURL(input.files[0]);
        preview.hidden = false;
    }
});

// Home: live court display boards. Shows a board inside VakilNiti when its court allows framing;
// otherwise opens it in one small reusable window beside VakilNiti. The person can hide the panel
// and choose which boards to list; both choices and the last court are saved on this device.
const boardStore = {
    get(key, fallback) {
        try {
            const value = localStorage.getItem(key);

            return value === null ? fallback : JSON.parse(value);
        } catch {
            return fallback;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch {}
    },
};

document.querySelectorAll('[data-board-picker]').forEach((picker, pickerIndex) => {
    const select = picker.querySelector('[data-board-select]');
    const search = picker.querySelector('[data-board-search]');
    const frameWrap = picker.querySelector('[data-board-frame-wrap]');
    const frame = picker.querySelector('[data-board-frame]');
    const blocked = picker.querySelector('[data-board-blocked]');
    const name = picker.querySelector('[data-board-name]');
    const manage = picker.querySelector('[data-board-manage]');
    const manageToggle = picker.querySelector('[data-board-manage-toggle]');
    const hideButton = picker.querySelector('[data-board-hide]');
    const choices = [...picker.querySelectorAll('[data-board-choice]')];

    if (!select) {
        return;
    }

    // Every board, kept aside so the list can be rebuilt (iPhone Safari ignores hidden options).
    const allBoards = [...select.querySelectorAll('optgroup')].flatMap((group) => [...group.children].map((option) => ({
        group: group.label,
        option,
    })));
    const options = allBoards.map((board) => board.option);
    let chosen = boardStore.get('vn-board-choices', null);

    choices.forEach((box) => {
        box.checked = !Array.isArray(chosen) || chosen.includes(box.value);
    });

    const show = () => {
        const option = select.selectedOptions[0];

        if (!option) {
            name.textContent = 'No board chosen. Use Manage to pick your courts.';
            comboLabel.textContent = 'No board selected';
            frameWrap.hidden = true;
            blocked.hidden = false;
            picker.querySelector('[data-board-open-actions]').hidden = true;
            loadRooms(null);

            return;
        }

        picker.querySelector('[data-board-open-actions]').hidden = false;
        name.textContent = option.dataset.label;
        comboLabel.textContent = option.dataset.label;
        const embeddable = option.dataset.embeddable === '1';
        frameWrap.hidden = !embeddable;
        blocked.hidden = embeddable;
        loadRooms(embeddable ? option.value : null);

        if (embeddable && frame.src !== option.value) {
            frame.src = option.value;
        }

        boardStore.set('vn-display-board', option.value);
    };

    const combo = picker.querySelector('[data-board-combo]');
    const comboToggle = combo.querySelector('[data-board-combo-toggle]');
    const comboPanel = combo.querySelector('[data-board-combo-panel]');
    const comboList = combo.querySelector('[data-board-combo-list]');
    const comboLabel = combo.querySelector('[data-board-combo-label]');
    const comboEmpty = combo.querySelector('[data-board-combo-empty]');
    comboPanel.id = `board-dropdown-${pickerIndex}`;
    comboToggle.setAttribute('aria-controls', comboPanel.id);
    comboList.id = `board-matches-${pickerIndex}`;
    search.setAttribute('aria-controls', comboList.id);
    let matches = [];
    let activeMatch = -1;

    const highlightMatch = (index, scroll = false) => {
        matches[activeMatch]?.setAttribute('aria-selected', 'false');
        activeMatch = index;
        const match = matches[index];

        if (match) {
            match.setAttribute('aria-selected', 'true');
            search.setAttribute('aria-activedescendant', match.id);
            if (scroll) {
                match.scrollIntoView({ block: 'nearest' });
            }
        } else {
            search.removeAttribute('aria-activedescendant');
        }
    };

    const renderCombo = () => {
        const terms = search.value.toLowerCase().replace(/\bhc\b/g, 'high court').trim().split(/\s+/).filter(Boolean);
        matches = [...select.options].filter((option) => terms.every((term) => option.dataset.label.toLowerCase().includes(term)))
            .map((option, index) => {
                const item = document.createElement('li');
                item.id = `${comboList.id}-${index}`;
                item.dataset.value = option.value;
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', 'false');
                item.className = 'cursor-pointer px-3 py-2 text-slate-800 hover:bg-teal-50 aria-selected:bg-teal-50 aria-selected:font-semibold aria-selected:text-[#16776e]';
                item.textContent = option.dataset.label;
                return item;
            });
        comboList.replaceChildren(...matches);
        comboList.hidden = matches.length === 0;
        comboEmpty.hidden = matches.length > 0;
        highlightMatch(matches.length ? 0 : -1);
    };

    const setComboOpen = (open) => {
        comboPanel.hidden = !open;
        comboToggle.setAttribute('aria-expanded', String(open));
        search.setAttribute('aria-expanded', String(open));
        if (open) {
            renderCombo();
        } else {
            search.removeAttribute('aria-activedescendant');
        }
    };

    const chooseBoard = (match) => {
        select.value = match.dataset.value;
        search.value = '';
        setComboOpen(false);
        comboToggle.focus();
        select.dispatchEvent(new Event('change'));
    };

    comboToggle.addEventListener('click', () => {
        const open = comboPanel.hidden;
        search.value = '';
        setComboOpen(open);
        if (open) {
            search.focus();
        }
    });
    search.addEventListener('input', renderCombo);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            setComboOpen(false);
            comboToggle.focus();
        } else if (event.key === 'Enter' && !comboPanel.hidden && matches[activeMatch]) {
            event.preventDefault();
            chooseBoard(matches[activeMatch]);
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (comboPanel.hidden) {
                setComboOpen(true);
            } else if (matches.length) {
                const step = event.key === 'ArrowDown' ? 1 : -1;
                highlightMatch((activeMatch + step + matches.length) % matches.length, true);
            }
        }
    });
    comboList.addEventListener('mousedown', (event) => event.preventDefault());
    comboList.addEventListener('click', (event) => {
        const choice = event.target.closest('[data-value]');
        if (choice) {
            chooseBoard(choice);
        }
    });
    combo.addEventListener('focusout', (event) => {
        if (!combo.contains(event.relatedTarget)) {
            setComboOpen(false);
        }
    });
    document.addEventListener('click', (event) => {
        if (!combo.contains(event.target)) {
            setComboOpen(false);
        }
    });

    const roomsSearch = picker.querySelector('[data-board-rooms-search]');
    const roomsStatus = picker.querySelector('[data-board-rooms-status]');
    let roomsBoard = null;
    let roomsRequest;
    let chosenRooms = [];
    let roomLabels = new Map();

    const boardSlug = (url) => (String(url || '').match(/\/live-board\/([a-z-]+)/) || [])[1] || null;
    const sendRooms = () => frame.contentWindow?.postMessage({ type: 'vn-board-rooms', rooms: chosenRooms.join(',') }, window.location.origin);
    const shortName = (name) => String(name).replace(/\b(?:court|room)(?:\s+no\.?)?\s*[:.-]?\s*/gi, '').trim();
    const roomKey = (name) => shortName(name).toLowerCase().replace(/[-.\s]/g, '').replace(/^(r?)0+(?=\d)/, '$1');
    const showRoomStatus = (message) => {
        roomsStatus.textContent = message;
        roomsStatus.hidden = !message;
    };

    const filterRooms = () => {
        const terms = shortName(roomsSearch.value).toLowerCase().replace(/\br\s+(?=\d)/g, 'r')
            .split(/[\s,;]+/).filter(Boolean).map(roomKey);
        const matchingRooms = [...new Set(terms.map((term) => roomLabels.get(term)).filter(Boolean))];
        const unknownRoom = terms.some((term) => !roomLabels.has(term));
        showRoomStatus(unknownRoom ? 'Check the court numbers you entered.' : '');

        if (!terms.length || matchingRooms.length) {
            chosenRooms = matchingRooms;
            boardStore.set('vn-board-rooms:' + roomsBoard, chosenRooms.join(','));
            sendRooms();
        }
    };

    const loadRooms = (url) => {
        const slug = boardSlug(url);

        if (!roomsSearch || (slug && slug === roomsBoard)) {
            return;
        }

        roomsRequest?.abort();
        roomsBoard = slug;
        roomLabels = new Map();
        roomsSearch.disabled = true;
        roomsSearch.placeholder = slug ? '1, 2, R1' : 'External board';
        chosenRooms = slug ? String(boardStore.get('vn-board-rooms:' + slug, '')).split(',').filter(Boolean) : [];
        roomsSearch.value = chosenRooms.map(shortName).join(', ');
        showRoomStatus('');

        if (!slug) {
            return;
        }

        const request = new AbortController();
        roomsRequest = request;

        fetch(new URL(url, window.location.origin).pathname.replace(/\/$/, '') + '/data', { headers: { Accept: 'application/json' }, signal: request.signal })
            .then((response) => response.ok ? response.json() : null)
            .catch(() => null)
            .then((board) => {
                if (request.signal.aborted) {
                    return;
                }

                if (!board?.rooms?.length) {
                    roomsSearch.placeholder = 'Rooms unavailable';
                    return;
                }

                board.rooms.forEach((room) => {
                    roomLabels.set(roomKey(room.label), room.label);
                    roomLabels.set(roomKey(room.room), room.label);
                });
                roomsSearch.value = chosenRooms.filter((room) => roomLabels.has(roomKey(room))).map(shortName).join(', ');
                roomsSearch.disabled = false;
                filterRooms();
            });
    };

    if (roomsSearch) {
        roomsStatus.id = `board-room-status-${pickerIndex}`;
        roomsSearch.setAttribute('aria-describedby', roomsStatus.id);
        roomsSearch.addEventListener('input', filterRooms);
        frame.addEventListener('load', sendRooms);
    }

    // The list holds the chosen boards (Manage); the drop-down's search only narrows what it shows.
    const applyFilters = () => {
        const picked = choices.filter((box) => box.checked).map((box) => box.value);

        const current = select.value;
        const groups = new Map();

        allBoards.forEach(({ group, option }) => {
            const visible = picked.includes(option.value);

            if (visible) {
                if (!groups.has(group)) {
                    const element = document.createElement('optgroup');
                    element.label = group;
                    groups.set(group, element);
                }

                groups.get(group).append(option);
            }
        });

        select.replaceChildren(...groups.values());

        if ([...select.options].some((option) => option.value === current)) {
            select.value = current;
        }

        show();
        if (!comboPanel.hidden) {
            renderCombo();
        }
    };

    // A board asked for in the address (?board=…, from search) wins over the one saved last time.
    const asked = new URLSearchParams(window.location.search).get('board');
    const saved = asked && options.some((option) => option.value === asked) ? asked : boardStore.get('vn-display-board', null);

    if (saved && options.some((option) => option.value === saved)) {
        select.value = saved;
    }

    const setHidden = (hidden) => {
        picker.querySelectorAll('[data-board-body]').forEach((element) => {
            element.hidden = hidden;
        });
        picker.querySelector('[data-board-collapsed]').hidden = !hidden;
        hideButton.textContent = hidden ? 'Show' : 'Hide';
        hideButton.setAttribute('aria-expanded', String(!hidden));
        boardStore.set('vn-boards-hidden', hidden);
    };

    const openBoard = (fullScreen) => {
        const width = fullScreen ? window.screen.availWidth : Math.min(1100, window.screen.availWidth - 40);
        const height = fullScreen ? window.screen.availHeight : Math.min(760, window.screen.availHeight - 60);
        const left = fullScreen ? 0 : Math.max(0, window.screen.availWidth - width - 20);
        window.open(select.value, 'vn-display-board', `popup=yes,width=${width},height=${height},left=${left},top=${fullScreen ? 0 : 40}`)?.focus();
    };

    select.addEventListener('change', () => {
        show();

        if (select.selectedOptions[0]?.dataset.embeddable !== '1') {
            openBoard(false);
        }
    });

    // Embedded boards: three heights, drag to resize, and full screen.
    const box = picker.querySelector('[data-board-frame-box]');
    const exitButton = picker.querySelector('[data-board-exit]');
    const heights = { s: 320, m: 480, l: 720 };
    const setSize = (size) => {
        box.style.height = Math.min(heights[size] || heights.m, Math.round(window.innerHeight * 0.85)) + 'px';
        picker.querySelectorAll('[data-board-size]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.boardSize === size));
        });
        boardStore.set('vn-board-size', size);
    };

    picker.querySelectorAll('[data-board-size]').forEach((button) => {
        button.addEventListener('click', () => setSize(button.dataset.boardSize));
    });
    // On the display boards page the board is the page: larger by default and never hidden.
    const isBoardPage = picker.hasAttribute('data-board-page');
    setSize(boardStore.get('vn-board-size', isBoardPage ? 'l' : 'm'));

    const leaveFullScreen = () => {
        box.classList.remove('vn-board-fullscreen');
        exitButton.hidden = true;

        if (document.fullscreenElement === box) {
            document.exitFullscreen?.();
        }
    };

    picker.querySelector('[data-board-fullscreen]').addEventListener('click', () => {
        exitButton.hidden = false;

        if (box.requestFullscreen) {
            box.requestFullscreen().catch(() => box.classList.add('vn-board-fullscreen'));
        } else {
            box.classList.add('vn-board-fullscreen');
        }
    });

    exitButton.addEventListener('click', leaveFullScreen);
    document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) {
            exitButton.hidden = !box.classList.contains('vn-board-fullscreen');
        }
    });
    search?.addEventListener('input', renderCombo);
    hideButton.addEventListener('click', () => setHidden(hideButton.textContent === 'Hide'));

    manageToggle.addEventListener('click', () => {
        manage.hidden = !manage.hidden;
        manageToggle.setAttribute('aria-expanded', String(!manage.hidden));
    });

    choices.forEach((box) => box.addEventListener('change', () => {
        boardStore.set('vn-board-choices', choices.filter((item) => item.checked).map((item) => item.value));
        applyFilters();
    }));

    picker.querySelector('[data-board-choose-all]').addEventListener('click', () => {
        choices.forEach((box) => {
            box.checked = true;
        });
        boardStore.set('vn-board-choices', null);
        applyFilters();
    });

    picker.querySelector('[data-board-manage-done]').addEventListener('click', () => {
        manage.hidden = true;
        manageToggle.setAttribute('aria-expanded', 'false');
    });

    picker.querySelectorAll('[data-board-open]').forEach((button) => {
        button.addEventListener('click', () => openBoard(button.dataset.boardOpen === 'full'));
    });

    applyFilters();
    setHidden(!isBoardPage && boardStore.get('vn-boards-hidden', false) === true);
});

// Appearance: the account menu's Same as device / Light / Dark buttons, and the guest header's moon button.
const themeChoice = () => document.documentElement.dataset.theme || 'system';
const markThemeButtons = () => {
    document.querySelectorAll('[data-theme-option]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.themeOption === themeChoice()));
    });
};

document.querySelectorAll('[data-theme-switch]').forEach((group) => {
    group.addEventListener('click', (event) => {
        const button = event.target.closest('[data-theme-option]');

        if (!button) {
            return;
        }

        window.vnSetTheme?.(button.dataset.themeOption);
        markThemeButtons();

        const token = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value;

        fetch(group.dataset.saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token || '' },
            body: JSON.stringify({ theme: button.dataset.themeOption }),
        }).catch(() => {});
    });
});

document.querySelectorAll('[data-theme-cycle]').forEach((button) => {
    button.addEventListener('click', () => {
        window.vnSetTheme?.(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
    });
});

markThemeButtons();

// Today's hearings in courts with a live board (x-court-info): "Court 12 · now item 27". Each board is
// fetched once per page and refreshed every 10 seconds while the page is open; the server caches it for everyone.
const liveChips = [...document.querySelectorAll('[data-live-chip]')];

if (liveChips.length) {
    const caseKey = (text) => {
        const match = String(text || '').match(/(\d+)\s*(?:\/|of)\s*(\d{4})/i);

        return match ? match[1] + '/' + match[2] : null;
    };

    const fill = (chip, board) => {
        const room = board.rooms.find((item) => item.room === chip.dataset.room);
        const text = chip.querySelector('[data-live-chip-text]');
        if (chip.hasAttribute('data-live-item-only')) {
            const item = board.ok && room?.status === 'running' ? room.item : null;
            const shown = text.textContent.trim();
            text.textContent = item || '*';

            // Yellow while the court sits, orange once this hearing's item is 7 or fewer away, red when it is on.
            const number = (value) => {
                const match = String(value || '').match(/\d+(?:\.\d+)?/);

                return match ? parseFloat(match[0]) : null;
            };
            const left = item && number(chip.dataset.myItem) !== null && number(item) !== null ? number(chip.dataset.myItem) - number(item) : null;
            chip.toggleAttribute('data-live-running', Boolean(item));
            chip.toggleAttribute('data-near', left !== null && left > 0 && left <= 7);
            chip.toggleAttribute('data-mine-now', left === 0);

            // A short glow when the number moves on.
            if (item && shown !== item) {
                chip.classList.remove('vn-item-changed');
                void chip.offsetWidth;
                chip.classList.add('vn-item-changed');
            }

            return;
        }

        // No board to show (the court's data cannot be reached): leave the line as it was.
        chip.hidden = board.rooms.length === 0;
        const isMineNow = room?.status === 'running' && caseKey(chip.dataset.caseNumber) !== null
            && caseKey(chip.dataset.caseNumber) === caseKey(room.case_number);

        // The hearing strip's "Now" cell (data-live-compact) shows just the value.
        const compact = chip.hasAttribute('data-live-compact');

        if (!room) {
            text.textContent = compact ? 'Live' : 'Live board';
        } else if (isMineNow) {
            text.textContent = compact ? room.item + ' · yours' : 'Your case is on now · item ' + room.item;
        } else if (room.status === 'running') {
            text.textContent = compact ? room.item : 'Now item ' + room.item;
        } else if (room.status === 'exhausted') {
            text.textContent = compact ? 'Over' : 'Board over';
        } else {
            text.textContent = compact ? 'Not yet' : 'Not sitting yet';
        }

        chip.toggleAttribute('data-mine-now', isMineNow);
        chip.toggleAttribute('data-live-running', room?.status === 'running' && !isMineNow);

        if (room?.status === 'running' && chip.dataset.lastItem !== room.item) {
            if (chip.dataset.lastItem) {
                chip.classList.remove('vn-item-changed');
                void chip.offsetWidth;
                chip.classList.add('vn-item-changed');
            }

            chip.dataset.lastItem = room.item;
        }

        chip.title = (room ? room.label + ': ' + text.textContent : 'Live display board')
            + (board.ok ? '' : ' (court site not answering, last known board)');
    };

    const sources = [...new Set(liveChips.map((chip) => chip.dataset.source))];
    const refreshChips = () => {
        if (document.hidden) {
            return;
        }

        sources.forEach((source) => {
            fetch(source, { headers: { Accept: 'application/json' } })
                .then((response) => (response.ok ? response.json() : null))
                .catch(() => null)
                .then((board) => {
                    liveChips.filter((chip) => chip.dataset.source === source).forEach((chip) => {
                        if (board) {
                            fill(chip, board);
                        } else if (chip.hasAttribute('data-live-item-only')) {
                            fill(chip, { rooms: [], ok: false });
                        }
                    });
                });
        });
    };

    refreshChips();
    setInterval(refreshChips, 10000);
    document.addEventListener('visibilitychange', refreshChips);
}

// Collections with many similar items: data-view-toggle="id" data-view="grid|list" switches #id
// between a tile grid and a compact list, remembered per collection on this device.
document.querySelectorAll('[data-view-toggle]').forEach((button) => {
    const target = document.getElementById(button.dataset.viewToggle);

    if (!target) {
        return;
    }

    const key = 'vn-view:' + button.dataset.viewToggle;
    const apply = (view) => {
        target.classList.toggle('vn-list-view', view === 'list');
        document.querySelectorAll(`[data-view-toggle="${button.dataset.viewToggle}"]`).forEach((other) => {
            other.setAttribute('aria-pressed', String(other.dataset.view === view));
        });
    };

    button.addEventListener('click', () => {
        apply(button.dataset.view);
        boardStore.set(key, button.dataset.view);
    });

    if (button.dataset.view === 'grid') {
        apply(boardStore.get(key, 'grid'));
    }
});

// Installing VakilNiti as an app. The layout keeps the browser's install prompt in
// window.vnInstallPrompt; [data-install-app] buttons appear only when the browser can
// install it, and [data-platform-hint="ios|android|desktop"] shows the steps for this device.
(() => {
    const userAgent = navigator.userAgent || '';
    const isIos = /iphone|ipad|ipod/i.test(userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const platform = isIos ? 'ios' : (/android/i.test(userAgent) ? 'android' : 'desktop');
    const installed = window.matchMedia?.('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const installButtons = document.querySelectorAll('[data-install-app]');

    document.querySelectorAll('[data-platform-hint]').forEach((element) => {
        element.toggleAttribute('data-current-platform', element.dataset.platformHint === platform);
    });
    document.querySelectorAll('[data-installed-note]').forEach((element) => {
        element.hidden = !installed;
    });

    const showInstallButtons = () => {
        installButtons.forEach((button) => {
            button.hidden = installed || !window.vnInstallPrompt;
        });
    };

    installButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const prompt = window.vnInstallPrompt;

            if (!prompt) {
                window.location.assign(button.dataset.installFallback || '/app');

                return;
            }

            prompt.prompt();
            await prompt.userChoice.catch(() => null);
            window.vnInstallPrompt = null;
            showInstallButtons();
        });
    });

    window.addEventListener('vn-installable', showInstallButtons);
    window.addEventListener('appinstalled', () => {
        window.vnInstallPrompt = null;
        showInstallButtons();
    });
    showInstallButtons();
})();

document.addEventListener('change', (event) => {
    const form = event.target.closest('form[data-court-position]');
    if (!form || !['appearing_for', 'petitioner_side'].includes(event.target.name)) {
        return;
    }

    const party = form.elements.appearing_for.selectedOptions[0]?.dataset.side;
    const petitionerSide = form.elements.petitioner_side?.value;
    const preview = form.querySelector('[data-court-position-preview]');
    if (!party || !petitionerSide) {
        preview.textContent = 'Choose your party and the petitioner’s side';
        return;
    }

    const position = party === 'moving' ? petitionerSide : (petitionerSide === 'left' ? 'right' : 'left');
    preview.textContent = position === 'left' ? 'Left' : 'Right';
});

// Tabs (components/tabs): one panel on screen at a time. The open tab is kept in the
// address (#panel-id) and for this page in the session, so reloads and form redirects come
// back to it. A panel holding an error message opens first.
document.querySelectorAll('[data-tabs]').forEach((tabList) => {
    const tabs = [...tabList.querySelectorAll('[data-tab]')];
    const panels = tabs.map((tab) => document.getElementById(tab.dataset.tab)).filter(Boolean);
    const mobileScreen = tabList.hasAttribute('data-mobile-tabs') ? window.matchMedia('(width < 64rem)') : null;

    if (panels.length === 0) {
        return;
    }

    const storageKey = 'vn-tab:' + window.location.pathname;
    const panelFor = (id) => panels.find((panel) => panel.id === id || (id && panel.querySelector('#' + CSS.escape(id))));

    const select = (id, { focus = false, remember = true } = {}) => {
        if (mobileScreen && !mobileScreen.matches) {
            panels.forEach((panel) => { panel.hidden = false; });
            return;
        }

        const panel = panelFor(id) || panels[0];

        tabs.forEach((tab) => {
            const selected = tab.dataset.tab === panel.id;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;

            if (selected) {
                const bounds = tabList.getBoundingClientRect();
                const target = tab.getBoundingClientRect();

                // Reveal the tab inside its pill row without moving the page.
                if (target.left < bounds.left) {
                    tabList.scrollBy({ left: target.left - bounds.left });
                } else if (target.right > bounds.right) {
                    tabList.scrollBy({ left: target.right - bounds.right });
                }

                if (focus) {
                    tab.focus({ preventScroll: true });
                }
            }
        });
        panels.forEach((other) => {
            other.hidden = other !== panel;
        });

        if (remember) {
            history.replaceState(history.state, '', '#' + panel.id);

            try {
                sessionStorage.setItem(storageKey, panel.id);
            } catch {
                // Private browsing: the tab simply isn't remembered.
            }
        }
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', (event) => {
            event.preventDefault();
            select(tab.dataset.tab);

            // Bring the start of the panel into view when the tab bar has scrolled away.
            if (tabList.getBoundingClientRect().top <= 80) {
                tabList.scrollIntoView({ block: 'start' });
            }
        });
        tab.addEventListener('keydown', (event) => {
            const step = { ArrowRight: 1, ArrowLeft: -1 }[event.key];

            if (step) {
                event.preventDefault();
                select(tabs[(index + step + tabs.length) % tabs.length].dataset.tab, { focus: true });
            }
        });
    });

    // Links elsewhere on the page to a tab (href="#case-clients" or data-tab-link="case-clients").
    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-tab-link], a[href^="#"]');
        const id = link?.dataset.tabLink || link?.getAttribute('href')?.slice(1);

        if ((!mobileScreen || mobileScreen.matches) && link && !link.hasAttribute('data-tab') && id && panelFor(id)) {
            event.preventDefault();
            select(id);
            tabList.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }
    });
    window.addEventListener('hashchange', () => select(window.location.hash.slice(1), { remember: false }));

    const restoreTab = () => {
        const fromAddress = window.location.hash.slice(1);
        const withError = panels.find((panel) => panel.querySelector('[role="alert"], [data-field-error]'));
        let remembered = null;

        try {
            remembered = sessionStorage.getItem(storageKey);
        } catch {
            // Private browsing: use the address or the default tab.
        }

        select(
            (fromAddress && panelFor(fromAddress) ? fromAddress : null) || withError?.id || remembered || panels[0].id,
            { remember: false },
        );
    };

    mobileScreen?.addEventListener('change', restoreTab);
    restoreTab();
});


// Keyboard shortcuts: "/" jumps to the page's search box, "N" presses the page's [data-shortcut="n"]
// button (Add case, New invoice…). Ignored while typing, with modifier keys, or when a pop-up is open.
document.addEventListener('keydown', (event) => {
    if (event.ctrlKey || event.metaKey || event.altKey || event.defaultPrevented) {
        return;
    }

    const typing = event.target.closest?.('input, textarea, select, [contenteditable=""], [contenteditable="true"]');

    if (typing || document.querySelector('dialog[open]')) {
        return;
    }

    if (event.key === '/') {
        const search = document.querySelector('.vn-main-content input[type="search"], .vn-main-content input[name="search"], input[type="search"]');

        if (search && search.offsetParent !== null) {
            event.preventDefault();
            search.focus();
            search.select?.();
        }
    } else if (event.key === 'n' || event.key === 'N') {
        const target = document.querySelector('[data-shortcut="n"]');

        if (target) {
            event.preventDefault();
            target.click();
        }
    }
});

// Long lists that go on while scrolling (Home's hearings): when the [data-load-more] marker comes into view,
// the next part is fetched and put in its place; the new part brings its own marker until the last one.
const loadMore = (marker) => {
    if (marker.dataset.loading) {
        return;
    }

    marker.dataset.loading = '1';
    const link = marker.innerHTML;
    marker.textContent = 'Loading more hearings…';
    // Signed out meanwhile: the answer is the sign-in page, which must not land in the list.
    fetch(marker.dataset.loadMore, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then((response) => (response.ok && !response.redirected ? response.text() : Promise.reject(response.status)))
        .then((html) => {
            const part = document.createRange().createContextualFragment(html);
            const next = part.querySelector('[data-load-more]');
            marker.replaceWith(part);

            if (next) {
                watchLoadMore(next);
            }
        })
        .catch(() => {
            // The plain link stays, so a tap still opens the rest.
            marker.innerHTML = link;
            delete marker.dataset.loading;
        });
};

const loadMoreObserver = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => entries.forEach((entry) => entry.isIntersecting && loadMore(entry.target)), { rootMargin: '1200px 0px' })
    : null;
const watchLoadMore = (marker) => loadMoreObserver?.observe(marker);

document.querySelectorAll('[data-load-more]').forEach(watchLoadMore);

// A judge's photo that does not load (removed from the court's site, offline) gives way to the judge's initials,
// in the same size and shape, instead of a broken-image icon.
const showInitials = (image) => {
    const badge = document.createElement('span');
    badge.className = `${image.className} inline-flex items-center justify-center bg-slate-100 text-[11px] font-bold text-slate-600`;
    badge.textContent = image.dataset.initials;
    badge.setAttribute('aria-hidden', 'true');
    image.replaceWith(badge);
};

document.addEventListener('error', (event) => {
    if (event.target instanceof HTMLImageElement && event.target.matches('img[data-initials]')) {
        showInitials(event.target);
    }
}, true);
document.querySelectorAll('img[data-initials]').forEach((image) => image.complete && image.naturalWidth === 0 && showInitials(image));

// Whole boxes open their page: a card marked data-card-href goes there when clicked anywhere except on its own
// buttons, links and fields (they keep doing their own thing). Same behaviour for every card on the site.
document.addEventListener('click', (event) => {
    const card = event.target.closest('[data-card-href]');

    if (!card || event.defaultPrevented || event.button !== 0 || window.getSelection()?.toString()) {
        return;
    }

    if (event.target.closest('a, button, input, select, textarea, label, summary, details, form, [data-sheet], [role="button"]')) {
        return;
    }

    if (event.metaKey || event.ctrlKey) {
        window.open(card.dataset.cardHref, '_blank', 'noopener');
    } else {
        window.location.assign(card.dataset.cardHref);
    }
});

// "Share day" on My cases: before the share preview opens (resources/js/sheet.js), gather the message from the
// cards of that day on the page (each card carries data-day-item and its own data-day-text).
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-share-day]');

    if (!button) {
        return;
    }

    const texts = [...document.querySelectorAll('[data-day-item]')]
        .filter((card) => card.dataset.dayItem === button.dataset.shareDay)
        .map((card) => card.dataset.dayText)
        .filter(Boolean);
    const message = ['*' + button.dataset.shareTitle + '*', ...texts.map((text, index) => (index + 1) + '. ' + text)].join('\n\n');

    button.dataset.shareText = message;
    button.href = 'https://wa.me/?text=' + encodeURIComponent(message);
}, true);

// Share buttons (data-share-text) open the share preview in resources/js/sheet.js.

// Phone cards for tables (.vn-stack-table): cells take their column heading as a label, and the
// first cell becomes the card title, unless the Blade view already set data-label / data-stack.
document.querySelectorAll('table.vn-stack-table').forEach((table) => {
    const headings = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());

    table.querySelectorAll('tbody tr').forEach((row) => {
        const cells = [...row.children].filter((cell) => cell.tagName === 'TD');

        if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
            return;
        }

        const hasTitle = cells.some((cell) => cell.dataset.stack === 'title');

        cells.forEach((cell, index) => {
            if (index === 0 && !hasTitle && !cell.dataset.stack) {
                cell.dataset.stack = 'title';

                return;
            }

            if (!cell.dataset.label && !cell.dataset.stack && headings[index]) {
                cell.dataset.label = headings[index];
            }
        });
    });
});

// Hearing strips (components/hearing-strip): a cause-list line too long for its box glides to show the rest.
const sizeTickers = () => {
    document.querySelectorAll('[data-ticker]').forEach((ticker) => {
        const text = ticker.querySelector('[data-ticker-text]');
        const shift = text ? text.scrollWidth - ticker.clientWidth + 24 : 0;

        ticker.classList.toggle('is-moving', shift > 24);
        ticker.style.setProperty('--vn-ticker-shift', `${Math.max(0, shift)}px`);
        ticker.style.setProperty('--vn-ticker-time', `${Math.max(5, shift / 35)}s`);
    });
};

sizeTickers();
window.addEventListener('resize', () => window.requestAnimationFrame(sizeTickers));
document.addEventListener('vn:sheet-loaded', sizeTickers);
