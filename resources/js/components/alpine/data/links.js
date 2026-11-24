import recentListMixin from "../mixins/recentList.js";

const FA_DIGITS = {'۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9'};

export default function links() {
    return {
        ...recentListMixin({key: 'fateh_links_recent', max: 6}),
        view: 'rail',

        init() {
            this.loadRecent();

            this.view = this.$wire.get('view') || 'rail';
        },

        handleHotkey(event) {
            if (!event.altKey) return;
            const tag = event.target?.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || event.target?.isContentEditable) return;
            const n = parseInt(FA_DIGITS[event.key] ?? event.key, 10);
            if (isNaN(n) || n < 1 || n > 9) return;
            this.$root.querySelector('[data-hotkey="' + n + '"]')?.click();
        },

        recordClick(item) {
            if (!item || !item.id) return;
            this.pushRecent({id: item.id, title: item.title || '', url: item.url || '', icon: item.icon || '', internal: !!item.internal});
        },
    }
}