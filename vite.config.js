import {defineConfig} from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import {viteStaticCopy} from 'vite-plugin-static-copy'
import {VitePWA} from 'vite-plugin-pwa'

export default defineConfig({
    plugins: [
        tailwindcss(),

        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/core/filament.css',
                'resources/js/app.js',
                'resources/js/core/filament.js',

                'resources/js/core/theme-manager.js',
                'resources/js/core/filament.js',
                'resources/js/core/topbar-autohide.js',
                'resources/js/core/nav-dock.js',
                'resources/js/components/alpine/stores/filament-menu.js',

                'resources/js/components/alpine/modules/dms.js',
                'resources/js/components/alpine/modules/ths.js',
                'resources/js/components/alpine/modules/taskboard.js',
                'resources/js/components/alpine/modules/project.js',
                'resources/js/components/alpine/modules/channel.js',
                'resources/js/components/alpine/modules/contact.js',
                'resources/js/components/alpine/modules/reservation.js',
                'resources/js/components/alpine/modules/profile.js',
                'resources/js/components/alpine/modules/analytics.js',
                'resources/js/components/alpine/modules/energy.js',
                'resources/js/components/alpine/modules/tasksheet.js',
            ],
            refresh: true,
        }),

        viteStaticCopy({
            targets: [
                {src: 'resources/assets/audio', dest: 'assets'},
                {src: 'resources/assets/video', dest: 'assets'},
                {src: 'resources/assets/img', dest: 'assets'},
                {src: 'resources/assets/fonts', dest: 'assets'},
                {src: 'resources/assets/js', dest: 'assets'},
                {
                    src: 'node_modules/material-symbols/rounded.css',
                    dest: 'assets/material-symbols'
                },
                {
                    src: 'node_modules/material-symbols/material-symbols-rounded.woff2',
                    dest: 'assets/material-symbols'
                },
            ]
        }),
        VitePWA({
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            outDir: 'public',
            registerType: 'autoUpdate',
            devOptions: {
                enabled: true,
                type: 'module'
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
            }
        })
    ],
    build: {
        manifest: 'manifest.json',
        outDir: 'public/build',
        emptyOutDir: true,
    }
})
