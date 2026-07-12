export default (Alpine) => {
    Alpine.store('sound', {
        mutedChannels: [],
        mutedContacts: [],
        _audio: null,

        init() {
            this.mutedChannels = this._load('chat-muted-channels');
            this.mutedContacts = this._load('chat-muted-contacts');
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
            try { localStorage.setItem(key, JSON.stringify(list)); } catch (e) {}
        },

        _set(scope) {
            return scope === 'contact' ? this.mutedContacts : this.mutedChannels;
        },

        _key(scope) {
            return scope === 'contact' ? 'chat-muted-contacts' : 'chat-muted-channels';
        },

        isMuted(id, scope = 'channel') {
            return this._set(scope).includes(Number(id));
        },

        toggleMute(id, scope = 'channel') {
            const list = this._set(scope);
            id = Number(id);
            const i = list.indexOf(id);
            if (i > -1) list.splice(i, 1);
            else list.push(id);
            this._persist(this._key(scope), list);
        },

        isAllMuted(ids, scope = 'channel') {
            if (!Array.isArray(ids) || ids.length === 0) return false;
            const list = this._set(scope);
            return ids.every(id => list.includes(Number(id)));
        },

        toggleAll(ids, scope = 'channel') {
            if (!Array.isArray(ids) || ids.length === 0) return;
            const list = this._set(scope);
            const numIds = ids.map(Number);
            const allMuted = numIds.every(id => list.includes(id));
            if (allMuted) {
                for (let i = list.length - 1; i >= 0; i--) {
                    if (numIds.includes(Number(list[i]))) list.splice(i, 1);
                }
            } else {
                numIds.forEach(id => {
                    if (!list.includes(id)) list.push(id);
                });
            }
            this._persist(this._key(scope), list);
        },

        playOutgoing(id = null, scope = 'channel') {
            if (id !== null && this.isMuted(id, scope)) return;
            if (document.hidden) return;
            if (!this._audio) {
                const el = document.querySelector('[data-outgoing-sound]');
                if (!el || !el.dataset.outgoingSound) return;
                const a = new Audio(el.dataset.outgoingSound);
                a.volume = 0.35;
                this._audio = a;
            }
            this._audio.currentTime = 0;
            this._audio.play().catch(() => {});
        }
    });
};