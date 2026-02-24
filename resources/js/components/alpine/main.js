// import registerAppStore from './stores/app.js'
// import registerThemeStore from './stores/theme.js'
// import registerBackgroundStore from './stores/background.js'
//
// import password from "./data/password.js";
// import greeting from "./data/greeting.js";
// import scrollManager from "./data/scrollManager.js";
// import mobile from "./data/mobile.js";
// import search from "./data/search.js";
// import menu from "./data/menu.js";
// import shapes from "./data/shapes.js";
// import timer from "./data/timer.js";
// import settings from "./data/settings.js";
// import palette from "./data/palette.js";
// import fullscreen from "./data/fullscreen.js";
// import googleTranslate from "./data/googleTranslate.js";
// import occasion from "./data/occasion.js";
// import sidebar from "./data/sidebar.js";
// import share from "./data/share.js";
// import background from "./data/background.js";
// import feed from "./data/feed.js";
// import links from "./data/links.js";
// import gallery from "./data/gallery.js";
// import report from "./data/report.js";
// import filters from "./data/filters.js";
//
//
// export default function initAlpine() {
//     document.addEventListener('alpine:init', () => {
//
//         /*
//          |--------------------------------------------------------------------------
//          | Register Stores
//          |--------------------------------------------------------------------------
//          */
//         registerAppStore(Alpine)
//         registerThemeStore(Alpine)
//         registerBackgroundStore(Alpine)
//
//         /*
//          |--------------------------------------------------------------------------
//          | Register Components
//          |--------------------------------------------------------------------------
//          */
//         Alpine.data('password', password)
//         Alpine.data('greeting', greeting)
//         Alpine.data('scrollManager', scrollManager)
//         Alpine.data('mobile', mobile)
//         Alpine.data('search', search)
//         Alpine.data('menu', menu)
//         Alpine.data('shapes', shapes)
//         Alpine.data('timer', timer)
//         Alpine.data('settings', settings)
//         Alpine.data('palette', palette)
//         Alpine.data('fullscreen', fullscreen)
//         Alpine.data('googleTranslate', googleTranslate)
//         Alpine.data('occasion', occasion)
//         Alpine.data('sidebar', sidebar)
//         Alpine.data('share', share)
//         Alpine.data('background', background)
//         Alpine.data('feed', feed)
//         Alpine.data('links', links)
//         Alpine.data('gallery', gallery)
//         Alpine.data('report', report)
//         Alpine.data('filters', filters)
//     })
// }
import registerAppStore from './stores/app.js';
import registerThemeStore from './stores/theme.js';
import registerBackgroundStore from './stores/background.js';
import sidebar from './data/sidebar.js';
import menu from './data/menu.js';
import mobile from './data/mobile.js';
import scrollManager from './data/scrollManager.js';
import search from './data/search.js';
import fullscreen from './data/fullscreen.js';

export default function initAlpine() {
    const modules = import.meta.glob('./data/*.js');
    const loaded = Object.create(null);

    const stores = [
        registerAppStore,
        registerThemeStore,
        registerBackgroundStore
    ];

    const eagerComponents = {
        sidebar,
        menu,
        mobile,
        scrollManager,
        search,
        fullscreen
    };

    document.addEventListener('alpine:init', () => {
        stores.forEach(store => store(Alpine));

        for (const [name, component] of Object.entries(eagerComponents)) {
            Alpine.data(name, component);
        }

        for (const path in modules) {
            const name = path.slice(7, -3);

            if (name in eagerComponents) continue;

            Alpine.data(name, (...args) => ({
                async init() {
                    if (!loaded[name]) {
                        try {
                            const module = await modules[path]();
                            loaded[name] = module.default;
                        } catch {
                            return;
                        }
                    }

                    const component = loaded[name](...args);
                    Object.assign(this, component);

                    if (typeof component.init === 'function') {
                        component.init.call(this);
                    }
                }
            }));
        }
    });
}
