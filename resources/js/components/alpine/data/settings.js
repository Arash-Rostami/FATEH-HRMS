import shapes from "./patterns/shapes.js";
import particle from "./patterns/particle.js";
import parallax from "./patterns/parallax.js";
import gradient from "./patterns/gradient.js";
import geometry from "./patterns/geometry.js";
import flora from "./patterns/flora.js";
import ambient from "./patterns/ambient.js";
import cyber from "./patterns/cyber.js";
import google from "./patterns/google.js";
import note from "./patterns/note.js";
import ripple from "./patterns/ripple.js";
import cloud from "./patterns/cloud.js";
import rain from "./patterns/rain.js";
import snow from "./patterns/snow.js";
import firefly from "./patterns/firefly.js";


const patterns = {
    shapes,
    rain,
    particle,
    parallax,
    gradient,
    geometry,
    cloud,
    flora,
    ambient,
    cyber,
    google,
    note,
    ripple,
    firefly,
    snow
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
            setTimeout(() => {location.reload();}, 500);
        }
    }
}
