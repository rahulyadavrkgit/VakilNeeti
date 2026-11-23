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
 * - data-vn-close-webview          closes the external webview page.
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

document.querySelectorAll('[data-board-picker]').forEach((picker) => {
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
            frameWrap.hidden = true;
            blocked.hidden = false;
            picker.querySelector('[data-board-open-actions]').hidden = true;

            return;
        }

        picker.querySelector('[data-board-open-actions]').hidden = false;
        name.textContent = option.dataset.label;
        const embeddable = option.dataset.embeddable === '1';
        frameWrap.hidden = !embeddable;
        blocked.hidden = embeddable;

        if (embeddable && frame.src !== option.value) {
            frame.src = option.value;
        }

        boardStore.set('vn-display-board', option.value);
    };

    // An option shows when it is one of the chosen boards and matches the search.
    const applyFilters = () => {
        const query = (search?.value || '').trim().toLowerCase();
        const picked = choices.filter((box) => box.checked).map((box) => box.value);

        const current = select.value;
        const groups = new Map();

        allBoards.forEach(({ group, option }) => {
            const visible = picked.includes(option.value) && (query === '' || option.textContent.toLowerCase().includes(query));

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
    };

    const saved = boardStore.get('vn-display-board', null);

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
    search?.addEventListener('input', applyFilters);
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
// fetched once per page and refreshed every minute while the page is open; the server caches it for everyone.
const liveChips = [...document.querySelectorAll('[data-live-chip]')];

if (liveChips.length) {
    const caseKey = (text) => {
        const match = String(text || '').match(/(\d+)\s*(?:\/|of)\s*(\d{4})/i);

        return match ? match[1] + '/' + match[2] : null;
    };

    const fill = (chip, board) => {
        const room = board.rooms.find((item) => item.room === chip.dataset.room);
        const text = chip.querySelector('[data-live-chip-text]');
        // No board to show (the court's data cannot be reached): leave the line as it was.
        chip.hidden = board.rooms.length === 0;
        const isMineNow = room?.status === 'running' && caseKey(chip.dataset.caseNumber) !== null
            && caseKey(chip.dataset.caseNumber) === caseKey(room.case_number);

        if (!room) {
            text.textContent = 'Live board';
        } else if (isMineNow) {
            text.textContent = 'Your case is on now · item ' + room.item;
        } else if (room.status === 'running') {
            text.textContent = 'Now item ' + room.item;
        } else if (room.status === 'exhausted') {
            text.textContent = 'Board over';
        } else {
            text.textContent = 'Not sitting yet';
        }

        chip.toggleAttribute('data-mine-now', isMineNow);
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
                .then((board) => {
                    if (board) {
                        liveChips.filter((chip) => chip.dataset.source === source).forEach((chip) => fill(chip, board));
                    }
                })
                .catch(() => {});
        });
    };

    refreshChips();
    setInterval(refreshChips, 60000);
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

// Tabs (components/tabs): one panel on screen at a time. The open tab is kept in the
// address (#panel-id) and for this page in the session, so reloads and form redirects come
// back to it. A panel holding an error message opens first.
document.querySelectorAll('[data-tabs]').forEach((tabList) => {
    const tabs = [...tabList.querySelectorAll('[data-tab]')];
    const panels = tabs.map((tab) => document.getElementById(tab.dataset.tab)).filter(Boolean);

    if (panels.length === 0) {
        return;
    }

    const storageKey = 'vn-tab:' + window.location.pathname;
    const panelFor = (id) => panels.find((panel) => panel.id === id || (id && panel.querySelector('#' + CSS.escape(id))));

    const select = (id, { focus = false, remember = true } = {}) => {
        const panel = panelFor(id) || panels[0];

        tabs.forEach((tab) => {
            const selected = tab.dataset.tab === panel.id;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;

            if (selected) {
                tab.scrollIntoView({ block: 'nearest', inline: 'nearest' });

                if (focus) {
                    tab.focus();
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

        if (link && !link.hasAttribute('data-tab') && id && panelFor(id)) {
            event.preventDefault();
            select(id);
            tabList.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }
    });
    window.addEventListener('hashchange', () => select(window.location.hash.slice(1), { remember: false }));

    const fromAddress = window.location.hash.slice(1);
    const withError = panels.find((panel) => panel.querySelector('[role="alert"], [data-field-error]'));
    let remembered = null;

    try {
        remembered = sessionStorage.getItem(storageKey);
    } catch {
        remembered = null;
    }

    select(
        (fromAddress && panelFor(fromAddress) ? fromAddress : null) || withError?.id || remembered || panels[0].id,
        { remember: Boolean(fromAddress || withError || remembered) },
    );
});

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
