/**
 * The site-wide pop-up sheet: a panel on the right on computers, a sheet from the bottom on phones.
 *
 * - <a href="/full/page" data-sheet="/sheets/…">  opens that piece of page in the sheet; the href stays the
 *   normal page (Ctrl/⌘-click, middle-click, or no JavaScript).
 * - <a href="/page" data-sheet-frame>              shows a whole page inside the sheet (court fee, live board).
 * - <form data-sheet-form>                          inside a sheet saves without leaving: errors appear by the
 *   fields; on success the sheet closes and the page refreshes (data-sheet-follow opens the saved record).
 * - [data-search-open], "/" or Ctrl+K              opens search, with results as you type.
 * - [data-share-text]                               shows the message first, then WhatsApp, Copy, Email or Share.
 */
const sheet = document.createElement('dialog');
sheet.className = 'vn-sheet';
sheet.setAttribute('aria-label', 'Panel');
sheet.innerHTML = `
    <div class="vn-sheet-bar">
        <span class="vn-sheet-grip" aria-hidden="true"></span>
        <button type="button" data-sheet-close class="vn-sheet-close" aria-label="Close">&times;</button>
    </div>
    <div class="vn-sheet-body" data-sheet-body></div>`;
document.body.append(sheet);

const body = sheet.querySelector('[data-sheet-body]');
let opener = null;

const loading = '<p class="py-10 text-center text-sm text-slate-500">Loading…</p>';

function openSheet(trigger = null) {
    opener = trigger || document.activeElement;
    sheet.classList.remove('is-frame');
    document.querySelectorAll('details.vn-profile-menu[open]').forEach((menu) => {
        menu.open = false;
    });

    if (!sheet.open) {
        sheet.showModal();
    }
}

function closeSheet() {
    if (sheet.open) {
        sheet.close();
    }
}

sheet.addEventListener('close', () => {
    body.replaceChildren();
    opener?.focus?.();
});

// Close on the backdrop (a click on the dialog itself, outside its content) and on the × button.
sheet.addEventListener('click', (event) => {
    if (event.target === sheet || event.target.closest('[data-sheet-close]')) {
        closeSheet();
    }
});

function focusFirstField() {
    (body.querySelector('[autofocus]') || body.querySelector('input:not([type=hidden]), select, textarea, button, a'))?.focus();
}

async function loadInto(url) {
    body.innerHTML = loading;

    try {
        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' } });

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        body.innerHTML = await response.text();
        sheet.setAttribute('aria-label', body.querySelector('[data-sheet-heading]')?.textContent.trim() || 'Panel');
        focusFirstField();
    } catch {
        body.innerHTML = '<p class="py-10 text-center text-sm text-red-700">This could not be opened. Check your connection and try again.</p>';
    }
}

function showFrame(url, title) {
    const frameUrl = new URL(url, window.location.href);
    frameUrl.searchParams.set('embed', '1');
    sheet.classList.add('is-frame');
    body.innerHTML = '';
    const frame = document.createElement('iframe');
    frame.src = frameUrl.toString();
    frame.title = title || 'Page';
    frame.className = 'vn-sheet-frame';
    body.append(frame);
    sheet.setAttribute('aria-label', frame.title);
}

const plainClick = (event) => event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey;

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-sheet], [data-sheet-frame]');

    if (!trigger || !plainClick(event)) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    openSheet(trigger);

    if (trigger.hasAttribute('data-sheet-frame')) {
        showFrame(trigger.dataset.sheetFrame || trigger.href, trigger.textContent.trim());
    } else {
        loadInto(trigger.dataset.sheet || trigger.href);
    }
}, true);

// A short note at the bottom of the screen after saving.
export function toast(message) {
    const note = document.createElement('div');
    note.className = 'vn-toast';
    note.setAttribute('role', 'status');
    note.textContent = message;
    document.body.append(note);
    setTimeout(() => note.classList.add('is-leaving'), 2200);
    setTimeout(() => note.remove(), 2700);
}

window.vnToast = toast;

// Show a saved-message note that survived a page refresh.
try {
    const pending = sessionStorage.getItem('vn-toast');

    if (pending) {
        sessionStorage.removeItem('vn-toast');
        toast(pending);
    }
} catch {}

function showErrors(form, errors) {
    form.querySelectorAll('.vn-field-error').forEach((element) => element.remove());
    let first = null;

    Object.entries(errors).forEach(([name, messages]) => {
        const field = form.querySelector(`[name="${name}"], [name="${name}[]"]`);
        const note = document.createElement('p');
        note.className = 'vn-field-error mt-1 text-sm font-medium text-red-700';
        note.textContent = [].concat(messages)[0];

        if (field) {
            field.setAttribute('aria-invalid', 'true');
            (field.closest('label') || field).after(note);
            first ??= field;
        } else {
            form.prepend(note);
        }
    });

    first?.focus();
}

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-sheet-form')) {
        return;
    }

    event.preventDefault();
    const button = form.querySelector('[type=submit]');
    button?.setAttribute('disabled', '');

    try {
        const response = await fetch(form.action, {
            method: form.method || 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (response.status === 422) {
            showErrors(form, (await response.json()).errors || {});

            return;
        }

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        try {
            sessionStorage.setItem('vn-toast', form.dataset.sheetSuccess || 'Saved');
        } catch {}

        closeSheet();

        if (form.hasAttribute('data-sheet-follow') && response.url) {
            window.location.href = response.url;
        } else {
            window.location.reload();
        }
    } catch {
        showErrors(form, { _form: ['This could not be saved. Check your connection and try again.'] });
    } finally {
        button?.removeAttribute('disabled');
    }
});

// Search: results as you type, from any page.
let searchTimer = null;

function openSearch(trigger = null) {
    openSheet(trigger);
    const url = document.querySelector('meta[name="vn-search-sheet"]')?.content;
    body.innerHTML = `
        <h2 data-sheet-heading class="sr-only">Search</h2>
        <form action="${document.querySelector('meta[name="vn-search-page"]')?.content || '/search'}" method="GET" role="search" class="flex items-center gap-2 rounded-xl border border-slate-300 px-3 focus-within:border-[#16776e]">
            <span aria-hidden="true" class="text-slate-400">⌕</span>
            <input type="search" name="q" data-search-input autocomplete="off" placeholder="Search cases, clients, judges, rooms…" class="min-h-12 w-full border-0 bg-transparent px-0 text-base focus:ring-0">
        </form>
        <div data-search-results class="mt-2 -mx-2"></div>`;
    sheet.setAttribute('aria-label', 'Search');
    const input = body.querySelector('[data-search-input]');
    const results = body.querySelector('[data-search-results]');

    const run = async () => {
        if (!url) {
            return;
        }

        const response = await fetch(`${url}?q=${encodeURIComponent(input.value)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });

        if (response.ok) {
            results.innerHTML = await response.text();
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(run, 220);
    });
    run();
    input.focus();
}

// Desktop header search: the same results drop down under the box while typing; Enter still opens all results.
document.querySelectorAll('[data-header-search]').forEach((form) => {
    const input = form.querySelector('[data-header-search-input]');
    const panel = form.querySelector('[data-header-search-results]');
    const url = document.querySelector('meta[name="vn-search-sheet"]')?.content;
    let timer = null;
    let latest = 0;

    if (!input || !panel || !url) {
        return;
    }

    const close = () => {
        panel.hidden = true;
        input.setAttribute('aria-expanded', 'false');
    };

    const run = async () => {
        const query = input.value.trim();

        if (query.length < 2) {
            close();

            return;
        }

        const ticket = ++latest;
        const response = await fetch(`${url}?q=${encodeURIComponent(query)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => null);

        // An older answer arriving after a newer one is dropped.
        if (response?.ok && ticket === latest) {
            panel.innerHTML = await response.text();
            panel.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(run, 200);
    });
    input.addEventListener('focus', () => {
        if (input.value.trim().length >= 2 && panel.innerHTML.trim() !== '') {
            panel.hidden = false;
        }
    });
    form.addEventListener('keydown', (event) => {
        const links = [...panel.querySelectorAll('a')];
        const at = links.indexOf(document.activeElement);

        if (event.key === 'Escape') {
            close();
            input.focus();
        } else if (event.key === 'ArrowDown' && !panel.hidden && links.length) {
            event.preventDefault();
            links[Math.min(at + 1, links.length - 1)].focus();
        } else if (event.key === 'ArrowUp' && at >= 0) {
            event.preventDefault();
            (at === 0 ? input : links[at - 1]).focus();
        }
    });
    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) {
            close();
        }
    });
});

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-search-open]');

    if (trigger && plainClick(event) && document.querySelector('meta[name="vn-search-sheet"]')) {
        event.preventDefault();
        openSearch(trigger);
    }
});

document.addEventListener('keydown', (event) => {
    const typing = event.target.closest?.('input, textarea, select, [contenteditable="true"]');
    const wantsSearch = ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') || (event.key === '/' && !typing);

    if (wantsSearch && document.querySelector('meta[name="vn-search-sheet"]')) {
        event.preventDefault();
        // Wide screens have the box in the header: type there, with results under it.
        const headerInput = document.querySelector('[data-header-search-input]');

        if (headerInput && headerInput.offsetParent !== null) {
            headerInput.focus();
            headerInput.select();

            return;
        }

        openSearch();
    }
});

// Share preview: see the message, then choose how to send it.
document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-share-text]');

    if (!link || !plainClick(event)) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    const text = link.dataset.shareText;
    const title = link.dataset.shareTitle || 'Share';

    // "Share now" buttons go straight to the phone's share menu; elsewhere (no share menu) the preview below opens.
    if (link.hasAttribute('data-share-now') && navigator.share) {
        navigator.share({ title, text }).catch(() => {});

        return;
    }

    openSheet(link);
    body.innerHTML = `
        <h2 data-sheet-heading class="text-lg font-semibold text-slate-900"></h2>
        <textarea readonly rows="9" class="mt-3 w-full rounded-xl border border-slate-300 bg-transparent p-3 text-sm leading-relaxed"></textarea>
        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
            <a data-share-whatsapp target="_blank" rel="noopener noreferrer" class="flex min-h-11 items-center justify-center rounded-xl bg-[#25d366] px-3 text-sm font-semibold text-white">WhatsApp</a>
            <button type="button" data-copy class="flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-800"><span data-copy-label>Copy</span></button>
            <a data-share-email class="flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-800">Email</a>
            <button type="button" data-share-native hidden class="flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-800">More…</button>
        </div>`;
    body.querySelector('[data-sheet-heading]').textContent = title;
    body.querySelector('textarea').value = text;
    body.querySelector('[data-share-whatsapp]').href = `https://wa.me/?text=${encodeURIComponent(text)}`;
    body.querySelector('[data-copy]').dataset.copy = text;
    body.querySelector('[data-share-email]').href = `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(text)}`;
    sheet.setAttribute('aria-label', title);

    const native = body.querySelector('[data-share-native]');

    if (navigator.share) {
        native.hidden = false;
        native.addEventListener('click', () => navigator.share({ title, text }).catch(() => {}));
    }
}, true);

// A step inside the sheet (<form data-sheet-step method="get">): its fields go into the next step's address,
// which opens in the same sheet. The browser checks required fields first.
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-sheet-step')) {
        return;
    }

    event.preventDefault();
    const url = new URL(form.action, window.location.href);
    new FormData(form).forEach((value, key) => {
        if (typeof value === 'string' && value.trim() !== '') {
            url.searchParams.set(key, value.trim());
        }
    });
    loadInto(url.toString());
});
