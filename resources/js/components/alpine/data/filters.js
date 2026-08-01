export default (open, searchModel, activeConditionFn, clearActionFn) => ({
    showFilters: open,
    isDocked: true,
    compact: false,
    _lastY: 0,
    _ticking: false,
    scrollHandler: null,

    init() {
        this._lastY = window.scrollY;
        this.scrollHandler = () => {
            if (this._ticking) return;
            this._ticking = true;
            requestAnimationFrame(() => {
                const y = window.scrollY, d = y - this._lastY;
                if (y < 60) this.compact = false;
                else if (d > 4) { this.compact = true; this.showFilters = false; }
                else if (d < -6) this.compact = false;
                this._lastY = y;
                this._ticking = false;
            });
        };
        window.addEventListener('scroll', this.scrollHandler, { passive: true });

        this.$watch('isDocked', (docked) => {
            this.$store.chrome.forceHidden = !docked;
        });
    },

    destroy() {
        window.removeEventListener('scroll', this.scrollHandler);
        this.$store.chrome.forceHidden = false;
    },

    get hasActiveFilters() {
        return activeConditionFn.call(this) || this.hasSearchQuery;
    },

    get hasSearchQuery() {
        return (this.$wire.get(searchModel) || '').length > 0;
    },

    clearFilters() { clearActionFn.call(this); },
    clearSearchOnly() { this.$wire.set(searchModel, ''); },
});
