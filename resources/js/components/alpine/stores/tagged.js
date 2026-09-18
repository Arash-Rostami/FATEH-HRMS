const SCOPES = ['contact', 'channel', 'project', 'task', 'ths', 'reservation'];

const KEYS = {
    contact: 'tagged-contacts',
    channel: 'tagged-channels',
    project: 'tagged-projects',
    task: 'tagged-tasks',
    ths: 'tagged-ths',
    reservation: 'tagged-reservations'
};

const INK = [
    'var(--tool-amethyst-color)',
    'var(--tool-sapphire-color)',
    'var(--tool-sage-color)',
    'var(--tool-gold-color)',
];

const WASH = [
    'var(--tool-amethyst-bg)',
    'var(--tool-sapphire-bg)',
    'var(--tool-sage-bg)',
    'var(--tool-gold-bg)',
];

const INK_LEN = 4;

export default function tagged(Alpine) {
    Alpine.store('tagged', {
        maps: { contact: null, channel: null, project: null, task: null, ths: null, reservation: null },
        palette: INK,

        init() {
            for (let i = 0; i < 6; i++) {
                const scope = SCOPES[i];
                this.maps[scope] = this._load(KEYS[scope]);
            }
        },

        _load(key) {
            try {
                const saved = JSON.parse(localStorage.getItem(key));
                if (!saved || typeof saved !== 'object' || Array.isArray(saved)) return {};

                const out = {};
                const keys = Object.keys(saved);
                const len = keys.length;

                for (let i = 0; i < len; i++) {
                    const k = keys[i];
                    const idx = Number(saved[k]);

                    if (Number.isInteger(idx) && idx >= 0 && idx < INK_LEN) {
                        out[Number(k)] = idx;
                    }
                }
                return out;
            } catch {
                return {};
            }
        },

        _persist(scope) {
            const data = Alpine.raw(this.maps[scope]);
            if (!data) return;

            queueMicrotask(() => {
                try {
                    localStorage.setItem(KEYS[scope], JSON.stringify(data));
                } catch {}
            });
        },

        getTag(id, scope = 'contact') {
            const m = this.maps[scope];
            if (!m) return null;
            const t = m[Number(id)];
            return t !== undefined ? t : null;
        },

        isTagged(id, scope = 'contact') {
            const m = this.maps[scope];
            return m ? m[Number(id)] !== undefined : false;
        },

        setTag(id, color, scope = 'contact') {
            const raw = Alpine.raw(this.maps[scope]);
            if (!raw) return;

            const idx = Number(color);
            if (Number.isInteger(idx) && idx >= 0 && idx < INK_LEN) {
                const next = Object.assign({}, raw);
                next[Number(id)] = idx;

                this.maps[scope] = next;
                this._persist(scope);
            }
        },

        clearTag(id, scope = 'contact') {
            const raw = Alpine.raw(this.maps[scope]);
            if (!raw) return;

            const numId = Number(id);
            if (raw[numId] !== undefined) {
                const next = Object.assign({}, raw);
                delete next[numId];

                this.maps[scope] = next;
                this._persist(scope);
            }
        },

        solid(i) {
            return INK[i] ?? 'transparent';
        },

        tagBg(id, scope = 'contact') {
            const m = this.maps[scope];
            if (!m) return null;
            const t = m[Number(id)];
            return t !== undefined ? WASH[t] : null;
        }
    });
}
