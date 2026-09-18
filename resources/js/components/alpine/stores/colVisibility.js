const STYLE_ID = 'dms-col-visibility-style';
const DOMAINS = ['dms', 'ths', 'reservation'];
const KEYS = { dms: 'dms-col-hidden', ths: 'ths-col-hidden', reservation: 'reservation-col-hidden' };
const SCOPES = { dms: '.dms-doc-table', ths: '.ths-ticket-table', reservation: '.reservation-command-table' };

let styleEl = null;

export default function colVisibility(Alpine) {
    Alpine.store('colVisibility', {
        maps: { dms: [], ths: [], reservation: [] },
        _renderRaf: null,

        init() {
            for (let i = 0; i < 3; i++) {
                const d = DOMAINS[i];
                try {
                    const saved = JSON.parse(localStorage.getItem(KEYS[d]));
                    if (Array.isArray(saved)) {
                        this.maps[d] = saved;
                    }
                } catch {}
            }
            this.render();
        },

        get hidden() {
            return this.maps.dms;
        },

        get thsHidden() {
            return this.maps.ths;
        },

        get reservationHidden() {
            return this.maps.reservation;
        },

        isHidden(key, scope = 'dms') {
            const arr = this.maps[scope];
            return arr && arr.indexOf(key) !== -1;
        },

        toggle(key, scope = 'dms') {
            const raw = Alpine.raw(this.maps[scope]);
            if (!raw) return;

            const idx = raw.indexOf(key);
            const next = raw.slice();

            if (idx !== -1) {
                next.splice(idx, 1);
            } else {
                next.push(key);
            }

            this.maps[scope] = next;

            this.persist(scope);
            this.render();
        },

        reset(scope = 'dms') {
            this.maps[scope] = [];
            this.persist(scope);
            this.render();
        },

        persist(scope) {
            const data = Alpine.raw(this.maps[scope]);
            queueMicrotask(() => {
                try {
                    localStorage.setItem(KEYS[scope], JSON.stringify(data));
                } catch {}
            });
        },

        render() {
            if (this._renderRaf) return;

            this._renderRaf = requestAnimationFrame(() => {
                this._renderRaf = null;

                if (!styleEl) {
                    styleEl = document.getElementById(STYLE_ID);
                    if (!styleEl) {
                        styleEl = document.createElement('style');
                        styleEl.id = STYLE_ID;
                        document.head.appendChild(styleEl);
                    }
                }

                let rules = '';

                for (let i = 0; i < 3; i++) {
                    const d = DOMAINS[i];
                    const arr = Alpine.raw(this.maps[d]);
                    const len = arr ? arr.length : 0;

                    if (len === 0) continue;

                    const prefix = SCOPES[d] + ' [data-col="';
                    for (let j = 0; j < len; j++) {
                        rules += prefix + arr[j] + '"]{display:none!important}';
                    }
                }

                if (styleEl.textContent !== rules) {
                    styleEl.textContent = rules;
                }
            });
        }
    });
}
