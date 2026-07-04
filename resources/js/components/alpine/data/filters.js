export default (open, searchModel, activeConditionFn, clearActionFn) => ({
    showFilters: open,
    compact: false,
    _lastY: 0,

    init() {
        this._lastY = window.scrollY;
        window.addEventListener('scroll', () => {
            requestAnimationFrame(() => {
                const y = window.scrollY, d = y - this._lastY;
                if (y < 30) this.compact = false;
                else if (d > 10) { this.compact = true; this.showFilters = false; }
                else if (d < -10) this.compact = false;
                this._lastY = y;
            });
        }, { passive: true });
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
