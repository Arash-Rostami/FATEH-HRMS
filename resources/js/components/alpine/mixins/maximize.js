const LAYOUT_ELEMENT_IDS = ['footer', 'header', 'navbar'];
const LAYOUT_HIDDEN_CLASS = 'layout-hidden';
const MAX_WIDGET_CLOSE_MS = 350;

const ESC_LISTENER = /^x-on:keydown\.(?:escape(?:\.window)?|window\.escape)$/;
const ESC_CLEARS = /^\s*(?:[\w$.]+\s*=\s*(?:false|null)\s*;?\s*)+/;

export function onTop(el) {
    const top = document.elementFromPoint?.(window.innerWidth / 2, window.innerHeight / 2);
    return !top || el.contains(top);
}

export function consumeEscape(attribute) {
    const cleared = ESC_LISTENER.test(attribute.name) && ESC_CLEARS.exec(attribute.value);
    if (!cleared) return attribute;
    const open = cleared[0].match(/[\w$.]+(?=\s*=)/g).map((name) => `(${name} ?? false) !== false`).join(' || ');
    return { name: attribute.name, value: `(${open}) && $event.preventDefault(); ${attribute.value}` };
}

if (typeof document !== 'undefined') {
    document.addEventListener('livewire:navigating', () => {
        document.documentElement.classList.remove('view-maximized');
    });
}

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
        maxOn: false,
        _maxStorageKey: maxStorageKey(scope),

        initMaximize() {
            document.documentElement.classList.remove('max-restore', 'view-maximized');

            if (!this._maxStorageKey) return;

            let stored;
            try {
                stored = localStorage.getItem(this._maxStorageKey);
            } catch {
                return;
            }

            if (stored === null) return;

            const restore = () => {
                if (typeof this.restoreMaximize === 'function') {
                    this.restoreMaximize(stored);
                    return;
                }

                this.max = true;
                this.applyMaximize(true);
            };

            if (typeof this.$nextTick === 'function') this.$nextTick(restore);
            else restore();
        },

        onEscape(event) {
            const covered = (this.maxOn && !onTop(this.$root)) || !!document.querySelector('.fancybox__container');
            setTimeout(() => {
                if (covered || event.defaultPrevented) return;
                if (typeof this.closeOverlays === 'function' && this.closeOverlays()) return;
                if (this.maxOn) this.toggleMaximize(null);
            });
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
            this.maxOn = active;
            for (let i = 0, len = LAYOUT_ELEMENT_IDS.length; i < len; i++) {
                document.getElementById(LAYOUT_ELEMENT_IDS[i])?.classList.toggle(LAYOUT_HIDDEN_CLASS, active);
            }
            document.getElementById('content-shell')?.classList.toggle('maximized', active);
            document.documentElement.classList.toggle('view-maximized', active);

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
