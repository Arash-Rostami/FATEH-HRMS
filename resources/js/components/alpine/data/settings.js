export default function settings() {
    return {
        open: false,
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
                document.documentElement.requestFullscreen().catch(() => {
                });
                this..enableFocusMode();
            } else {
                if (document.exitFullscreen) document.exitFullscreen().catch(() => {
                });
                this..disableFocusMode();
            }
        },

        resetApp() {
            if (confirm('آیا مطمئن هستید؟ تمام تنظیمات ظاهری به حالت پیش‌فرض باز می‌گردد.')) {
                localStorage.clear();
                location.reload();
            }
        }
    }
}
