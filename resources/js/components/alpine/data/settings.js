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

let activePatternInstance = null;
let patternInitRunId = 0;
let patternInitAbort = null;

export default function settings() {
    return {
        open: false,
        focusMode: false,
        fontSizeLevel: 0,
        minScale: -2,
        maxScale: 3,
        readingRuler: false,
        doubleClickCopy: false,


        get availablePatterns() {
            return Alpine.store('background').patterns;
        },

        init() {
            this.fontSizeLevel = parseInt(localStorage.getItem('fontSizeLevel') || '0');
            this.readingRuler = localStorage.getItem('readingRuler') === 'true';
            this.doubleClickCopy = localStorage.getItem('doubleClickCopy') === 'true';
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
            const scale = 1 + (this.fontSizeLevel * 0.1);
            document.documentElement.style.setProperty('--app-font-scale', scale);
            document.documentElement.style.fontSize = `${scale * 100}%`;
            localStorage.setItem('fontSizeLevel', this.fontSizeLevel);
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
            localStorage.setItem('readingRuler', this.readingRuler);
            this.applyReadingRuler();
        },

        applyReadingRuler() {
            document.documentElement.classList.toggle('reading-ruler', this.readingRuler);
            if (this.readingRuler) {
                document.addEventListener('mousemove', this._rulerHandler = (e) => {
                    document.documentElement.style.setProperty('--ruler-y', `${e.clientY}px`);
                });
            } else {
                document.removeEventListener('mousemove', this._rulerHandler);
            }
        },

        toggleDoubleClickCopy() {
            this.doubleClickCopy = !this.doubleClickCopy;
            localStorage.setItem('doubleClickCopy', this.doubleClickCopy);
            this.applyDoubleClickCopy();
        },

        applyDoubleClickCopy() {
            if (this.doubleClickCopy) {
                document.addEventListener('mouseup', this._copyHandler = () => {
                    const selection = window.getSelection()?.toString().trim();
                    if (selection) {
                        navigator.clipboard.writeText(selection).then(() => {
                            this._showCopyToast();
                        });
                    }
                });
            } else {
                document.removeEventListener('mouseup', this._copyHandler);
            }
        },

        _showCopyToast() {
            const toast = document.createElement('div');
            toast.textContent = 'کپی شد';
            toast.classList.add('toast-floating');
            document.body.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));

            setTimeout(() => {
                toast.classList.remove('show');

                setTimeout(() => toast.remove(), 200);
            }, 1500);
        },

        clearVisuals() {
            if (typeof activePatternInstance?.destroy === 'function') {
                try {
                    activePatternInstance.destroy();
                } catch {
                }
            }
            activePatternInstance = null;
            ['interactive-background', 'interactive-background-apple', 'interactive-background-google']
                .forEach(id => document.getElementById(id)?.remove());
        },

        async initPattern() {
            patternInitAbort?.abort();
            const controller = new AbortController();
            patternInitAbort = controller;
            const runId = ++patternInitRunId;

            this.clearVisuals();

            if (!Alpine.store('background').patternEnabled) return;

            const currentPatternId = Alpine.store('background').activePattern || 'shapes';
            const loader = patternLoaders[currentPatternId];
            if (!loader) return;

            try {
                const module = await loader();
                if (controller.signal.aborted || runId !== patternInitRunId) return;

                await new Promise(r => setTimeout(r, 50));
                if (controller.signal.aborted || runId !== patternInitRunId) return;

                const patternObj = module.default;
                if (typeof patternObj?.init === 'function') {
                    patternObj.init();
                    activePatternInstance = patternObj;
                } else if (typeof patternObj === 'function') {
                    activePatternInstance = patternObj();
                    if (typeof activePatternInstance === 'function') activePatternInstance();
                }
            } catch {
            }
        },

        setPattern(patternId) {
            Alpine.store('background').setPattern(patternId);
        },

        toggleBackground() {
            Alpine.store('background').toggleBackground(!Alpine.store('background').enabled);
        },

        togglePattern() {
            Alpine.store('background').togglePattern(!Alpine.store('background').patternEnabled);
        },

        toggleFocus() {
            this.focusMode = !this.focusMode;

            if (this.focusMode) {
                document.documentElement.requestFullscreen().catch(() => {
                });
                Alpine.store('background').patternEnabled = false;
                Alpine.store('background').enabled = false;
                this.$wire.call('setFocusMode', true);
            } else {
                document.exitFullscreen?.().catch(() => {
                });
                Alpine.store('background').enabled = true;
                this.$wire.call('setFocusMode', false);
            }
        },

        resetApp() {
            if ('caches' in window) {
                caches.keys().then(names => names.forEach(n => caches.delete(n)));
            }
            navigator.serviceWorker?.getRegistrations().then(regs => regs.forEach(r => r.unregister()));
            localStorage.clear();
            setTimeout(() => location.reload(), 500);
        }
    };
}
