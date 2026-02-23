import registerAppStore from './stores/app.js';
import registerThemeStore from './stores/theme.js';
import registerBackgroundStore from './stores/background.js';
import sidebar from './data/sidebar.js';
import menu from './data/menu.js';
import mobile from './data/mobile.js';
import scrollManager from './data/scrollManager.js';
import search from './data/search.js';

export default function initAlpine() {
    const modules = import.meta.glob('./data/*.js');
    const loaders = {};
    const critical = new Set(['sidebar', 'menu', 'mobile', 'scrollManager', 'search']);

    const loadComponent = (el) => {
        if (el._x_dataStack) return;

        const xData = el.getAttribute('x-data');
        if (!xData) return;

        const match = xData.match(/^\s*([a-zA-Z_$][a-zA-Z0-9_$]*)/);
        if (!match) return;

        const name = match[1];
        if (critical.has(name)) return;

        if (loaders[name]) {
            loaders[name].then(() => Alpine.initTree(el));
            return;
        }

        const path = `./data/${name}.js`;
        if (!modules[path]) return;

        loaders[name] = modules[path]().then((mod) => {
            Alpine.data(name, mod.default);
        }).catch(() => {
            delete loaders[name];
        });

        loaders[name].then(() => Alpine.initTree(el));
    };

    document.addEventListener('alpine:init', () => {
        registerAppStore(Alpine);
        registerThemeStore(Alpine);
        registerBackgroundStore(Alpine);

        Alpine.data('sidebar', sidebar);
        Alpine.data('menu', menu);
        Alpine.data('mobile', mobile);
        Alpine.data('scrollManager', scrollManager);
        Alpine.data('search', search);

        document.querySelectorAll('[x-data]').forEach(loadComponent);
    });

    document.addEventListener('livewire:init', () => {
        Livewire.hook('element.init', ({ el }) => loadComponent(el));
    });
}
