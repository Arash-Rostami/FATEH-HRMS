const CHANNELS_KEY = 'chat-muted-channels';
const CONTACTS_KEY = 'chat-muted-contacts';
const OUTGOING_SOUND_SELECTOR = '[data-outgoing-sound]';
const OUTGOING_VOLUME = 0.35;

export default (Alpine) => {
    Alpine.store('sound', {
        mutedChannels: [],
        mutedContacts: [],
        _audio: null,

        init() {
            this.mutedChannels = this._load(CHANNELS_KEY);
            this.mutedContacts = this._load(CONTACTS_KEY);
        },

        _load(key) {
            try {
                const saved = localStorage.getItem(key);
                return saved ? JSON.parse(saved).map(Number).filter(Number.isFinite) : [];
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
            return scope === 'contact' ? this.mutedContacts : this.mutedChannels;
        },

        _key(scope) {
            return scope === 'contact' ? CONTACTS_KEY : CHANNELS_KEY;
        },

        isMuted(id, scope = 'channel') {
            return this._set(scope).includes(Number(id));
        },

        toggleMute(id, scope = 'channel') {
            const list = this._set(scope);
            id = Number(id);
            const i = list.indexOf(id);

            if (i > -1) {
                list.splice(i, 1);
            } else {
                list.push(id);
            }

            this._persist(this._key(scope), list);
        },

        isAllMuted(ids, scope = 'channel') {
            if (!Array.isArray(ids) || ids.length === 0) return false;
            const listSet = new Set(this._set(scope));
            return ids.every(id => listSet.has(Number(id)));
        },

        toggleAll(ids, scope = 'channel') {
            if (!Array.isArray(ids) || ids.length === 0) return;

            const list = this._set(scope);
            const numIds = ids.map(Number);
            const listSet = new Set(list);

            const allMuted = numIds.every(id => listSet.has(id));

            if (allMuted) {
                const idSet = new Set(numIds);
                for (let i = list.length - 1; i >= 0; i--) {
                    if (idSet.has(Number(list[i]))) {
                        list.splice(i, 1);
                    }
                }
            } else {
                for (let i = 0, len = numIds.length; i < len; i++) {
                    if (!listSet.has(numIds[i])) {
                        list.push(numIds[i]);
                    }
                }
            }

            this._persist(this._key(scope), list);
        },

        playOutgoing(id = null, scope = 'channel') {
            if (id !== null && this.isMuted(id, scope)) return;
            if (document.hidden) return;

            if (!this._audio) {
                const el = document.querySelector(OUTGOING_SOUND_SELECTOR);
                if (!el || !el.dataset.outgoingSound) return;

                const a = new Audio(el.dataset.outgoingSound);
                a.volume = OUTGOING_VOLUME;
                this._audio = a;
            }

            this._audio.currentTime = 0;
            this._audio.play().catch(() => {});
        }
    });
};
