const RECENT_KEY = 'fateh_links_recent';
const RECENT_MAX = 6;

export default function links() {
    return {
        recent: [],
        view: 'rail',

        init() {
            try {
                this.recent = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
            } catch (e) {
                this.recent = [];
            }

            this.view = this.$wire.get('view') || 'rail';
        },

        handleHotkey(event) {
            const tag = event.target?.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || event.target?.isContentEditable) return;
            const n = parseInt(event.key, 10);
            if (isNaN(n) || n < 1 || n > 9) return;
            this.$root.querySelector('[data-hotkey="' + n + '"]')?.click();
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