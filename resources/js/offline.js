/*
 * Offline copy on the phone (public/sw.js): register it while signed in, remove the saved pages after
 * signing out, and say clearly when a page is the saved copy because there is no internet.
 */
const signedIn = document.body.dataset.signedIn === '1';

if ('serviceWorker' in navigator && window.isSecureContext) {
    if (signedIn) {
        navigator.serviceWorker.register('/sw.js').then(() => navigator.serviceWorker.ready).then((registration) => {
            const assets = [...document.querySelectorAll('link[rel="stylesheet"][href], script[src], link[rel="modulepreload"][href]')]
                .map((element) => element.href || element.src);

            registration.active?.postMessage({ type: 'warm', assets });
        }).catch(() => {});
    } else if (navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({ type: 'forget' });
    }
}

// Signed out (or another person on this phone): no saved case pages may stay behind.
if (!signedIn && window.caches) {
    caches.keys().then((names) => names.filter((name) => name.startsWith('vn-pages')).forEach((name) => caches.delete(name))).catch(() => {});
}

const showOfflineState = () => {
    const offline = !navigator.onLine;

    document.querySelectorAll('[data-offline-note]').forEach((note) => {
        note.hidden = !offline;
    });

    let pill = document.getElementById('vn-offline-pill');

    if (offline && !pill && !document.querySelector('[data-offline-note]')) {
        pill = document.createElement('p');
        pill.id = 'vn-offline-pill';
        pill.setAttribute('role', 'status');
        pill.className = 'fixed inset-x-4 bottom-20 z-50 mx-auto max-w-sm rounded-full bg-[#142235] px-4 py-2 text-center text-sm font-semibold text-white shadow-lg lg:bottom-6';
        pill.textContent = 'No internet · showing the copy saved on this phone';
        document.body.append(pill);
    } else if (!offline && pill) {
        pill.remove();
    }
};

window.addEventListener('online', showOfflineState);
window.addEventListener('offline', showOfflineState);
showOfflineState();
