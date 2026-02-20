import registerAppStore from './stores/app.js'
import registerThemeStore from './stores/theme.js'
import registerBackgroundStore from './stores/background.js'

export default function initAlpine() {
    document.addEventListener('alpine:init', () => {

        const Alpine = window.Alpine;

        // Custom Lazy Loader for Alpine Components
        const lazy = (name, loader) => {
            Alpine.data(name, (...args) => ({
                init() {
                    loader()
                        .then(module => {
                            const componentFactory = module.default;

                            let realComponent;
                            // Check if the default export is a function (factory pattern)
                            if (typeof componentFactory === 'function') {
                                realComponent = componentFactory(...args);
                            } else {
                                // If it's just an object export
                                realComponent = componentFactory;
                            }

                            // 1. Assign properties to the component instance ('this')
                            //    We iterate to ensure we copy all enumerable properties.
                            Object.keys(realComponent).forEach(key => {
                                // Skip 'init' for now to avoid premature execution or overwriting
                                if (key !== 'init') {
                                    this[key] = realComponent[key];
                                }
                            });

                            // 2. Explicitly call the component's init() method if it exists.
                            //    This ensures setup logic runs with the correct context.
                            if (realComponent.init) {
                                realComponent.init.call(this);
                            }
                        })
                        .catch(err => console.error(`Failed to lazy load Alpine component: ${name}`, err));
                }
            }));
        };

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
        lazy('password', () => import('./data/password.js'))
        lazy('menu', () => import('./data/menu.js'))
        lazy('shapes', () => import('./data/shapes.js'))
        lazy('timer', () => import('./data/timer.js'))
        lazy('settings', () => import('./data/settings.js'))
        lazy('palette', () => import('./data/palette.js'))
        lazy('fullscreen', () => import('./data/fullscreen.js'))
        lazy('sidebar', () => import('./data/sidebar.js'))
        lazy('share', () => import('./data/share.js'))
        lazy('background', () => import('./data/background.js'))
        lazy('feed', () => import('./data/feed.js'))
        lazy('links', () => import('./data/links.js'))
        lazy('gallery', () => import('./data/gallery.js'))
    })
}
