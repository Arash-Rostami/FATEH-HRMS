const LAYOUT_ELEMENT_IDS = ['footer', 'header', 'navbar'];
const LAYOUT_HIDDEN_CLASS = 'layout-hidden';
const MAX_WIDGET_CLOSE_MS = 350;

function maxStorageKey(scope) {
    if (!scope) return null;
    if (window.self !== window.top) return null;
    const uid = document.documentElement.dataset.uid;
    if (!uid) return null;
    return `max:${scope}:${uid}`;
}

export default function maximizeMixin(scope) {
    return {
        max: false,
        maxLeaving: false,
        _maxStorageKey: maxStorageKey(scope),

        initMaximize() {
            document.documentElement.classList.remove('max-restore');

            if (!this._maxStorageKey) return;

            let stored;
            try {
                stored = localStorage.getItem(this._maxStorageKey);
            } catch {
                return;
            }

            if (stored === null) return;

            if (typeof this.restoreMaximize === 'function') {
                this.restoreMaximize(stored);
                return;
            }

            this.max = true;
            this.applyMaximize(true);
        },

        _persistMaximize(value) {
            if (!this._maxStorageKey) return;
            try {
                value
                    ? localStorage.setItem(this._maxStorageKey, value)
                    : localStorage.removeItem(this._maxStorageKey);
            } catch {}
        },

        applyMaximize(active) {
            for (let i = 0, len = LAYOUT_ELEMENT_IDS.length; i < len; i++) {
                document.getElementById(LAYOUT_ELEMENT_IDS[i])?.classList.toggle(LAYOUT_HIDDEN_CLASS, active);
            }
            document.getElementById('content-shell')?.classList.toggle('maximized', active);

            if (typeof this.$nextTick === 'function') {
                this.$nextTick(() => window.dispatchEvent(new Event('resize')));
            }
        },

        toggleMaximize() {
            if (this.max) {
                this.maxLeaving = true;
                this._persistMaximize(false);
                this.applyMaximize(false);
                setTimeout(() => {
                    this.max = false;
                    this.maxLeaving = false;
                }, MAX_WIDGET_CLOSE_MS);
                return;
            }

            this.max = true;
            this._persistMaximize(true);
            this.applyMaximize(true);
        }
    };
}
