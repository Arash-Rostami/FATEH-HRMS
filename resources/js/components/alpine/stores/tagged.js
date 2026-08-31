const KEYS = { contact: 'tagged-contacts', channel: 'tagged-channels', project: 'tagged-projects', task: 'tagged-tasks' };

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

export default (Alpine) => {
    Alpine.store('tagged', {
        maps: { contact: {}, channel: {}, project: {}, task: {} },
        palette: INK,

        init() {
            for (const scope of Object.keys(KEYS)) this.maps[scope] = this._load(KEYS[scope]);
        },

        _load(key) {
            try {
                const saved = JSON.parse(localStorage.getItem(key));
                if (!saved || typeof saved !== 'object' || Array.isArray(saved)) return {};
                const out = {};
                for (const k of Object.keys(saved)) {
                    const idx = Number(saved[k]);
                    if (Number.isInteger(idx) && idx >= 0 && idx < INK.length) out[k] = idx;
                }
                return out;
            } catch {
                return {};
            }
        },

        _persist(scope) {
            try {
                localStorage.setItem(KEYS[scope], JSON.stringify(this.maps[scope]));
            } catch {}
        },

        getTag(id, scope = 'contact') {
            return this.maps[scope]?.[Number(id)] ?? null;
        },

        isTagged(id, scope = 'contact') {
            return this.getTag(id, scope) !== null;
        },

        setTag(id, color, scope = 'contact') {
            const m = this.maps[scope];
            const idx = Number(color);
            if (m && Number.isInteger(idx) && idx >= 0 && idx < INK.length) {
                m[Number(id)] = idx;
                this._persist(scope);
            }
        },

        clearTag(id, scope = 'contact') {
            const m = this.maps[scope];
            if (m) {
                delete m[Number(id)];
                this._persist(scope);
            }
        },

        solid(i) {
            return INK[i] ?? 'transparent';
        },

        tagBg(id, scope = 'contact') {
            const t = this.getTag(id, scope);
            return t === null ? null : WASH[t] ?? null;
        },
    });
};
