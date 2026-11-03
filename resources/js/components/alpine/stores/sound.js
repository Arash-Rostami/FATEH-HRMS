const KEYS = {
    channel: 'chat-muted-channels',
    contact: 'chat-muted-contacts',
    project: 'chat-muted-projects'
};

const OUTGOING_SOUND_SELECTOR = '[data-outgoing-sound]';
const ATTR_OUTGOING_SOUND = 'data-outgoing-sound';
const OUTGOING_VOLUME = 0.35;
const INCOMING_SOUND_SELECTOR = '[data-incoming-sound]';
const ATTR_INCOMING_SOUND = 'data-incoming-sound';
const INCOMING_VOLUME = 0.35;
const NOTIFICATION_KEY = 'notification-sound-muted';
const NOTIFICATION_SOUND_SELECTOR = '[data-notification-sound]';
const ATTR_NOTIFICATION_SOUND = 'data-notification-sound';
const NOTIFICATION_VOLUME = 0.35;
const NOTIFICATION_COOLDOWN_MS = 15000;
const ALL = -1;

export default function soundStore(Alpine) {
    Alpine.store('sound', {
        mutedChannels: [],
        mutedContacts: [],
        mutedProjects: [],
        globalHush: false,
        notificationMuted: false,
        _audio: null,
        _incomingAudio: null,
        _notificationAudio: null,
        _notificationAt: 0,

        init() {
            this.mutedChannels = this._load(KEYS.channel);
            this.mutedContacts = this._load(KEYS.contact);
            this.mutedProjects = this._load(KEYS.project);

            try {
                this.notificationMuted = localStorage.getItem(NOTIFICATION_KEY) === 'true';
            } catch (e) {}

            window.addEventListener('storage', (e) => {
                if (e.key === NOTIFICATION_KEY) this.notificationMuted = e.newValue === 'true';
                else if (e.key === KEYS.channel) this.mutedChannels = this._load(KEYS.channel);
                else if (e.key === KEYS.contact) this.mutedContacts = this._load(KEYS.contact);
                else if (e.key === KEYS.project) this.mutedProjects = this._load(KEYS.project);
            });
        },

        _load(key) {
            try {
                const raw = localStorage.getItem(key);
                if (raw === null) return [];

                const parsed = JSON.parse(raw);
                if (!Array.isArray(parsed)) return [];

                const len = parsed.length;
                const result = [];

                for (let i = 0; i < len; i++) {
                    const val = parsed[i];
                    if (val === null || val === undefined) continue;
                    const num = Number(val);
                    if (Number.isFinite(num)) {
                        result.push(num);
                    }
                }
                return result;
            } catch (e) {
                return [];
            }
        },

        _persist(key, list) {
            try {
                localStorage.setItem(key, JSON.stringify(list));
            } catch (e) {}
        },

        _set(scope) {
            const raw = scope === 'contact' ? this.mutedContacts
                : (scope === 'project' ? this.mutedProjects
                    : this.mutedChannels);
            return Alpine.raw(raw);
        },

        _setList(scope, list) {
            if (scope === 'contact') this.mutedContacts = list;
            else if (scope === 'project') this.mutedProjects = list;
            else this.mutedChannels = list;
        },

        _key(scope) {
            return KEYS[scope] || KEYS.channel;
        },

        isMuted(id, scope = 'channel') {
            const list = this._set(scope);
            const listed = list.includes(Number(id));
            return list.includes(ALL) ? !listed : listed;
        },

        toggleMute(id, scope = 'channel') {
            const list = this._set(scope);
            const num = Number(id);
            const i = list.indexOf(num);

            const next = i !== -1
                ? list.slice(0, i).concat(list.slice(i + 1))
                : list.concat(num);

            this._setList(scope, next);
            this._persist(this._key(scope), next);
        },

        isAllMuted(scope = 'channel') {
            return this._set(scope).includes(ALL);
        },

        toggleAll(scope = 'channel') {
            const next = this.isAllMuted(scope) ? [] : [ALL];
            this._setList(scope, next);
            this._persist(this._key(scope), next);
        },

        _silenced(id, scope) {
            if (id !== null) return this.isMuted(id, scope);
            const list = this._set(scope);
            return list.includes(ALL) && list.length === 1;
        },

        playOutgoing(id = null, scope = 'channel') {
            if (this.globalHush || this._silenced(id, scope)) return;
            if (document.hidden) return;

            if (this._audio === null) {
                const el = document.querySelector(OUTGOING_SOUND_SELECTOR);
                if (el === null) return;

                const src = el.getAttribute(ATTR_OUTGOING_SOUND);
                if (src === null) return;

                const a = new Audio(src);
                a.volume = OUTGOING_VOLUME;
                this._audio = a;
            }

            this._audio.currentTime = 0;
            this._audio.play().catch(() => {});
        },

        playIncoming(id = null, scope = 'channel') {
            if (this.globalHush || this._silenced(id, scope)) return;

            if (this._incomingAudio === null) {
                const el = document.querySelector(INCOMING_SOUND_SELECTOR);
                if (el === null) return;

                const src = el.getAttribute(ATTR_INCOMING_SOUND);
                if (src === null) return;

                const a = new Audio(src);
                a.volume = INCOMING_VOLUME;
                this._incomingAudio = a;
            }

            this._incomingAudio.currentTime = 0;
            this._incomingAudio.play().catch(() => {});
        },

        toggleNotification() {
            this.notificationMuted = !this.notificationMuted;
            this._persist(NOTIFICATION_KEY, this.notificationMuted);
        },

        playNotification() {
            if (this.globalHush || this.notificationMuted || document.hidden) return;
            if (document.querySelector(OUTGOING_SOUND_SELECTOR) !== null) return;

            const now = Date.now();
            if (now - this._notificationAt < NOTIFICATION_COOLDOWN_MS) return;
            this._notificationAt = now;

            if (this._notificationAudio === null) {
                const src = document.querySelector(NOTIFICATION_SOUND_SELECTOR)?.getAttribute(ATTR_NOTIFICATION_SOUND);
                if (!src) return;

                const a = new Audio(src);
                a.volume = NOTIFICATION_VOLUME;
                this._notificationAudio = a;
            }

            this._notificationAudio.currentTime = 0;
            this._notificationAudio.play().catch(() => {});
        },

        clearAll(scope = 'channel') {
            this._persist(this._key(scope), []);
            this._setList(scope, []);
        }
    });
}
