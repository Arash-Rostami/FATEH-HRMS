export default function shortcut(catalog = []) {
    return {
        catalog,
        usageKey: 'qa.usage',
        pinnedKey: 'qa.pinned',
        usage: {},
        pinned: [],
        editing: false,
        max: 8,
        _last: {key: null, at: 0},

        init() {
            this.usage = this._read(this.usageKey, {});
            this.pinned = this._read(this.pinnedKey, []);

            window.addEventListener('switch-tab', (e) => {
                const d = e?.detail;
                const tab = d?.tab ?? (Array.isArray(d) ? d[0]?.tab : null);
                if (tab) this._trackTab(tab);
            });
        },

        _read(key, fallback) {
            try {
                return JSON.parse(localStorage.getItem(key)) ?? fallback;
            } catch (e) {
                return fallback;
            }
        },
        _save(key, val) {
            localStorage.setItem(key, JSON.stringify(val));
        },

        track(key) {
            if (!key) return;
            const now = Date.now();
            if (this._last.key === key && now - this._last.at < 800) return; // de-dupe
            this._last = {key, at: now};
            const e = this.usage[key] ?? {count: 0, last: 0};
            e.count++;
            e.last = now;
            this.usage = {...this.usage, [key]: e};
            this._save(this.usageKey, this.usage);
        },
        _trackTab(tab) {
            const item = this.catalog.find(i => i.type === 'tab' && i.target === tab);
            if (item) this.track(item.key);
        },
        score(key) {
            const u = this.usage[key];
            if (!u) return 0;
            const days = (Date.now() - u.last) / 86400000;
            return (u.count * 2) + Math.max(0, 5 - days); // frequency weighted, recency boost
        },

        get manual() {
            return this.pinned.length > 0;
        },
        get shortcuts() {
            if (this.manual) {
                return this.pinned.map(k => this.catalog.find(i => i.key === k)).filter(Boolean).slice(0, this.max);
            }
            return this.catalog
                .filter(i => this.score(i.key) > 0)
                .sort((a, b) => this.score(b.key) - this.score(a.key))
                .slice(0, this.max);
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
            if (item.type === 'tab') {
                this.$dispatch('switch-tab', {tab: item.target}); // tracked by the window listener
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
                this._save(this.pinnedKey, this.pinned);
            }
        },
        unpin(key) {
            this.pinned = this.pinned.filter(k => k !== key);
            this._save(this.pinnedKey, this.pinned);
        },
        move(key, d) {
            const i = this.pinned.indexOf(key), j = i + d;
            if (i < 0 || j < 0 || j >= this.pinned.length) return;
            const a = [...this.pinned];
            [a[i], a[j]] = [a[j], a[i]];
            this.pinned = a;
            this._save(this.pinnedKey, this.pinned);
        },
        resetToSmart() {
            this.pinned = [];
            this._save(this.pinnedKey, this.pinned);
        },
    };
}
