const KEYS = { contact: 'tagged-contacts', channel: 'tagged-channels' };

const INK = [
    'var(--md-sys-color-primary)',
    'var(--md-sys-color-tertiary)',
    'var(--md-sys-color-secondary)',
    'var(--md-sys-color-error)',
    'var(--md-sys-color-on-surface-variant)',
];

const WASH = [
    'var(--md-sys-color-primary-container)',
    'var(--md-sys-color-tertiary-container)',
    'var(--md-sys-color-secondary-container)',
    'var(--md-sys-color-error-container)',
    'var(--md-sys-color-surface-variant)',
];

const WASH_BG = WASH.map(c => `color-mix(in srgb, ${c} 55%, var(--md-sys-color-surface))`);

export default (Alpine) => {
    Alpine.store('tagged', {
        maps: { contact: {}, channel: {} },
        palette: INK,

        init() {
            for (const scope in KEYS) this.maps[scope] = this._load(KEYS[scope]);
        },

        _load(key) {
            try {
                const saved = JSON.parse(localStorage.getItem(key));
                if (!saved || typeof saved !== 'object' || Array.isArray(saved)) return {};
                const out = {};
                for (const k in saved) {
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
            return t === null ? null : WASH_BG[t] ?? null;
        },
    });
};
