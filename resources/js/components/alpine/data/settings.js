import clipboardMixin from "../mixins/clipboard.js";

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

const LS_FONT_SIZE = 'fontSizeLevel';
const LS_READING_RULER = 'readingRuler';
const LS_DOUBLE_CLICK_COPY = 'doubleClickCopy';

const SCALE_STEP = 0.1;
const PATTERN_SETTLE_DELAY = 50;
const RESET_RELOAD_DELAY = 500;
const TOAST_VISIBLE_MS = 1500;
const TOAST_FADE_MS = 200;

let activePatternInstance = null;
let patternInitRunId = 0;
let patternInitAbort = null;

export default function settings() {
    return {
        ...clipboardMixin(),
        open: false,
        focusMode: false,
        fontSizeLevel: 0,
        minScale: -2,
        maxScale: 3,
        readingRuler: false,
        doubleClickCopy: false,

        _isDestroyed: false,
        _rulerHandler: null,
        _rulerRaf: null,
        _copyHandler: null,

        get availablePatterns() {
            return this.$store?.background?.patterns || [];
        },

        init() {
            this._isDestroyed = false;

            try {
                this.fontSizeLevel = parseInt(localStorage.getItem(LS_FONT_SIZE) || '0');
                this.readingRuler = localStorage.getItem(LS_READING_RULER) === 'true';
                this.doubleClickCopy = localStorage.getItem(LS_DOUBLE_CLICK_COPY) === 'true';
            } catch (e) {}

            this.applyDoubleClickCopy();
            this.applyFontSize();
            this.applyReadingRuler();

            this.$nextTick(() => this.initPattern());

            this.$watch('$store.background.patternEnabled', () => this.initPattern());
            this.$watch('$store.background.activePattern', () => this.initPattern());
            this.$watch('$store.background.enabled', (value) => {
                if (value) this.clearVisuals();
            });
        },

        applyFontSize() {
            const scale = 1 + (this.fontSizeLevel * SCALE_STEP);
            document.documentElement.style.setProperty('--app-font-scale', scale);
            document.documentElement.style.fontSize = `${scale * 100}%`;
            try { localStorage.setItem(LS_FONT_SIZE, this.fontSizeLevel); } catch (e) {}
        },

        increaseFontSize() {
            if (this.fontSizeLevel < this.maxScale) {
                this.fontSizeLevel++;
                this.applyFontSize();
            }
        },

        decreaseFontSize() {
            if (this.fontSizeLevel > this.minScale) {
                this.fontSizeLevel--;
                this.applyFontSize();
            }
        },

        resetFontSize() {
            this.fontSizeLevel = 0;
            this.applyFontSize();
        },

        getScaleLabel() {
            if (this.fontSizeLevel < 0) return 'کوچک';
            if (this.fontSizeLevel === 0) return 'پیش‌فرض';
            if (this.fontSizeLevel === 1) return 'بزرگ';
            return 'خیلی بزرگ';
        },

        toggleReadingRuler() {
            this.readingRuler = !this.readingRuler;
            try { localStorage.setItem(LS_READING_RULER, this.readingRuler); } catch (e) {}
            this.applyReadingRuler();
        },

        applyReadingRuler() {
            document.documentElement.classList.toggle('reading-ruler', this.readingRuler);

            if (this._rulerHandler) {
                document.removeEventListener('mousemove', this._rulerHandler);
                this._rulerHandler = null;
            }

            if (this._rulerRaf) {
                window.cancelAnimationFrame(this._rulerRaf);
                this._rulerRaf = null;
            }

            if (this.readingRuler) {
                this._rulerHandler = (e) => {
                    if (this._rulerRaf) return;
                    this._rulerRaf = window.requestAnimationFrame(() => {
                        this._rulerRaf = null;
                        document.documentElement.style.setProperty('--ruler-y', `${e.clientY}px`);
                    });
                };
                document.addEventListener('mousemove', this._rulerHandler, { passive: true });
            }
        },

        toggleDoubleClickCopy() {
            this.doubleClickCopy = !this.doubleClickCopy;
            try { localStorage.setItem(LS_DOUBLE_CLICK_COPY, this.doubleClickCopy); } catch (e) {}
            this.applyDoubleClickCopy();
        },

        applyDoubleClickCopy() {
            if (this._copyHandler) {
                document.removeEventListener('mouseup', this._copyHandler);
                this._copyHandler = null;
            }

            if (this.doubleClickCopy) {
                this._copyHandler = () => {
                    const selection = window.getSelection()?.toString().trim();
                    if (selection) this.copyText(selection, 'کپی شد');
                };
                document.addEventListener('mouseup', this._copyHandler, { passive: true });
            }
        },

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

        toggleFocus() {
            this.focusMode = !this.focusMode;
            const store = this.$store?.background;

            if (this.focusMode) {
                document.documentElement.requestFullscreen?.().catch(() => {});
                if (store) {
                    store.patternEnabled = false;
                    store.enabled = false;
                }
                this.$wire?.call('setFocusMode', true);
            } else {
                document.exitFullscreen?.().catch(() => {});
                if (store) {
                    store.enabled = true;
                }
                this.$wire?.call('setFocusMode', false);
            }
        },

        resetApp() {
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

            if (this._rulerHandler) {
                document.removeEventListener('mousemove', this._rulerHandler);
                this._rulerHandler = null;
            }

            if (this._rulerRaf) {
                window.cancelAnimationFrame(this._rulerRaf);
                this._rulerRaf = null;
            }

            if (this._copyHandler) {
                document.removeEventListener('mouseup', this._copyHandler);
                this._copyHandler = null;
            }

            this.clearVisuals();
        }
    };
}
