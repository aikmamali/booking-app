/*
 * PWA service worker (spec section 11). Pages are always fetched from the network so
 * bookings and balances are never stale; when offline, an offline page is shown instead.
 * Built assets (/build/, hashed names) and icons are cached because they never change.
 */
const CACHE = 'booking-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll([OFFLINE_URL, '/icons/icon-192.png'])));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                const copy = response.clone();
                caches.open(CACHE).then((cache) => cache.put(request, copy));
                return response;
            }))
        );
    }
});
