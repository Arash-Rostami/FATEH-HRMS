const HISTORY_KEY = 'cmd_history';
const MAX_HISTORY = 5;

export default function search() {
    return {
        open: false,
        selectedIndex: 0,
        recentSearches: [],

        init() {
            try {
                this.recentSearches = JSON.parse(localStorage.getItem(HISTORY_KEY)) || [];
            } catch (e) {
                this.recentSearches = [];
            }

            this.$wire.on('close-command-palette', () => {
                this.open = false;
            });

            this.$wire.on('add-recent-search', (data) => {
                const item = data?.item ?? (Array.isArray(data) ? data[0]?.item : null);
                if (item) this.addToHistory(item);
            });
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.$refs.searchInput?.focus());
                this.selectedIndex = 0;
            }
        },

        addToHistory(item) {
            if (!item?.action) return;

            const updated = this.recentSearches.filter(i => i.action !== item.action);
            updated.unshift(item);
            this.recentSearches = updated.slice(0, MAX_HISTORY);

            try {
                localStorage.setItem(HISTORY_KEY, JSON.stringify(this.recentSearches));
            } catch (e) {}
        },

        clearHistory() {
            this.recentSearches = [];
            try {
                localStorage.removeItem(HISTORY_KEY);
            } catch (e) {}
        },

        selectHistoryItem(item) {
            if (item?.action) {
                this.$wire?.selectResult(item.action);
            }
        }
    };
}
