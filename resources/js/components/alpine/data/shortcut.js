const USAGE_KEY = 'qa.usage';
const PINNED_KEY = 'qa.pinned';
const MAX_SHORTCUTS = 8;
const DEBOUNCE_MS = 800;
const MS_PER_DAY = 86400000;
const COUNT_WEIGHT = 2;
const RECENCY_BOOST = 5;

export default function shortcut(catalog = []) {
    return {
        catalog,
        usage: {},
        pinned: [],
        editing: false,
        _last: { key: null, at: 0 },
        _switchTabHandler: null,

        init() {
            this.usage = this._read(USAGE_KEY, {});
            this.pinned = this._read(PINNED_KEY, []);

            this._switchTabHandler = (e) => {
                const d = e?.detail;
                const tab = d?.tab ?? (Array.isArray(d) ? d[0]?.tab : null);
                if (tab) this._trackTab(tab);
            };

            window.addEventListener('switch-tab', this._switchTabHandler);
        },

        destroy() {
            if (this._switchTabHandler) {
                window.removeEventListener('switch-tab', this._switchTabHandler);
                this._switchTabHandler = null;
            }
        },

        _read(key, fallback) {
            try {
                return JSON.parse(localStorage.getItem(key)) ?? fallback;
            } catch (e) {
                return fallback;
            }
        },

        _save(key, val) {
            try {
                localStorage.setItem(key, JSON.stringify(val));
            } catch (e) {}
        },

        track(key) {
            if (!key) return;
            const now = Date.now();
            if (this._last.key === key && now - this._last.at < DEBOUNCE_MS) return;

            this._last = { key, at: now };

            const e = this.usage[key] ? { ...this.usage[key] } : { count: 0, last: 0 };
            e.count++;
            e.last = now;

            this.usage = { ...this.usage, [key]: e };
            this._save(USAGE_KEY, this.usage);
        },

        _trackTab(tab) {
            const item = this.catalog.find(i => i.type === 'tab' && i.target === tab);
            if (item) this.track(item.key);
        },

        score(key, now = Date.now()) {
            const u = this.usage[key];
            if (!u) return 0;
            const days = (now - u.last) / MS_PER_DAY;
            return (u.count * COUNT_WEIGHT) + Math.max(0, RECENCY_BOOST - days);
        },

        get manual() {
            return this.pinned.length > 0;
        },

        get shortcuts() {
            if (this.manual) {
                return this.pinned
                    .map(k => this.catalog.find(i => i.key === k))
                    .filter(Boolean)
                    .slice(0, MAX_SHORTCUTS);
            }

            const now = Date.now();

            return this.catalog
                .map(item => ({ item, score: this.score(item.key, now) }))
                .filter(mapped => mapped.score > 0)
                .sort((a, b) => b.score - a.score)
                .slice(0, MAX_SHORTCUTS)
                .map(mapped => mapped.item);
        },

        get showEmpty() {
            return !this.manual && this.shortcuts.length === 0;
        },

        isPinned(key) {
            return this.pinned.includes(key);
        },

        itemByKey(key) {
            return this.catalog.find(i => i.key === key);
        },

        open(item) {
            if (!item) return;

            if (item.type === 'tab') {
                this.$dispatch('switch-tab', { tab: item.target });
            } else {
                this.track(item.key);
                window.Livewire ? window.Livewire.navigate(item.url) : (window.location.href = item.url);
            }
        },

        toggleEdit() {
            this.editing = !this.editing;
        },

        pin(key) {
            if (!this.pinned.includes(key)) {
                this.pinned = [...this.pinned, key];
                this._save(PINNED_KEY, this.pinned);
            }
        },

        unpin(key) {
            const newPinned = this.pinned.filter(k => k !== key);
            if (newPinned.length !== this.pinned.length) {
                this.pinned = newPinned;
                this._save(PINNED_KEY, this.pinned);
            }
        },

        move(key, d) {
            const i = this.pinned.indexOf(key);
            const j = i + d;
            if (i < 0 || j < 0 || j >= this.pinned.length) return;

            const a = [...this.pinned];
            [a[i], a[j]] = [a[j], a[i]];
            this.pinned = a;
            this._save(PINNED_KEY, this.pinned);
        },

        resetToSmart() {
            if (this.pinned.length > 0) {
                this.pinned = [];
                this._save(PINNED_KEY, this.pinned);
            }
        },
    };
}
