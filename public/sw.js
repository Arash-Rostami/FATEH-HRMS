
const CACHE_NAME = 'app-cache-1771931863858';
const ASSETS = ["/build/assets/Yekan-C-jnuSFy.woff","/build/assets/Yekan-DfMZECic.woff2","/build/assets/Yekan-_-H4vtX6.ttf","/build/assets/app-CPPCROjm.css","/build/assets/app-CWZrGuCE.js","/build/assets/assets/fonts/Yekan.css","/build/assets/assets/fonts/Yekan.eot","/build/assets/assets/fonts/Yekan.ttf","/build/assets/assets/fonts/Yekan.woff","/build/assets/assets/fonts/Yekan.woff2","/build/assets/assets/img/mining.svg","/build/assets/background-CJlwin5j.js","/build/assets/feed-CumNT8nD.js","/build/assets/filters-iyUQqgZ1.js","/build/assets/gallery-L_4QB6FV.js","/build/assets/googleTranslate-CYwTToCN.js","/build/assets/greeting-C6-INOB0.js","/build/assets/links-C5eFwHyF.js","/build/assets/material-symbols-rounded-GzsEeY_J.woff2","/build/assets/occasion-QfEFMner.js","/build/assets/palette-_3K-RTJg.js","/build/assets/password-Byn6xLFp.js","/build/assets/report-DUuO63gp.js","/build/assets/settings-DqblkygM.js","/build/assets/shapes-BuCgLEbL.js","/build/assets/share-CZMClS0D.js","/build/assets/timer-C-CxNaGC.js"];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS);
        })
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Only intercept requests for cached assets
    // This strategy ensures we serve from cache if available,
    // but fall back to network if for some reason it's missing.
    // We strictly avoid caching HTML (navigation) to prevent stale content issues in Laravel/Livewire.
    if (ASSETS.includes(url.pathname)) {
        event.respondWith(
            caches.match(event.request).then((response) => {
                return response || fetch(event.request);
            })
        );
    }
});
