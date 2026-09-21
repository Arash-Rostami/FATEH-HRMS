import clipboardMixin from "../mixins/clipboard.js";
import focusModeMixin from "../mixins/focusMode.js";

const patternLoaders = {
    shapes: () => import("./patterns/shapes.js"),
    rain: () => import("./patterns/rain.js"),
    particle: () => import("./patterns/particle.js"),
    parallax: () => import("./patterns/parallax.js"),
    gradient: () => import("./patterns/gradient.js"),
    geometry: () => import("./patterns/geometry.js"),
    cloud: () => import("./patterns/cloud.js"),
    flora: () => import("./patterns/flora.js"),
    ambient: () => import("./patterns/ambient.js"),
    cyber: () => import("./patterns/cyber.js"),
    google: () => import("./patterns/google.js"),
    note: () => import("./patterns/note.js"),
    ripple: () => import("./patterns/ripple.js"),
    firefly: () => import("./patterns/firefly.js"),
    snow: () => import("./patterns/snow.js")
};

const BACKGROUND_ELEMENT_IDS = [
    'interactive-background',
    'interactive-background-apple',
    'interactive-background-google'
];

const PATTERN_SETTLE_DELAY = 50;
const RESET_RELOAD_DELAY = 500;
const TOAST_VISIBLE_MS = 1500;
const TOAST_FADE_MS = 200;

const EXPORT_KEYS = [
    'user-theme', 'user-mode', 'app-density',
    'backgroundEnabled', 'patternEnabled', 'activePattern',
    'fontSizeLevel', 'readingRuler', 'doubleClickCopy',
    'dms-col-hidden', 'ths-col-hidden', 'reservation-col-hidden',
    'chat-muted-channels', 'chat-muted-contacts', 'chat-muted-projects',
    'chat-push-channel', 'chat-push-contact', 'chat-push-project'
];

let activePatternInstance = null;
let patternInitRunId = 0;
let patternInitAbort = null;
let watcherOwner = null;

export default function settings() {
    return {
        ...clipboardMixin(),
        ...focusModeMixin(),
        open: false,
        focusDuration: null,
        currentPresence: 'onsite',

        _isDestroyed: false,

        get fontSizeLevel() { return this.$store.accessibility.fontSizeLevel; },
        get minScale() { return this.$store.accessibility.minScale; },
        get maxScale() { return this.$store.accessibility.maxScale; },
        get readingRuler() { return this.$store.accessibility.readingRuler; },
        get doubleClickCopy() { return this.$store.accessibility.doubleClickCopy; },

        get availablePatterns() {
            return this.$store?.background?.patterns || [];
        },

        init() {
            this._isDestroyed = false;
            this.currentPresence = this.$root?.dataset?.currentPresence || 'onsite';

            this.initFocusFromSnapshot();

            if (!this.$store.focus.active) {
                const serverUntil = parseInt(this.$root?.dataset?.focusUntil || '', 10);
                if (serverUntil && serverUntil > Date.now()) {
                    this.$store.focus.active = true;
                    this.$store.focus.until = serverUntil;
                    this.$store.focus.schedule(serverUntil);
                }
            }

            this.$nextTick(() => this.initPattern());

            if (watcherOwner === null) {
                watcherOwner = this;
                this.$watch('$store.background.patternEnabled', () => this.initPattern());
                this.$watch('$store.background.activePattern', () => this.initPattern());
                this.$watch('$store.background.enabled', (value) => {
                    if (value) this.clearVisuals();
                });
            }
        },

        increaseFontSize() { this.$store.accessibility.increaseFontSize(); },
        decreaseFontSize() { this.$store.accessibility.decreaseFontSize(); },
        resetFontSize() { this.$store.accessibility.resetFontSize(); },
        getScaleLabel() { return this.$store.accessibility.getScaleLabel(); },
        toggleReadingRuler() { this.$store.accessibility.toggleReadingRuler(); },
        toggleDoubleClickCopy() { this.$store.accessibility.toggleDoubleClickCopy(); },

        _copyToast() {
            const toast = document.createElement('div');
            toast.textContent = 'کپی شد';
            toast.classList.add('toast-floating');
            document.body.appendChild(toast);

            window.requestAnimationFrame(() => toast.classList.add('show'));

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), TOAST_FADE_MS);
            }, TOAST_VISIBLE_MS);
        },

        clearVisuals() {
            if (typeof activePatternInstance?.destroy === 'function') {
                try { activePatternInstance.destroy(); } catch (e) {}
            }
            activePatternInstance = null;

            for (let i = 0, len = BACKGROUND_ELEMENT_IDS.length; i < len; i++) {
                document.getElementById(BACKGROUND_ELEMENT_IDS[i])?.remove();
            }
        },

        async initPattern() {
            patternInitAbort?.abort();
            const controller = new AbortController();
            patternInitAbort = controller;
            const runId = ++patternInitRunId;

            this.clearVisuals();

            const store = this.$store?.background;
            if (!store?.patternEnabled) return;

            const currentPatternId = store.activePattern || 'shapes';
            const loader = patternLoaders[currentPatternId];
            if (!loader) return;

            try {
                const module = await loader();
                if (controller.signal.aborted || runId !== patternInitRunId || this._isDestroyed) return;

                await new Promise(r => setTimeout(r, PATTERN_SETTLE_DELAY));
                if (controller.signal.aborted || runId !== patternInitRunId || this._isDestroyed) return;

                const patternObj = module.default;
                if (typeof patternObj?.init === 'function') {
                    patternObj.init();
                    activePatternInstance = patternObj;
                } else if (typeof patternObj === 'function') {
                    activePatternInstance = patternObj();
                    if (typeof activePatternInstance === 'function') activePatternInstance();
                }
            } catch (e) {}
        },

        setPattern(patternId) {
            this.$store?.background?.setPattern?.(patternId);
        },

        toggleBackground() {
            const store = this.$store?.background;
            store?.toggleBackground?.(!store.enabled);
        },

        togglePattern() {
            const store = this.$store?.background;
            store?.togglePattern?.(!store.patternEnabled);
        },

        setFocusDuration(minutes) {
            this.focusDuration = minutes;
        },

        toggleFocus() {
            if (!this.$store.focus.active) {
                this.activateFocus(this.focusDuration ?? null, this.currentPresence);
            } else {
                this.deactivateFocus();
            }
        },

        resetAppearance() {
            window.ThemeManager?.setTheme('default');
            if (this.$store.density?.compact) this.$store.density.toggle();
            this.$store.background?.toggleBackground(false);
            this.$store.background?.togglePattern(false);
            this.resetFontSize();
            if (this.readingRuler) this.toggleReadingRuler();
            if (this.doubleClickCopy) this.toggleDoubleClickCopy();
        },

        resetNotifications() {
            ['channel', 'contact', 'project'].forEach(scope => this.$store.sound?.clearAll(scope));
        },

        resetTables() {
            this.$store.colVisibility?.reset('dms');
            this.$store.colVisibility?.reset('ths');
            this.$store.colVisibility?.reset('reservation');
        },

        exportSettings() {
            const data = {};
            EXPORT_KEYS.forEach(key => {
                const value = localStorage.getItem(key);
                if (value !== null) data[key] = value;
            });

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'fateh-settings.json';
            a.click();
            URL.revokeObjectURL(url);
        },

        importSettings(file) {
            if (!file) return;

            const reader = new FileReader();
            reader.onload = () => {
                try {
                    const data = JSON.parse(reader.result);
                    if (!data || typeof data !== 'object' || Array.isArray(data)) return;

                    EXPORT_KEYS.forEach(key => {
                        if (typeof data[key] === 'string') localStorage.setItem(key, data[key]);
                    });

                    location.reload();
                } catch (e) {}
            };
            reader.readAsText(file);
        },

        resetApp() {
            if (!confirm('همه تنظیمات بازنشانی شود؟')) return;

            if ('caches' in window) {
                caches.keys().then(names => names.forEach(n => caches.delete(n)));
            }
            navigator.serviceWorker?.getRegistrations().then(regs => regs.forEach(r => r.unregister()));
            try { localStorage.clear(); } catch (e) {}
            setTimeout(() => location.reload(), RESET_RELOAD_DELAY);
        },

        destroy() {
            this._isDestroyed = true;

            patternInitAbort?.abort();
            patternInitAbort = null;

            if (watcherOwner === this) watcherOwner = null;

            this.destroyFocusMode();
            this.clearVisuals();
        }
    };
}
