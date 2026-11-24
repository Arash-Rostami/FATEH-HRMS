const PUSH_STORAGE_PREFIX = 'chat-push-';
const NOTIFICATION_ICON = '/favicon.ico';
const LIVE_KEY = 'chat-push-live';
const SYNCED_KEY = 'chat-push-synced';
const SUBSCRIPTION_URL = '/push/subscription';
const READY_TIMEOUT_MS = 10000;
const RESYNC_MS = 60 * 60 * 1000;
const DIRTY_KEY = 'chat-push-dirty';

const urlBase64ToUint8Array = (b64) => {
    const padded = (b64 + '='.repeat((4 - (b64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(padded);
    return Uint8Array.from(raw, (c) => c.charCodeAt(0));
};

const sameKey = (buffer, bytes) => buffer.byteLength === bytes.length
    && new Uint8Array(buffer).every((b, i) => b === bytes[i]);

export default (Alpine) => {
    Alpine.store('push', {
        supported: typeof Notification !== 'undefined',
        enabled: { channel: false, contact: false, project: false },
        live: false,
        pushSeen: { contact: 0, channel: 0 },

        init() {
            Object.keys(this.enabled).forEach((scope) => this._load(scope));

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.addEventListener('message', async (e) => {
                    const { type, endpoint, scope } = e.data ?? {};

                    if (type === 'push-confirmed') {
                        await this._confirmLive(endpoint);
                    } else if (type === 'chat-push') {
                        (scope ? [scope] : ['contact', 'channel']).forEach((s) => {
                            this.pushSeen[s] = (this.pushSeen[s] ?? 0) + 1;
                        });
                        await this._confirmLive(endpoint);
                        const wanted = scope ? this.isEnabled(scope) : this.isEnabled('contact') || this.isEnabled('channel');
                        if (document.hidden && wanted) this._badgeTitle();
                    }
                });
            }

            if (this._webPushReady() && (this.isEnabled('contact') || this.isEnabled('channel'))) {
                this._resume();
            }

            if ('serviceWorker' in navigator) {
                setInterval(() => {
                    if (!document.hidden && this._webPushReady() && (this.isEnabled('contact') || this.isEnabled('channel'))) this._resume();
                }, RESYNC_MS / 2);
            }

            document.addEventListener('visibilitychange', () => {
                this.pushSeen = { contact: 0, channel: 0 };
                if (document.hidden) return;
                this._resetTitle();
                if (this._webPushReady() && (this.isEnabled('contact') || this.isEnabled('channel'))) this._resume();
            });

            window.addEventListener('storage', (e) => {
                const scope = Object.keys(this.enabled).find((s) => this._key(s) === e.key);
                if (!scope) return;
                this._load(scope);
                if (scope !== 'project' && this._webPushReady()) this._sync();
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
                if (this._webPushReady()) this._sync(false).then(() => this._failClosed());
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
            if (this._webPushReady()) this._sync(true);
        },

        pollsHidden(scope = 'channel') {
            return this.isEnabled(scope);
        },

        _webPushReady() {
            const ready = this.supported
                && 'serviceWorker' in navigator
                && 'PushManager' in window
                && !!document.querySelector('meta[name="vapid-key"]')?.content
                && Notification.permission === 'granted';

            if (!ready) {
                this.live = false;
                if (!this.supported || Notification.permission !== 'granted') {
                    try {
                        localStorage.removeItem(LIVE_KEY);
                    } catch (e) {}
                }
            }

            return ready;
        },

        async _resume() {
            await this._restore();
            const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            try {
                const [seen, at] = (sessionStorage.getItem(SYNCED_KEY) ?? '').split('|');
                const dirty = localStorage.getItem(DIRTY_KEY) === '1';
                if (this.live && !dirty && seen === token && Date.now() - Number(at) < RESYNC_MS) return;
                sessionStorage.setItem(SYNCED_KEY, `${token}|${Date.now()}`);
            } catch (err) {}

            this._sync();
        },

        async _confirmLive(endpoint) {
            try {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                if (sub && (!endpoint || sub.endpoint === endpoint)) {
                    localStorage.setItem(LIVE_KEY, sub.endpoint);
                    this.live = true;
                }
            } catch (err) {}
        },

        async _restore() {
            try {
                const reg = await Promise.race([
                    navigator.serviceWorker.ready,
                    new Promise((_, reject) => setTimeout(reject, READY_TIMEOUT_MS))
                ]);
                const sub = await reg.pushManager.getSubscription();
                this.live = !!sub && localStorage.getItem(LIVE_KEY) === sub.endpoint;
            } catch (e) {}
        },

        async _failClosed() {
            try {
                if (localStorage.getItem(DIRTY_KEY) !== '1') return;
            } catch (e) {
                return;
            }
            try {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                await sub?.unsubscribe();
            } catch (e) {}
            this.live = false;
            try {
                localStorage.removeItem(LIVE_KEY);
            } catch (e) {}
        },

        async _post(sub, scopes, confirm) {
            return fetch(SUBSCRIPTION_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                },
                body: JSON.stringify({
                    _token: document.querySelector('meta[name="csrf-token"]')?.content,
                    endpoint: sub.endpoint,
                    keys: sub.toJSON().keys,
                    scopes,
                    confirm
                })
            });
        },

        _sync(confirm = false) {
            if (this._syncing) {
                this._next ??= { confirm: false, done: new Promise((resolve) => { this._resolveNext = resolve; }) };
                this._next.confirm ||= confirm;
                return this._next.done;
            }

            this._syncing = this._runSync(confirm).finally(() => {
                this._syncing = null;
                const next = this._next;
                const resolve = this._resolveNext;
                this._next = null;
                this._resolveNext = null;
                if (next) this._sync(next.confirm).then(resolve);
            });

            return this._syncing;
        },

        async _runSync(confirm) {
            try {
                const scopes = { contact: this.isEnabled('contact'), channel: this.isEnabled('channel') };
                const reg = await Promise.race([
                    navigator.serviceWorker.ready,
                    new Promise((_, reject) => setTimeout(reject, READY_TIMEOUT_MS))
                ]);

                if (!scopes.contact && !scopes.channel) {
                    const existing = await reg.pushManager.getSubscription();
                    if (existing) {
                        await existing.unsubscribe();
                        try {
                            await this._post(existing, scopes, false);
                        } catch (e) {}
                    }
                    this.live = false;
                    localStorage.removeItem(LIVE_KEY);
                    return;
                }

                const wanted = urlBase64ToUint8Array(document.querySelector('meta[name="vapid-key"]')?.content);
                let sub = await reg.pushManager.getSubscription();
                const have = sub?.options?.applicationServerKey;

                if (sub && have && !sameKey(have, wanted)) {
                    await sub.unsubscribe();
                    sub = null;
                }

                sub ??= await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: wanted });
                const confirmedBefore = localStorage.getItem(LIVE_KEY) === sub.endpoint;
                const res = await this._post(sub, scopes, confirm || !confirmedBefore);

                if (res.ok) localStorage.removeItem(DIRTY_KEY);
                else localStorage.setItem(DIRTY_KEY, '1');

                if (res.ok && (scopes.contact !== this.isEnabled('contact') || scopes.channel !== this.isEnabled('channel'))) {
                    this._sync(false);
                }

                if (res.ok) this.live = confirmedBefore || localStorage.getItem(LIVE_KEY) === sub.endpoint;
                else if (res.status === 401 || res.status === 419) this.live = false;
            } catch (e) {
                this.live = false;
                try {
                    localStorage.setItem(DIRTY_KEY, '1');
                } catch (err) {}
            }
        },

        _badgeTitle() {
            this._baseTitle ??= document.title;
            this._count = (this._count || 0) + 1;
            document.title = `(${String(this._count).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d])}) پیام جدید`;
        },

        _resetTitle() {
            if (this._baseTitle === undefined) return;
            document.title = this._baseTitle;
            this._baseTitle = undefined;
            this._count = 0;
        },

        notify(title, body, scope = 'channel') {
            if (!document.hidden || !this.isEnabled(scope)) return;
            if (this.live && this.pushSeen[scope] > 0) {
                this.pushSeen[scope] = 0;
                return;
            }
            this._badgeTitle();
            try {
                new Notification(title, { body, icon: NOTIFICATION_ICON });
            } catch (e) {}
        }
    });
};
