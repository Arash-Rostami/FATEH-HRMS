export default function status() {
    return {
        activeFilter: 'onsite',
        searchQuery: '',

        init() {
            this.activeFilter = this.$wire.activeFilter;
            this.searchQuery = this.$wire.search;

            this.$watch('searchQuery', (value) => {
                this.$wire.set('search', value);
            });
        },

        setFilter(filter) {
            this.activeFilter = filter;
            this.$wire.set('activeFilter', filter);
        }
    }
}
