import AsyncAlpine from 'async-alpine';
import registerAppStore from './stores/app.js'
import registerThemeStore from './stores/theme.js'
import registerBackgroundStore from './stores/background.js'

export default function initAlpine() {
    document.addEventListener('alpine:init', () => {
        // Ensure we are working with the correct Alpine instance (usually window.Alpine with Livewire v3)
        const Alpine = window.Alpine;

        if (!Alpine) {
            console.error('Alpine is not defined!');
            return;
        }

        AsyncAlpine.init(Alpine);

        /*
         |--------------------------------------------------------------------------
         | Register Stores
         |--------------------------------------------------------------------------
         */
        registerAppStore(Alpine)
        registerThemeStore(Alpine)
        registerBackgroundStore(Alpine)

        /*
         |--------------------------------------------------------------------------
         | Register Components (Lazy Loaded)
         |--------------------------------------------------------------------------
         */
        AsyncAlpine.data('password', () => import('./data/password.js'))
        AsyncAlpine.data('menu', () => import('./data/menu.js'))
        AsyncAlpine.data('shapes', () => import('./data/shapes.js'))
        AsyncAlpine.data('timer', () => import('./data/timer.js'))
        AsyncAlpine.data('settings', () => import('./data/settings.js'))
        AsyncAlpine.data('palette', () => import('./data/palette.js'))
        AsyncAlpine.data('fullscreen', () => import('./data/fullscreen.js'))
        AsyncAlpine.data('sidebar', () => import('./data/sidebar.js'))
        AsyncAlpine.data('share', () => import('./data/share.js'))
        AsyncAlpine.data('background', () => import('./data/background.js'))
        AsyncAlpine.data('feed', () => import('./data/feed.js'))
        AsyncAlpine.data('links', () => import('./data/links.js'))
        AsyncAlpine.data('gallery', () => import('./data/gallery.js'))

        AsyncAlpine.start();
    })
}
