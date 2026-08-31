const KEYS = {contact: 'pinned-contacts', channel: 'pinned-channels', message: 'pinned-messages', project: 'pinned-projects', activity: 'pinned-activity'};

export default (Alpine) => {
    Alpine.store('pinned', {
        sets: {contact: new Set(), channel: new Set(), message: new Set(), project: new Set(), activity: new Set()},

        init() {
            for (const scope of Object.keys(KEYS)) {
                this.sets[scope] = this._load(KEYS[scope]);
            }
        },

        _load(key) {
            try {
                const saved = JSON.parse(localStorage.getItem(key));
                return new Set(Array.isArray(saved) ? saved.map(Number).filter(Number.isFinite) : []);
            } catch {
                return new Set();
            }
        },

        _persist(scope) {
            try {
                localStorage.setItem(KEYS[scope], JSON.stringify([...this.sets[scope]]));
            } catch {}
        },

        isPinned(id, scope = 'contact') {
            return this.sets[scope]?.has(Number(id)) ?? false;
        },

        togglePin(id, scope = 'contact') {
            const s = this.sets[scope];
            if (s) {
                id = Number(id);
                s.has(id) ? s.delete(id) : s.add(id);
                this._persist(scope);
            }
        },

        getPinned(scope = 'contact') {
            return this.sets[scope] ? [...this.sets[scope]] : [];
        }
    });
};
