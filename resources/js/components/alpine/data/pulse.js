const CHANNEL_NAME = 'pulse';
const LOCK_NAME = 'pulse-leader';
const INTERVAL_MS = 70000;

export default function pulse(fingerprint) {
    return {
        fp: fingerprint,
        lastAt: 0,
        pending: new Set(),
        _channel: null,
        _timer: null,
        _release: null,
        _leading: false,
        _requesting: false,
        _destroyed: false,

        init() {
            this.lastAt = Date.now();

            if (typeof BroadcastChannel !== 'undefined') {
                this._channel = new BroadcastChannel(CHANNEL_NAME);
                this._channel.onmessage = (e) => this._apply(e.data);
            }

            this._onVisibility = () => (document.hidden ? this._standDown() : this._standUp());
            document.addEventListener('visibilitychange', this._onVisibility);
            this._onVisibility();
        },

        destroy() {
            this._destroyed = true;
            this._standDown();
            document.removeEventListener('visibilitychange', this._onVisibility);
            this._channel?.close();
        },

        _standUp() {
            this._flush();

            if (this._destroyed || this._leading || this._requesting) return;

            if (!('locks' in navigator)) {
                this._lead();
                return;
            }

            this._requesting = true;
            navigator.locks.request(LOCK_NAME, () => new Promise((resolve) => {
                this._requesting = false;

                if (this._destroyed || document.hidden) {
                    resolve();
                    return;
                }

                this._release = resolve;
                this._lead();
            })).catch(() => {
                this._requesting = false;
            });
        },

        _standDown() {
            this._leading = false;
            clearTimeout(this._timer);
            this._timer = null;
            this._release?.();
            this._release = null;
        },

        _lead() {
            this._leading = true;
            this._schedule(Math.max(0, INTERVAL_MS - (Date.now() - this.lastAt)));
        },

        _schedule(baseMs) {
            clearTimeout(this._timer);
            this._timer = setTimeout(() => this._tick(), baseMs * (0.9 + Math.random() * 0.2));
        },

        async _tick() {
            if (!this._leading) return;

            try {
                const message = { ...(await this.$wire.pulse(this.fp)), at: Date.now() };
                this._channel?.postMessage(message);
                this._apply(message);
            } catch {
            }

            if (this._leading) this._schedule(INTERVAL_MS);
        },

        _apply({ events, fingerprint, at }) {
            this.fp = fingerprint;
            this.lastAt = at;
            events.forEach((event) => this.pending.add(event));
            this._flush();
        },

        _flush() {
            if (document.hidden) return;

            this.pending.forEach((event) => window.Livewire?.dispatch(event));
            this.pending.clear();
        },
    };
}
