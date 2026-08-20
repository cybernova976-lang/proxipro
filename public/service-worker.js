const CACHE_PREFIX = 'prokejem-pwa-';
const STATIC_CACHE = `${CACHE_PREFIX}static-v2`;
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

self.addEventListener('push', (event) => {
    let payload = {};

    if (event.data) {
        try {
            payload = event.data.json();
        } catch (error) {
            payload = { body: event.data.text() };
        }
    }

    const title = payload.title || 'Prokejem';
    const options = {
        body: payload.body || 'Vous avez une nouvelle notification.',
        icon: payload.icon || '/pwa/icon-192.png',
        badge: payload.badge || '/pwa/icon-192.png',
        data: payload.data || { url: '/' },
        tag: payload.tag || 'prokejem-notification',
        lang: payload.lang || 'fr',
        actions: payload.actions || [],
        vibrate: payload.vibrate || [200, 100, 200],
        renotify: payload.renotify || false,
        requireInteraction: payload.requireInteraction || false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    let targetUrl = self.location.origin + '/';
    try {
        const candidate = new URL(event.notification.data?.url || '/', self.location.origin);
        if (candidate.origin === self.location.origin) targetUrl = candidate.href;
    } catch (error) {
        // Conserver la page d'accueil comme destination sûre.
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async (windowClients) => {
            const exactClient = windowClients.find((client) => client.url === targetUrl);
            if (exactClient) return exactClient.focus();

            const appClient = windowClients.find((client) => client.url.startsWith(self.location.origin));
            if (appClient) {
                if ('navigate' in appClient) await appClient.navigate(targetUrl);
                return appClient.focus();
            }

            return clients.openWindow(targetUrl);
        })
    );
});
