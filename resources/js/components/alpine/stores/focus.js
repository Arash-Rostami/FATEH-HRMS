const EVENT_EXPIRED = 'focus-mode-expired';

export default function focusStore(Alpine) {
    Alpine.store('focus', {
        active: false,
        until: null,
        _timerId: null,

        schedule(endsAt) {
            if (this._timerId !== null) {
                clearTimeout(this._timerId);
            }

            if (!endsAt) {
                this._timerId = null;
                return;
            }

            const now = Date.now();
            const delay = endsAt > now ? endsAt - now : 0;

            this._timerId = setTimeout(() => {
                this._timerId = null;
                window.dispatchEvent(new CustomEvent(EVENT_EXPIRED));
            }, delay);
        },

        clear() {
            if (this._timerId !== null) {
                clearTimeout(this._timerId);
                this._timerId = null;
            }
        }
    });
}
