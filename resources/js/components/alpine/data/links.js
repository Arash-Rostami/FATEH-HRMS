const RECENT_KEY = 'fateh_links_recent';
const RECENT_MAX = 6;

export default function links() {
    return {
        recent: [],

        init() {
            try {
                this.recent = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
            } catch (e) {
                this.recent = [];
            }
        },

        recordClick(item) {
            if (!item || !item.id) return;
            this.recent = this.recent.filter((r) => r.id !== item.id);
            this.recent.unshift({id: item.id, title: item.title || '', url: item.url || '', icon: item.icon || '', internal: !!item.internal});
            this.recent = this.recent.slice(0, RECENT_MAX);
            try {
                localStorage.setItem(RECENT_KEY, JSON.stringify(this.recent));
            } catch (e) {
            }
        },

        clearRecent() {
            this.recent = [];
            try {
                localStorage.removeItem(RECENT_KEY);
            } catch (e) {
            }
        },
    }
}