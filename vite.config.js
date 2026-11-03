import {defineConfig} from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import {viteStaticCopy} from 'vite-plugin-static-copy'
import {VitePWA} from 'vite-plugin-pwa'
import {existsSync, readdirSync, renameSync, rmSync} from 'node:fs'

const dropSiblingOriginals = () => ({
    name: 'drop-sibling-originals',
    apply: 'build',
    closeBundle: {
        order: 'post',
        handler() {
            const img = 'public/build/assets/img';
            if (!existsSync(img)) return;
            for (const tenant of readdirSync(img)) {
                const dir = `${img}/${tenant}/sibling`;
                if (!existsSync(dir)) continue;
                const optimized = `${dir}/optimized`;
                if (!existsSync(optimized)) continue;
                for (const entry of readdirSync(dir)) if (entry !== 'optimized') rmSync(`${dir}/${entry}`, {recursive: true, force: true});
                for (const file of readdirSync(optimized)) renameSync(`${optimized}/${file}`, `${dir}/${file}`);
                rmSync(optimized, {recursive: true, force: true});
            }
        },
    },
})

export default defineConfig({
    plugins: [
        tailwindcss(),

        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/core/filament.css',
                'resources/js/app.js',
                'resources/js/auth.js',
                'resources/js/core/filament.js',

                'resources/js/core/theme-manager.js',
                'resources/js/core/tooltip.js',
                'resources/js/core/filament.js',
                'resources/js/core/topbar-autohide.js',
                'resources/js/core/nav-dock.js',
                'resources/js/core/auto-close.js',
                'resources/js/core/table-density.js',
                'resources/js/core/recents.js',
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

        dropSiblingOriginals(),

        viteStaticCopy({
            targets: [
                {src: 'resources/assets/audio/*.mp3', dest: 'assets/audio'},
                {src: 'resources/assets/video', dest: 'assets'},
                {src: 'resources/assets/img', dest: 'assets'},
                {src: 'resources/assets/fonts', dest: 'assets'},
                {src: 'resources/assets/js', dest: 'assets'},
                // {src: 'resources/fallback', dest: '.'},
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
