import shapes from "./shapes.js";
import particle from "./particle.js";
import parallax from "./parallax.js";
import gradient from "./gradient.js";
import geometry from "./geometry.js";
import ambient from "./ambient.js";
import apple from "./apple.js";
import google from "./google.js";

const patterns = {
    shapes,
    particle,
    parallax,
    gradient,
    geometry,
    ambient,
    apple,
    google
};

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
            Object.values(patterns).forEach(p => {
                if (p && typeof p.destroy === 'function') {
                    try {
                        p.destroy();
                    } catch (e) {}
                }
            });

            ['interactive-background', 'interactive-background-apple', 'interactive-background-google'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.remove();
            });
        },

        initPattern() {
            this.clearVisuals();

            if (Alpine.store('background').patternEnabled) {
                const currentPatternId = Alpine.store('background').activePattern || 'shapes';
                const patternObj = patterns[currentPatternId];

                if (patternObj) {
                    setTimeout(() => {
                        if (typeof patternObj.init === 'function') {
                            patternObj.init();
                        } else if (typeof patternObj === 'function') {
                            const runner = patternObj();
                            if (typeof runner === 'function') runner();
                        }
                    }, 50);
                }
            }
        },

        setPattern(patternId) {
            Alpine.store('background').setPattern(patternId);
        },

        toggleBackground() {
            Alpine.store('background').toggle(!Alpine.store('background').enabled);
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
                this.$wire.call('enableFocusMode');
            } else {
                if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
                Alpine.store('background').enabled = true;
                this.$wire.call('disableFocusMode');
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
            setTimeout(() => {
                location.reload();
            }, 500);
        }
    }
}
