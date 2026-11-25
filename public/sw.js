/* VakilNiti service worker. Only public static assets are retained offline. */
const ASSETS = 'vn-assets-v2';
const MAX_ASSETS = 80;

const OFFLINE_PAGE = `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>No internet · VakilNiti</title><style>body{font:16px/1.5 system-ui,sans-serif;margin:0;background:#f7f4ee;color:#142235;display:grid;place-items:center;min-height:100vh;padding:16px}
main{max-width:28rem}h1{font-size:1.25rem}a{color:#16776e;font-weight:600}@media (prefers-color-scheme:dark){body{background:#0f1a28;color:#e6edf5}a{color:#8ed2c8}}</style></head>
<body><main><h1>No internet</h1><p>Reconnect to view your workspace.</p></main></body></html>`;

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const names = await caches.keys();
        await Promise.all(names.filter((name) => name.startsWith('vn-') && name !== ASSETS).map((name) => caches.delete(name)));
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    const data = event.data || {};

    // The page lists its own stylesheet and scripts, so the very first page is complete offline too.
    if (data.type === 'warm' && Array.isArray(data.assets)) {
        event.waitUntil(caches.open(ASSETS).then((cache) => Promise.all(data.assets
            .filter((url) => sameOrigin(url) && isAsset(new URL(url, self.location.origin).pathname))
            .map((url) => cache.match(url).then((hit) => hit || cache.add(url).catch(() => null))))));
    }
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET' || !sameOrigin(request.url)) {
        return;
    }

    const url = new URL(request.url);

    if (isAsset(url.pathname)) {
        event.respondWith(assetFirstFromCache(request));

        return;
    }

    if (request.mode !== 'navigate') {
        return;
    }

    event.respondWith(fetch(request).catch(() => new Response(OFFLINE_PAGE, {
        status: 503,
        headers: { 'Content-Type': 'text/html; charset=utf-8' },
    })));
});

function sameOrigin(url) {
    return new URL(url, self.location.origin).origin === self.location.origin;
}

function isAsset(path) {
    return path.startsWith('/build/') || path.startsWith('/images/') || path === '/favicon.ico' || path === '/manifest.webmanifest';
}

// Build files have a content hash in their name, so a saved copy is always right.
async function assetFirstFromCache(request) {
    const cache = await caches.open(ASSETS);
    const hit = await cache.match(request);

    if (hit) {
        return hit;
    }

    const response = await fetch(request);

    if (response.ok && response.type === 'basic') {
        await cache.put(request, response.clone());
        trim(cache, MAX_ASSETS);
    }

    return response;
}

async function trim(cache, max) {
    const keys = await cache.keys();

    await Promise.all(keys.slice(0, Math.max(0, keys.length - max)).map((key) => cache.delete(key)));
}
