import { THEME_COLORS } from '../components/alpine/stores/theme.js';

const THEME_KEY = 'user-theme';
const MODE_KEY = 'user-mode';
const DARK_MODE_QUERY = '(prefers-color-scheme: dark)';
const THEME_META_SELECTOR = 'meta[name="theme-color"]';
const THEME_COLOR_PROPERTY = '--md-sys-color-primary';
const SYNC_EVENT_NAME = 'theme-system-updated';

const root = document.documentElement;

export default class ThemeManager {
    static initialized = false;
    static _mediaQuery = null;

    static getColors() {
        return THEME_COLORS;
    }

    static getSystemMode() {
        if (!this._mediaQuery) {
            this._mediaQuery = window.matchMedia(DARK_MODE_QUERY);
        }
        return this._mediaQuery.matches ? 'dark' : 'light';
    }

    static getUserMode() {
        return localStorage.getItem(MODE_KEY) || this.getSystemMode();
    }

    static getUserTheme() {
        return localStorage.getItem(THEME_KEY) || 'default';
    }

    static init() {
        if (this.initialized) return;
        this.initialized = true;

        window.ThemeManager = this;
        window.AdminThemeManager = this;

        this.applyPreferences();

        window.addEventListener('storage', (event) => {
            if (event.key === THEME_KEY || event.key === MODE_KEY) {
                this.applyPreferences();
            }
        });

        document.addEventListener('livewire:navigated', () => {
            this.applyPreferences();
        });

        document.addEventListener('alpine:init', () => {
            this.syncStore();
        });
    }

    static applyPreferences() {
        const theme = this.getUserTheme();
        const mode = this.getUserMode();

        this.applyThemeDOM(theme);
        this.applyModeDOM(mode);
        this.syncStore(theme, mode);
        this.syncThemeColorMeta();
        this.dispatchSyncEvent();
    }

    static applyThemeDOM(theme) {
        if (theme === 'default') {
            root.removeAttribute('data-theme');
        } else {
            root.setAttribute('data-theme', theme);
        }
    }

    static applyModeDOM(mode) {
        root.classList.toggle('dark', mode === 'dark');
    }

    static setTheme(theme) {
        this.applyThemeDOM(theme);
        localStorage.setItem(THEME_KEY, theme);
        this.syncStore(theme);
        this.syncThemeColorMeta();
        this.dispatchSyncEvent();
    }

    static toggleMode() {
        const newMode = this.getUserMode() === 'dark' ? 'light' : 'dark';

        this.applyModeDOM(newMode);
        localStorage.setItem(MODE_KEY, newMode);

        this.syncStore(null, newMode);
        this.syncThemeColorMeta();
        this.dispatchSyncEvent();
    }

    static syncThemeColorMeta() {
        const meta = document.querySelector(THEME_META_SELECTOR);
        if (!meta) return;

        const value = getComputedStyle(root)
            .getPropertyValue(THEME_COLOR_PROPERTY)
            .trim();

        if (value) meta.setAttribute('content', value);
    }

    static syncStore(forcedTheme = null, forcedMode = null) {
        const store = window.Alpine?.store('appTheme');
        if (!store) return;

        const theme = forcedTheme || this.getUserTheme();
        const mode = forcedMode || this.getUserMode();
        store.updateState(theme, mode);
    }

    static dispatchSyncEvent() {
        window.dispatchEvent(new CustomEvent(SYNC_EVENT_NAME));
    }
}

ThemeManager.init();
