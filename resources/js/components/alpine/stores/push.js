export default (Alpine) => {
    Alpine.store('push', {
        supported: typeof Notification !== 'undefined',

        _key(scope) {
            return `chat-push-${scope}`;
        },

        isEnabled(scope = 'channel') {
            if (!this.supported || Notification.permission !== 'granted') return false;
            try { return localStorage.getItem(this._key(scope)) === '1'; } catch (e) { return false; }
        },

        async toggle(scope = 'channel') {
            if (!this.supported) return;
            if (this.isEnabled(scope)) {
                try { localStorage.setItem(this._key(scope), '0'); } catch (e) {}
                return;
            }
            const permission = Notification.permission === 'granted'
                ? 'granted'
                : await Notification.requestPermission();
            if (permission !== 'granted') return;
            try { localStorage.setItem(this._key(scope), '1'); } catch (e) {}
        },

        notify(title, body, scope = 'channel') {
            if (!document.hidden || !this.isEnabled(scope)) return;
            new Notification(title, { body, icon: '/favicon.ico' });
        }
    });
};
