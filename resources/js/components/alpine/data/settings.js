import particle from "./particle.js";
import parallax from "./parallax.js";
import gradient from "./gradient.js";
import geometry from "./geometry.js";
import ambient from "./ambient.js";

const patterns = {
    particle,
    parallax,
    gradient,
    geometry,
    ambient
};


export default function settings() {
    return {
        open: false,
        focusMode: false,

        init() {
            this.initPattern();
        },

        get backgroundEnabled() {
            return Alpine.store('background').enabled;
        },

        get activePattern() {
            return Alpine.store('background').activePattern;
        },

        get availablePatterns() {
            return Alpine.store('background').patterns;
        },

        get patternEnabled() {
            return Alpine.store('background').patternEnabled;
        },

        initPattern() {
            document.getElementById('interactive-background')?.remove();

            if (this.patternEnabled) {
                const patternFunc = patterns[this.activePattern] || patterns['particle'];
                return patternFunc();
            }
        },

        setPattern(patternId) {
            Alpine.store('background').setPattern(patternId);
            this.initPattern();
        },

        toggleBackground() {
            Alpine.store('background').toggle(!this.backgroundEnabled);
        },

        togglePattern() {
            Alpine.store('background').togglePattern(!this.patternEnabled);
            return this.initPattern();
        },
        toggleFocus() {
            this.focusMode = !this.focusMode;
            if (this.focusMode) {
                document.documentElement.requestFullscreen().catch(() => {
                });
                Alpine.store('background').patternEnabled = false;
                Alpine.store('background').enabled = false;
                this.$wire.call('enableFocusMode');
            } else {
                if (document.exitFullscreen) document.exitFullscreen().catch(() => {
                });
                Alpine.store('background').enabled = true;
                this.$wire.call('disableFocusMode');
            }
        },

        resetApp() {
            // Clear Service Worker Caches
            if ('caches' in window) {
                caches.keys().then((names) => {
                    names.forEach((name) => {
                        caches.delete(name);
                    });
                });
            }

            // Unregister Service Workers
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then((registrations) => {
                    registrations.forEach((registration) => {
                        registration.unregister();
                    });
                });
            }

            localStorage.clear();
            setTimeout(() => {
                location.reload();
            }, 500);
        }
    }
}
