const KEYS = {contact: 'pinned-contacts', channel: 'pinned-channels', message: 'pinned-messages', project: 'pinned-projects', activity: 'pinned-activity', menu: 'pinned-menu-items'};
const STRING_SCOPES = new Set(['menu']);

const coerceId = (id, scope) => STRING_SCOPES.has(scope) ? String(id) : Number(id);

export default (Alpine) => {
    Alpine.store('pinned', {
        sets: {contact: new Set(), channel: new Set(), message: new Set(), project: new Set(), activity: new Set(), menu: new Set()},

        init() {
            for (const scope of Object.keys(KEYS)) {
                this.sets[scope] = this._load(scope);
            }
        },

        _load(scope) {
            try {
                const saved = JSON.parse(localStorage.getItem(KEYS[scope]));
                if (!Array.isArray(saved)) return new Set();
                const ids = saved.map((v) => coerceId(v, scope));
                return new Set(STRING_SCOPES.has(scope) ? ids.filter(Boolean) : ids.filter(Number.isFinite));
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
            return this.sets[scope]?.has(coerceId(id, scope)) ?? false;
        },

        togglePin(id, scope = 'contact') {
            const s = this.sets[scope];
            if (s) {
                const key = coerceId(id, scope);
                s.has(key) ? s.delete(key) : s.add(key);
                this._persist(scope);
            }
        },

        getPinned(scope = 'contact') {
            return this.sets[scope] ? [...this.sets[scope]] : [];
        }
    });
};
