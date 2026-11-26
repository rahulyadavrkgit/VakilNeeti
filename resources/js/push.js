/*
 * Push notifications for alerts. The Alerts page offers one "Turn on" button while the browser has not been asked;
 * after that, every page quietly keeps this browser's push address up to date on the server. Signing out removes it,
 * so a shared phone stops getting someone else's alerts. iPhones get pushes only once VakilNiti is on the Home Screen.
 */
const publicKey = document.querySelector('meta[name="vn-push-key"]')?.content;
const supported = Boolean(publicKey) && window.isSecureContext && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
const installed = window.matchMedia?.('(display-mode: standalone)').matches || window.navigator.standalone === true;

const keyBytes = (base64url) => {
    const base64 = (base64url + '='.repeat((4 - (base64url.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');

    return Uint8Array.from(atob(base64), (character) => character.charCodeAt(0));
};

const send = (method, body) => fetch('/push-subscriptions', {
    method,
    keepalive: true,
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
    body: JSON.stringify(body),
});

const subscribe = async () => {
    const registration = await navigator.serviceWorker.register('/sw.js').then(() => navigator.serviceWorker.ready);
    const subscription = await registration.pushManager.getSubscription()
        || await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(publicKey) });

    await send('POST', subscription.toJSON());

    return subscription;
};

if (supported) {
    // Already allowed: make sure the server still has this browser (it may have changed its address).
    if (Notification.permission === 'granted') {
        subscribe().catch(() => {});
    }

    document.querySelectorAll('[data-push-offer]').forEach((offer) => {
        offer.hidden = Notification.permission !== 'default' || (isIos && !installed);
    });

    document.querySelectorAll('[data-push-enable]').forEach((button) => {
        button.addEventListener('click', async () => {
            button.disabled = true;
            const permission = await Notification.requestPermission();

            if (permission === 'granted') {
                await subscribe().catch(() => {});
            }

            button.closest('[data-push-offer]')?.setAttribute('hidden', '');
        });
    });

    // Signing out: stop this browser getting the account's alerts.
    document.querySelectorAll('form[action$="/user-logout"]').forEach((form) => {
        form.addEventListener('submit', () => {
            navigator.serviceWorker.ready
                .then((registration) => registration.pushManager.getSubscription())
                .then((subscription) => subscription && send('DELETE', { endpoint: subscription.endpoint }))
                .catch(() => {});
        });
    });
}

// iPhone in Safari: pushes need VakilNiti on the Home Screen first.
document.querySelectorAll('[data-push-ios-hint]').forEach((hint) => {
    hint.hidden = !(publicKey && isIos && !installed);
});
