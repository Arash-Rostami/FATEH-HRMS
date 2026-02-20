import registerAppStore from './stores/app.js'
import registerThemeStore from './stores/theme.js'
import registerBackgroundStore from './stores/background.js'

import password from "./data/password.js";
import menu from "./data/menu.js";
import shapes from "./data/shapes.js";
import timer from "./data/timer.js";
import settings from "./data/settings.js";
import palette from "./data/palette.js";
import fullscreen from "./data/fullscreen.js";
import sidebar from "./data/sidebar.js";
import share from "./data/share.js";
import background from "./data/background.js";
import feed from "./data/feed.js";
import links from "./data/links.js";
import gallery from "./data/gallery.js";
import report from "./data/report.js";


export default function initAlpine() {
    document.addEventListener('alpine:init', () => {

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
         | Register Components
         |--------------------------------------------------------------------------
         */
        Alpine.data('password', password)
        Alpine.data('menu', menu)
        Alpine.data('shapes', shapes)
        Alpine.data('timer', timer)
        Alpine.data('settings', settings)
        Alpine.data('palette', palette)
        Alpine.data('fullscreen', fullscreen)
        Alpine.data('sidebar', sidebar)
        Alpine.data('share', share)
        Alpine.data('background', background)
        Alpine.data('feed', feed)
        Alpine.data('links', links)
        Alpine.data('gallery', gallery)
        Alpine.data('report', report)
    })
}
