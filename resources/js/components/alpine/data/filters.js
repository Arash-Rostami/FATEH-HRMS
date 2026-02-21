export default (open, searchModel, activeConditionFn, clearActionFn) => ({
    showFilters: open,

    get hasActiveFilters() {
        return activeConditionFn.call(this) || this.hasSearchQuery;
    },

    get hasSearchQuery() {
        return (this.$wire.get(searchModel) || '').length > 0;
    },

    clearFilters() {
        clearActionFn.call(this);
    },

    clearSearchOnly() {
        this.$wire.set(searchModel, '');
    }
});
