/**
 * Calendar hover cards: pointing at (or keyboard-focusing) an event shows its key details
 * (side, court, room, judge, VC link, stage) in one floating card. The details come from the
 * <template data-event-card> inside each [data-event-peek] element, so nothing extra is fetched.
 * The card stays open while the pointer is on it, so its Join VC / Open case links can be used.
 */
const card = document.createElement('div');
card.className = 'vn-event-peek';
card.setAttribute('role', 'tooltip');
card.id = 'vn-event-peek';
card.hidden = true;
document.body.append(card);

let owner = null;
let hideTimer = null;

const canHover = window.matchMedia('(hover: hover) and (pointer: fine)');

function place(target) {
    const gap = 8;
    const box = target.getBoundingClientRect();
    const width = card.offsetWidth;
    const height = card.offsetHeight;
    const viewportWidth = document.documentElement.clientWidth;
    const viewportHeight = window.innerHeight;

    // Prefer the right of the event, then the left, then below it.
    let left = box.right + gap;
    let top = box.top;

    if (left + width > viewportWidth - gap) {
        left = box.left - width - gap;
    }

    if (left < gap) {
        left = Math.min(Math.max(gap, box.left), viewportWidth - width - gap);
        top = box.bottom + gap;
    }

    top = Math.min(Math.max(gap, top), viewportHeight - height - gap);

    card.style.left = `${Math.round(left)}px`;
    card.style.top = `${Math.round(top)}px`;
}

function show(target) {
    const template = target.querySelector('template[data-event-card]');

    if (!template) {
        return;
    }

    clearTimeout(hideTimer);

    if (owner !== target) {
        owner?.removeAttribute('aria-describedby');
        owner = target;
        card.replaceChildren(template.content.cloneNode(true));
        target.setAttribute('aria-describedby', card.id);
    }

    card.hidden = false;
    place(target);
}

function hideSoon() {
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => {
        card.hidden = true;
        owner?.removeAttribute('aria-describedby');
        owner = null;
    }, 150);
}

document.addEventListener('pointerover', (event) => {
    if (!canHover.matches) {
        return;
    }

    const target = event.target.closest('[data-event-peek]');

    if (target) {
        show(target);
    } else if (event.target.closest('#vn-event-peek')) {
        clearTimeout(hideTimer);
    }
});

document.addEventListener('pointerout', (event) => {
    if (card.hidden || !event.target.closest?.('[data-event-peek], #vn-event-peek')) {
        return;
    }

    // Moving between the event and its card (or within either) keeps the card open.
    const to = event.relatedTarget;

    if (to instanceof Node && (card.contains(to) || owner?.contains(to))) {
        return;
    }

    hideSoon();
});

document.addEventListener('focusin', (event) => {
    if (!canHover.matches) {
        return;
    }

    const target = event.target.closest('[data-event-peek]');

    if (target) {
        show(target);
    } else if (!event.target.closest('#vn-event-peek')) {
        hideSoon();
    }
});

/*
 * Phones and tablets (no hover): tapping an event opens the same details as a sheet from the
 * bottom of the screen, with Join VC and Open case; tapping outside or Close shuts it.
 */
const backdrop = document.createElement('div');
backdrop.className = 'vn-event-peek-backdrop';
backdrop.hidden = true;
document.body.append(backdrop);

function closeSheet() {
    card.hidden = true;
    backdrop.hidden = true;
    card.classList.remove('is-sheet');
    owner = null;
}

function openSheet(target) {
    const template = target.querySelector('template[data-event-card]');

    if (!template) {
        return false;
    }

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'vn-event-peek-close';
    close.textContent = 'Close';
    close.addEventListener('click', closeSheet);

    owner = target;
    card.replaceChildren(close, template.content.cloneNode(true));
    card.classList.add('is-sheet');
    card.style.left = '';
    card.style.top = '';
    card.hidden = false;
    backdrop.hidden = false;

    return true;
}

document.addEventListener('click', (event) => {
    if (canHover.matches) {
        return;
    }

    const target = event.target.closest('[data-event-peek]');

    if (target && !card.contains(event.target) && openSheet(target)) {
        event.preventDefault();
    }
});

backdrop.addEventListener('click', closeSheet);

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !card.hidden) {
        card.classList.contains('is-sheet') ? closeSheet() : (card.hidden = true);
    }
});

window.addEventListener('scroll', () => {
    if (!card.hidden && owner && !card.classList.contains('is-sheet')) {
        place(owner);
    }
}, { passive: true });
