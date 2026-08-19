const CACHE_PREFIX = 'prokejem-pwa-';
const STATIC_CACHE = `${CACHE_PREFIX}static-v1`;
const OFFLINE_PAGE = '/offline.html';
const PRECACHE_URLS = [
    OFFLINE_PAGE,
    '/manifest.webmanifest',
    '/favicon.ico',
    '/pwa/icon-192.png',
    '/pwa/icon-512.png',
    '/pwa/icon-maskable-192.png',
    '/pwa/icon-maskable-512.png',
    '/pwa/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith(CACHE_PREFIX) && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_PAGE))
        );
        return;
    }

    const isPublicStaticAsset = url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/images/brand/')
        || url.pathname.startsWith('/pwa/')
        || url.pathname === '/favicon.ico'
        || url.pathname === '/manifest.webmanifest';

    if (!isPublicStaticAsset) return;

    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            const networkResponse = fetch(request).then((response) => {
                if (response.ok && response.type === 'basic') {
                    const responseCopy = response.clone();
                    return caches.open(STATIC_CACHE)
                        .then((cache) => cache.put(request, responseCopy))
                        .then(() => response);
                }

                return response;
            }).catch(() => cachedResponse || Response.error());

            return cachedResponse || networkResponse;
        })
    );
});
