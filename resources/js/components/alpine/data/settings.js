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
let patternInitPromise = null;
let patternInitRunId = 0;

export default function settings() {
    return {
        open: false,
        focusMode: false,

        get availablePatterns() {
            return Alpine.store('background').patterns;
        },

        init() {
            this.$nextTick(() => {
                this.initPattern();
            });

            this.$watch('$store.background.patternEnabled', () => this.initPattern());
            this.$watch('$store.background.activePattern', () => this.initPattern());

            this.$watch('$store.background.enabled', (value) => {
                if (value) {
                    this.clearVisuals();
                }
            });
        },

        clearVisuals() {
            if (activePatternInstance && typeof activePatternInstance.destroy === 'function') {
                try {
                    activePatternInstance.destroy();
                } catch (e) {}
            }

            activePatternInstance = null;

            ['interactive-background', 'interactive-background-apple', 'interactive-background-google'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.remove();
            });
        },

        async initPattern() {
            if (patternInitPromise) {
                return patternInitPromise;
            }

            const runId = ++patternInitRunId;

            patternInitPromise = (async () => {
                this.clearVisuals();

                if (!Alpine.store('background').patternEnabled) {
                    return null;
                }

                const currentPatternId = Alpine.store('background').activePattern || 'shapes';
                const loader = patternLoaders[currentPatternId];

                if (!loader) {
                    return null;
                }

                try {
                    const module = await loader();

                    if (runId !== patternInitRunId) {
                        return null;
                    }

                    const patternObj = module.default;

                    await new Promise(resolve => setTimeout(resolve, 50));

                    if (runId !== patternInitRunId) {
                        return null;
                    }

                    if (patternObj && typeof patternObj.init === 'function') {
                        patternObj.init();
                        activePatternInstance = patternObj;
                    } else if (typeof patternObj === 'function') {
                        activePatternInstance = patternObj();
                        if (typeof activePatternInstance === 'function') {
                            activePatternInstance();
                        }
                    }

                    return activePatternInstance;
                } catch (err) {
                    return null;
                }
            })();

            try {
                return await patternInitPromise;
            } finally {
                if (runId === patternInitRunId) {
                    patternInitPromise = null;
                }
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
                document.documentElement.requestFullscreen().catch(() => {});
                Alpine.store('background').patternEnabled = false;
                Alpine.store('background').enabled = false;
                this.$wire.call('setFocusMode', true);
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen().catch(() => {});
                }
                Alpine.store('background').enabled = true;
                this.$wire.call('setFocusMode', false);
            }
        },

        resetApp() {
            if ('caches' in window) {
                caches.keys().then((names) => {
                    names.forEach((name) => caches.delete(name));
                });
            }

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then((registrations) => {
                    registrations.forEach((registration) => registration.unregister());
                });
            }

            localStorage.clear();
            setTimeout(() => { location.reload(); }, 500);
        }
    };
}
