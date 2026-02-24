export default function settings() {
    return {
        open: false,
        confirmReset: false, // New state for custom modal
        focusMode: false,

        get backgroundEnabled() {
            return Alpine.store('background').enabled;
        },

        get patternEnabled() {
            return Alpine.store('background').patternEnabled;
        },

        toggleBackground() {
            Alpine.store('background').toggle(!this.backgroundEnabled);
        },

        togglePattern() {
            Alpine.store('background').togglePattern(!this.patternEnabled);
        },

        toggleFocus() {
            this.focusMode = !this.focusMode;
            if (this.focusMode) {
                document.documentElement.requestFullscreen().catch(() => {});
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
            this.confirmReset = false; // Close modal if open

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

            // Small delay to ensure async cleanup starts
            setTimeout(() => {
                location.reload();
            }, 100);
        }
    }
}
