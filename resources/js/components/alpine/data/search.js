export default function search() {
    return {
        open: false,
        selectedIndex: 0,
        recentSearches: [],

        init() {
            this.recentSearches = JSON.parse(localStorage.getItem('cmd_history') || '[]');

            this.$wire.on('close-command-palette', () => {
                this.open = false;
            });

            this.$wire.on('add-recent-search', (data) => {
                this.addToHistory(data.item);
            });
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.$refs.searchInput.focus());
                this.selectedIndex = 0;
            }
        },

        addToHistory(item) {
            this.recentSearches = this.recentSearches.filter(i => i.action !== item.action);
            this.recentSearches.unshift(item);
            this.recentSearches = this.recentSearches.slice(0, 5);
            localStorage.setItem('cmd_history', JSON.stringify(this.recentSearches));
        },

        clearHistory() {
            this.recentSearches = [];
            localStorage.removeItem('cmd_history');
        },

        selectHistoryItem(item) {
            this.$wire.selectResult(item.action);
        }
    }
}
