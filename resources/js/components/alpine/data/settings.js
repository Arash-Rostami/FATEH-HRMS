export default function settings() {
    return {
        open: false,
        focusMode: false,

        get backgroundEnabled() {
            return Alpine.store('background').enabled;
        },

        toggleBackground() {
            Alpine.store('background').toggle(!this.backgroundEnabled);
        },

        toggleFocus() {
            this.focusMode = !this.focusMode;
            if (this.focusMode) {
                document.documentElement.requestFullscreen().catch(() => {
                });
                this.$wire.enableFocusMode();
            } else {
                if (document.exitFullscreen) document.exitFullscreen().catch(() => {
                });
                this.$wire.disableFocusMode();
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
