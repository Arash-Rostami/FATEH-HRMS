import {THEME_COLORS} from '../components/alpine/stores/theme.js';

const THEME_KEY = 'user-theme';
const MODE_KEY = 'user-mode';

export default class ThemeManager {
    static initialized = false;

    static getColors() {
        return THEME_COLORS;
    }

    static getSystemMode() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
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
            document.documentElement.removeAttribute('data-theme');
        } else {
            document.documentElement.setAttribute('data-theme', theme);
        }
    }

    static applyModeDOM(mode) {
        if (mode === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    static setTheme(theme) {
        this.applyThemeDOM(theme);
        localStorage.setItem(THEME_KEY, theme);
        this.syncStore();
        this.syncThemeColorMeta();
        this.dispatchSyncEvent();
    }

    static toggleMode() {
        const newMode = this.getUserMode() === 'dark' ? 'light' : 'dark';

        this.applyModeDOM(newMode);
        localStorage.setItem(MODE_KEY, newMode);

        this.syncStore();
        this.syncThemeColorMeta();
        this.dispatchSyncEvent();
    }

    static syncThemeColorMeta() {
        const meta = document.querySelector('meta[name="theme-color"]');
        if (!meta) return;

        const value = getComputedStyle(document.documentElement)
            .getPropertyValue('--md-sys-color-primary')
            .trim();

        if (value) meta.setAttribute('content', value);
    }

    static syncStore(forcedTheme = null, forcedMode = null) {
        if (!window.Alpine || !window.Alpine.store('appTheme')) return;

        const theme = forcedTheme || this.getUserTheme();
        const mode = forcedMode || this.getUserMode();
        window.Alpine.store('appTheme').updateState(theme, mode);
    }

    static dispatchSyncEvent() {
        window.dispatchEvent(new CustomEvent('theme-system-updated'));
    }
}

ThemeManager.init();
