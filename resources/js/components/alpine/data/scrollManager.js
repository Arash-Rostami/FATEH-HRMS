const TOP_THRESHOLD = 60;
const HIDE_DELTA = 4;
const SHOW_DELTA = -6;

export default function scrollManager() {
    return {
        _isVisible: true,
        _lastY: 0,
        _ticking: false,
        _scrollHandler: null,
        _rafId: null,

        get isVisible() {
            return !this.$store?.chrome?.forceHidden && this._isVisible;
        },

        init() {
            this._lastY = window.scrollY;

            this._scrollHandler = () => {
                if (this._ticking) return;
                this._ticking = true;
                this._rafId = window.requestAnimationFrame(() => this.update());
            };

            window.addEventListener('scroll', this._scrollHandler, { passive: true });
        },

        update() {
            const y = window.scrollY;
            const delta = y - this._lastY;

            if (y < TOP_THRESHOLD)       this._isVisible = true;
            else if (delta > HIDE_DELTA) this._isVisible = false;
            else if (delta < SHOW_DELTA) this._isVisible = true;

            this._lastY = y;
            this._ticking = false;
        },

        destroy() {
            if (this._rafId) {
                window.cancelAnimationFrame(this._rafId);
                this._rafId = null;
            }

            if (this._scrollHandler) {
                window.removeEventListener('scroll', this._scrollHandler);
                this._scrollHandler = null;
            }
        }
    };
}
