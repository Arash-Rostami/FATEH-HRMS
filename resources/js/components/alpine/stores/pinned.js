const SCOPES = ['contact', 'channel', 'message', 'project', 'activity', 'menu', 'reservation'];

const KEYS = {
    contact: 'pinned-contacts',
    channel: 'pinned-channels',
    message: 'pinned-messages',
    project: 'pinned-projects',
    activity: 'pinned-activity',
    menu: 'pinned-menu-items',
    reservation: 'pinned-reservations'
};

export default function pinned(Alpine) {
    Alpine.store('pinned', {
        sets: { contact: null, channel: null, message: null, project: null, activity: null, menu: null, reservation: null },
        lists: { contact: [], channel: [], message: [], project: [], activity: [], menu: [], reservation: [] },

        init() {
            for (let i = 0; i < 7; i++) {
                const scope = SCOPES[i];
                const set = this._load(scope);
                this.sets[scope] = set;
                this.lists[scope] = Array.from(set);
            }
        },

        _load(scope) {
            const set = new Set();
            try {
                const saved = JSON.parse(localStorage.getItem(KEYS[scope]));
                if (Array.isArray(saved)) {
                    const len = saved.length;
                    const isMenu = scope === 'menu';
                    for (let i = 0; i < len; i++) {
                        const val = saved[i];
                        if (isMenu) {
                            if (val) set.add(String(val));
                        } else {
                            const num = Number(val);
                            if (Number.isFinite(num)) set.add(num);
                        }
                    }
                }
            } catch {}
            return set;
        },

        _persist(scope) {
            const data = Alpine.raw(this.lists[scope]);
            if (!data) return;

            queueMicrotask(() => {
                try {
                    localStorage.setItem(KEYS[scope], JSON.stringify(data));
                } catch {}
            });
        },

        isPinned(id, scope = 'contact') {
            const s = this.sets[scope];
            if (!s) return false;
            return s.has(scope === 'menu' ? String(id) : Number(id));
        },

        togglePin(id, scope = 'contact') {
            const rawSet = Alpine.raw(this.sets[scope]);
            if (!rawSet) return;

            const key = scope === 'menu' ? String(id) : Number(id);
            const nextSet = new Set(rawSet);

            if (nextSet.has(key)) {
                nextSet.delete(key);
            } else {
                nextSet.add(key);
            }

            this.sets[scope] = nextSet;
            this.lists[scope] = Array.from(nextSet);

            this._persist(scope);
        },

        getPinned(scope = 'contact') {
            return this.lists[scope] || [];
        }
    });
}
