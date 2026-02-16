export default () => ({
    open: false,
    focusMode: false,
    backgroundEnabled: localStorage.getItem('backgroundEnabled') === 'true',

    init() {
        this.$watch('backgroundEnabled', value => {
            localStorage.setItem('backgroundEnabled', value);
            window.dispatchEvent(new CustomEvent('background-toggled', { detail: value }));
        });
    },

    toggleBackground() {
        this.backgroundEnabled = !this.backgroundEnabled;
    },

    toggleFocus() {
        this.focusMode = !this.focusMode;
        if (this.focusMode) {
            document.documentElement.requestFullscreen().catch(() => {});
            this.$wire.enableFocusMode();
        } else {
            if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
            this.$wire.disableFocusMode();
        }
    },

    resetApp() {
        if (confirm('آیا مطمئن هستید؟ تمام تنظیمات ظاهری به حالت پیش‌فرض باز می‌گردد.')) {
            localStorage.clear();
            location.reload();
        }
    }
});
