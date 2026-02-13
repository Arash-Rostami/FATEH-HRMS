import registerAppStore from './stores/app.js'
import registerThemeStore from './stores/theme.js'
import password from "./data/password.js";
import floatingShapes from "./data/floatingShapes.js";


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
        Alpine.data('floatingShapes', floatingShapes)

    })
}
