const TOP_THRESHOLD = 60;
const HIDE_DELTA = 4;
const SHOW_DELTA = -6;

export default (open, searchModel, activeConditionFn, clearActionFn) => ({
    showFilters: open,
    isDocked: true,
    compact: false,
    _lastY: 0,
    _ticking: false,
    _scrollHandler: null,
    _rafId: null,

    init() {
        this._lastY = window.scrollY;

        this._scrollHandler = () => {
            if (this._ticking) return;
            this._ticking = true;
            this._rafId = window.requestAnimationFrame(() => {
                const y = window.scrollY;
                const d = y - this._lastY;

                if (y < TOP_THRESHOLD) {
                    this.compact = false;
                } else if (d > HIDE_DELTA) {
                    this.compact = true;
                    this.showFilters = false;
                } else if (d < SHOW_DELTA) {
                    this.compact = false;
                }

                this._lastY = y;
                this._ticking = false;
            });
        };

        window.addEventListener('scroll', this._scrollHandler, { passive: true });

        this.$watch('isDocked', (docked) => {
            if (this.$store?.chrome) {
                this.$store.chrome.forceHidden = !docked;
            }
        });
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

        if (this.$store?.chrome) {
            this.$store.chrome.forceHidden = false;
        }
    },

    get hasActiveFilters() {
        return (activeConditionFn?.call(this)) || this.hasSearchQuery;
    },

    get hasSearchQuery() {
        return (this.$wire.get(searchModel) || '').length > 0;
    },

    clearFilters() {
        clearActionFn?.call(this);
    },

    clearSearchOnly() {
        this.$wire.set(searchModel, '');
    },
});
