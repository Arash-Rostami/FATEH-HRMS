import { precacheAndRoute } from 'workbox-precaching';
import { clientsClaim } from 'workbox-core';

// Automatically take control of clients
self.skipWaiting(); clientsClaim();

const manifest = self.__WB_MANIFEST;

precacheAndRoute(manifest);

self.addEventListener('fetch', (event) => {
    // Only intercept GET requests
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    const isPrecached = manifest.some(entry => {
        const assetUrl = (typeof entry === 'string') ? entry : entry.url;
        return url.pathname.endsWith(assetUrl);
    });

    if (isPrecached) {
        event.respondWith(
            caches.match(event.request).then((response) => {
                if (response) {
                    console.log('⚡ [Service Worker] Serving from cache:', url.pathname);
                    return response;
                }
                return fetch(event.request);
            })
        );
    }
});

console.log('✅ Service Worker Loaded & Ready');
