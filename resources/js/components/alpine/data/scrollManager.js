export default function scrollManager() {
    return {
        _isVisible: true,
        lastY: 0,
        ticking: false,
        scrollHandler: null,

        get isVisible() {
            return !this.$store.chrome.forceHidden && this._isVisible;
        },

        init() {
            this.scrollHandler = () => {
                if (!this.ticking) {
                    requestAnimationFrame(() => this.update());
                    this.ticking = true;
                }
            };
            window.addEventListener('scroll', this.scrollHandler, { passive: true });
        },

        update() {
            const y = window.scrollY;
            const delta = y - this.lastY;

            if (y < 60)             this._isVisible = true;
            else if (delta > 4)     this._isVisible = false;
            else if (delta < -6)    this._isVisible = true;

            this.lastY = y;
            this.ticking = false;
        },

        destroy() {
            window.removeEventListener('scroll', this.scrollHandler);
        }
    };
}
