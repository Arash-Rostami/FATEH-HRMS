import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import fs from 'node:fs';
import path from 'node:path';

// Custom plugin to generate Service Worker
const generateServiceWorker = () => {
    return {
        name: 'generate-service-worker',
        closeBundle() {
            const publicDir = 'public';
            const buildDir = 'public/build';
            const swPath = path.join(publicDir, 'sw.js');

            // Function to recursively find files
            const getFiles = (dir, fileList = []) => {
                if (!fs.existsSync(dir)) return fileList;

                const files = fs.readdirSync(dir);

                files.forEach(file => {
                    const filePath = path.join(dir, file);
                    const stat = fs.statSync(filePath);

                    if (stat.isDirectory()) {
                        getFiles(filePath, fileList);
                    } else {
                        // Only cache JS, CSS, and Fonts from build directory
                        if (/\.(js|css|woff2?|ttf|eot|svg)$/i.test(file)) {
                            // Convert filesystem path to URL path
                            // e.g., public/build/assets/app.js -> /build/assets/app.js
                            const relativePath = filePath.replace(publicDir, '').replace(/\\/g, '/');
                            fileList.push(relativePath);
                        }
                    }
                });

                return fileList;
            };

            const filesToCache = getFiles(buildDir);

            // Create Service Worker content
            const swContent = `
const CACHE_NAME = 'app-cache-${Date.now()}';
const ASSETS = ${JSON.stringify(filesToCache)};

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
`;

            fs.writeFileSync(swPath, swContent);
            console.log(`✅ Service Worker generated with ${filesToCache.length} assets.`);
        }
    };
};

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/assets',
                    dest: 'assets'
                }
            ]
        }),
        generateServiceWorker(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
