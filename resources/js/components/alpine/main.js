import registerAppStore from './stores/app.js'
import registerThemeStore from './stores/theme.js'
import registerBackgroundStore from './stores/background.js'

import password from "./data/password.js";
import greeting from "./data/greeting.js";
import home from "./data/home.js";
import shortcut from "./data/shortcut.js";
import scrollManager from "./data/scrollManager.js";
import mobile from "./data/mobile.js";
import search from "./data/search.js";
import menu from "./data/menu.js";
import timer from "./data/timer.js";
import settings from "./data/settings.js";
import palette from "./data/palette.js";
import fullscreen from "./data/fullscreen.js";
import googleTranslate from "./data/googleTranslate.js";
import occasion from "./data/occasion.js";
import sidebar from "./data/sidebar.js";
import share from "./data/share.js";
import background from "./data/background.js";
import feed from "./data/feed.js";
import links from "./data/links.js";
import gallery from "./data/gallery.js";
import report from "./data/report.js";
import filters from "./data/filters.js";
import taskboard from "./data/taskboard.js";
import profile from "./data/profile.js";
import calculator from "./data/calculator.js";
import stopwatch from "./data/stopwatch.js";
import dms from "./data/dms.js";
import ths from "./data/ths.js";
import reservation from "./data/reservation.js";
import radio from "./data/radio.js";
import contact from "./data/contact.js";
import energyChart from "./data/energyChart.js";


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
        Alpine.data('greeting', greeting)
        Alpine.data('home', home)
        Alpine.data('shortcut', shortcut)
        Alpine.data('scrollManager', scrollManager)
        Alpine.data('mobile', mobile)
        Alpine.data('search', search)
        Alpine.data('menu', menu)
        Alpine.data('timer', timer)
        Alpine.data('settings', settings)
        Alpine.data('palette', palette)
        Alpine.data('fullscreen', fullscreen)
        Alpine.data('googleTranslate', googleTranslate)
        Alpine.data('occasion', occasion)
        Alpine.data('sidebar', sidebar)
        Alpine.data('share', share)
        Alpine.data('background', background)
        Alpine.data('feed', feed)
        Alpine.data('links', links)
        Alpine.data('gallery', gallery)
        Alpine.data('report', report)
        Alpine.data('filters', filters)
        Alpine.data('taskboard', taskboard)
        Alpine.data('profile', profile)
        Alpine.data('calculator', calculator)
        Alpine.data('stopwatch', stopwatch)
        Alpine.data('dms', dms)
        Alpine.data('ths', ths)
        Alpine.data('reservation', reservation)
        Alpine.data('radio', radio)
        Alpine.data('contact', contact)
        Alpine.data('energyChart', energyChart)

    })
}
