import registerAppStore from './stores/app.js'
import registerThemeStore from './stores/theme.js'
import password from "./data/password.js";
import menu from "./data/menu.js";
import shapes from "./data/shapes.js";
import timer from "./data/timer.js";
import settings from "./data/settings.js";
import palette from "./data/palette.js";
import fullscreen from "./data/fullscreen.js";


export default function initAlpine() {
    document.addEventListener('alpine:init', () => {

        /*
         |--------------------------------------------------------------------------
         | Register Stores
         |--------------------------------------------------------------------------
         */
        registerAppStore(Alpine)
        registerThemeStore(Alpine)

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
    })
}
