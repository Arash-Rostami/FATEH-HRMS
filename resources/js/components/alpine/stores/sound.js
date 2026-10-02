const KEYS = {
    channel: 'chat-muted-channels',
    contact: 'chat-muted-contacts',
    project: 'chat-muted-projects'
};

const OUTGOING_SOUND_SELECTOR = '[data-outgoing-sound]';
const ATTR_OUTGOING_SOUND = 'data-outgoing-sound';
const OUTGOING_VOLUME = 0.35;

export default function soundStore(Alpine) {
    Alpine.store('sound', {
        mutedChannels: [],
        mutedContacts: [],
        mutedProjects: [],
        globalHush: false,
        _audio: null,

        init() {
            this.mutedChannels = this._load(KEYS.channel);
            this.mutedContacts = this._load(KEYS.contact);
            this.mutedProjects = this._load(KEYS.project);

            window.addEventListener('storage', (e) => {
                if (e.key === KEYS.channel) this.mutedChannels = this._load(KEYS.channel);
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
            const num = Number(id);
            const len = list.length;

            for (let i = 0; i < len; i++) {
                if (list[i] === num) return true;
            }
            return false;
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

        isAllMuted(ids, scope = 'channel') {
            if (!Array.isArray(ids)) return false;

            const len = ids.length;
            if (len === 0) return false;

            const listSet = new Set(this._set(scope));

            for (let i = 0; i < len; i++) {
                if (!listSet.has(Number(ids[i]))) return false;
            }
            return true;
        },

        toggleAll(ids, scope = 'channel') {
            if (!Array.isArray(ids)) return;

            const len = ids.length;
            if (len === 0) return;

            const list = this._set(scope);
            const listSet = new Set(list);
            const numIds = new Array(len);
            let allMuted = true;

            for (let i = 0; i < len; i++) {
                const num = Number(ids[i]);
                numIds[i] = num;
                if (!listSet.has(num)) {
                    allMuted = false;
                }
            }

            let next;
            if (allMuted) {
                const idSet = new Set(numIds);
                next = list.filter((v) => !idSet.has(v));
            } else {
                next = list.slice();
                for (let i = 0; i < len; i++) {
                    const num = numIds[i];
                    if (!listSet.has(num)) {
                        next.push(num);
                    }
                }
            }

            this._setList(scope, next);
            this._persist(this._key(scope), next);
        },

        playOutgoing(id = null, scope = 'channel') {
            if (this.globalHush) return;
            if (id !== null && this.isMuted(id, scope)) return;
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

        clearAll(scope = 'channel') {
            this._persist(this._key(scope), []);
            this._setList(scope, []);
        }
    });
}
