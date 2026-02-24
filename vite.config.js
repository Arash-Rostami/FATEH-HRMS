import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import { VitePWA } from 'vite-plugin-pwa';

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
        VitePWA({
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            registerType: 'autoUpdate',
            outDir: 'public',
            manifest: {
                name: 'Intra Dashboard',
                short_name: 'Intra',
                description: 'The digital home for your work.',
                theme_color: '#000000',
                background_color: '#000000',
                display: 'standalone',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: 'assets/img/mining.svg', // Placeholder, using available asset
                        sizes: 'any',
                        type: 'image/svg+xml'
                    }
                ]
            },
            workbox: {
                cleanupOutdatedCaches: true,
                globPatterns: ['build/assets/**/*.{js,css,woff2,png,svg,jpg,ttf,woff}'],
                navigateFallback: null
            },
            devOptions: {
                enabled: true,
                type: 'module'
            }
        })
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**', '**/public/sw.js'],
        },
    },
});
