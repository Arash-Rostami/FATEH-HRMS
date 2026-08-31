import registerAppStore from './stores/app.js'
import registerThemeStore from './stores/theme.js'
import registerBackgroundStore from './stores/background.js'
import registerSoundStore from './stores/sound.js'
import registerPushStore from './stores/push.js'
import registerChromeStore from './stores/chrome.js'
import registerDensityStore from './stores/density.js'
import registerColVisibilityStore from './stores/colVisibility.js'
import registerPinStore from './stores/pinned.js'
import registerTaggedStore from './stores/tagged.js'
import registerActivityReactionPickerStore from './stores/activityReactionPicker.js'

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
import faq from "./data/faq.js";
import links from "./data/links.js";
import gallery from "./data/gallery.js";
import report from "./data/report.js";
import filters from "./data/filters.js";
import calculator from "./data/calculator.js";
import stopwatch from "./data/stopwatch.js";
import radio from "./data/radio.js";
import ambient from "./data/ambient.js";
import feedComposer from "./data/feedComposer.js";
import feedReactions from "./data/feedReactions.js";
import countdown from "./data/countdown.js";
import { calendarDrag, calendarNow, calendarResize, calendarView } from "./data/calendar.js";

export default function initAlpine() {
    document.addEventListener('alpine:init', () => {
        registerAppStore(Alpine)
        registerThemeStore(Alpine)
        registerBackgroundStore(Alpine)
        registerSoundStore(Alpine)
        registerPushStore(Alpine)
        registerChromeStore(Alpine)
        registerDensityStore(Alpine)
        registerColVisibilityStore(Alpine)
        registerPinStore(Alpine)
        registerTaggedStore(Alpine)
        registerActivityReactionPickerStore(Alpine)

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
        Alpine.data('faq', faq)
        Alpine.data('links', links)
        Alpine.data('gallery', gallery)
        Alpine.data('report', report)
        Alpine.data('filters', filters)
        Alpine.data('calculator', calculator)
        Alpine.data('stopwatch', stopwatch)
        Alpine.data('radio', radio)
        Alpine.data('ambient', ambient)
        Alpine.data('feedComposer', feedComposer)
        Alpine.data('feedReactions', feedReactions)
        Alpine.data('countdown', countdown)
        Alpine.data('calendarDrag', calendarDrag)
        Alpine.data('calendarNow', calendarNow)
        Alpine.data('calendarResize', calendarResize)
        Alpine.data('calendarView', calendarView)
    })
}
