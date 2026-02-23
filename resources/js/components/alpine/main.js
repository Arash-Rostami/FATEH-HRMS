import registerAppStore from './stores/app.js';
import registerThemeStore from './stores/theme.js';
import registerBackgroundStore from './stores/background.js';
import sidebar from './data/sidebar.js';
import menu from './data/menu.js';
import mobile from './data/mobile.js';
import scrollManager from './data/scrollManager.js';
import search from './data/search.js';
import timer from './data/timer.js';
import fullscreen from './data/fullscreen.js';
// Removed notification import as it might not exist

export default function initAlpine() {
    const modules = import.meta.glob('./data/*.js');
    const loaded = {};

    document.addEventListener('alpine:init', () => {
        registerAppStore(Alpine);
        registerThemeStore(Alpine);
        registerBackgroundStore(Alpine);

        Alpine.data('sidebar', sidebar);
        Alpine.data('menu', menu);
        Alpine.data('mobile', mobile);
        Alpine.data('scrollManager', scrollManager);
        Alpine.data('search', search);
        Alpine.data('timer', timer);
        Alpine.data('fullscreen', fullscreen);

        const criticalNames = [
            'sidebar',
            'menu',
            'mobile',
            'scrollManager',
            'search',
            'timer',
            'fullscreen'
        ];

        for (const path in modules) {
            const match = path.match(/\/([^\/]+)\.js$/);
            if (!match) continue;

            const name = match[1];
            if (criticalNames.includes(name)) continue;

            Alpine.data(name, (...args) => ({
                async init() {
                    if (loaded[name]) {
                        Object.assign(this, loaded[name](...args));
                        if (this.init) this.init.call(this);
                        return;
                    }

                    try {
                        const module = await modules[path]();
                        loaded[name] = module.default;

                        const component = loaded[name](...args);
                        Object.assign(this, component);

                        if (component.init) component.init.call(this);
                    } catch {
                        // Fail silently
                    }
                }
            }));
        }
    });
}
