const PUSH_STORAGE_PREFIX = 'chat-push-';
const NOTIFICATION_ICON = '/favicon.ico';

export default (Alpine) => {
    Alpine.store('push', {
        supported: typeof Notification !== 'undefined',
        enabled: { channel: false, contact: false, project: false },

        init() {
            Object.keys(this.enabled).forEach((scope) => this._load(scope));

            window.addEventListener('storage', (e) => {
                const scope = Object.keys(this.enabled).find((s) => this._key(s) === e.key);
                if (scope) this._load(scope);
            });
        },

        _key(scope) {
            return PUSH_STORAGE_PREFIX + scope;
        },

        _load(scope) {
            try {
                this.enabled[scope] = localStorage.getItem(this._key(scope)) === '1';
            } catch (e) {}
        },

        isEnabled(scope = 'channel') {
            if (!this.supported || Notification.permission !== 'granted') return false;
            return !!this.enabled[scope];
        },

        async toggle(scope = 'channel') {
            if (!this.supported) return;

            if (this.isEnabled(scope)) {
                try {
                    localStorage.setItem(this._key(scope), '0');
                } catch (e) {}
                this.enabled[scope] = false;
                return;
            }

            const permission = Notification.permission === 'granted'
                ? 'granted'
                : await Notification.requestPermission();

            if (permission !== 'granted') return;

            try {
                localStorage.setItem(this._key(scope), '1');
            } catch (e) {}
            this.enabled[scope] = true;
        },

        notify(title, body, scope = 'channel') {
            if (!document.hidden || !this.isEnabled(scope)) return;
            try {
                new Notification(title, { body, icon: NOTIFICATION_ICON });
            } catch (e) {}
        }
    });
};
