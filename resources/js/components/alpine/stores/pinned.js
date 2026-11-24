const SCOPES = ['contact', 'channel', 'message', 'channel-message', 'project', 'activity', 'menu', 'reservation', 'ai-report', 'correspondence', 'faq', 'post', 'report', 'gallery', 'media', 'media-pin'];

const KEYS = {
    contact: 'pinned-contacts',
    channel: 'pinned-channels',
    message: 'pinned-messages',
    'channel-message': 'pinned-channel-messages',
    project: 'pinned-projects',
    activity: 'pinned-activity',
    menu: 'pinned-menu-items',
    reservation: 'pinned-reservations',
    'ai-report': 'pinned-ai-reports',
    correspondence: 'pinned-correspondence',
    faq: 'saved_faqs',
    post: 'saved_posts',
    report: 'saved_reports',
    gallery: 'saved_gallery',
    media: 'saved_media',
    'media-pin': 'pinned-media'
};

const SEED_KEY = 'pinned-channel-messages-seeded';

export default function pinned(Alpine) {
    Alpine.store('pinned', {
        sets: { contact: null, channel: null, message: null, 'channel-message': null, project: null, activity: null, menu: null, reservation: null, 'ai-report': null, correspondence: null, faq: null, post: null, report: null, gallery: null, media: null, 'media-pin': null },
        lists: { contact: [], channel: [], message: [], 'channel-message': [], project: [], activity: [], menu: [], reservation: [], 'ai-report': [], correspondence: [], faq: [], post: [], report: [], gallery: [], media: [], 'media-pin': [] },

        init() {
            for (let i = 0; i < SCOPES.length; i++) {
                const scope = SCOPES[i];
                const set = this._load(scope);
                this.sets[scope] = set;
                this.lists[scope] = Array.from(set);
            }
            this._seedChannelMessages();
        },

        _seedChannelMessages() {
            try {
                if (localStorage.getItem(SEED_KEY) !== null) return;
                const merged = new Set([...this.sets['channel-message'], ...this._load('message')]);
                this.sets['channel-message'] = merged;
                this.lists['channel-message'] = Array.from(merged);
                this._persist('channel-message');
                localStorage.setItem(SEED_KEY, '1');
            } catch {}
        },

        _load(scope) {
            const set = new Set();
            try {
                const saved = JSON.parse(localStorage.getItem(KEYS[scope]));
                if (Array.isArray(saved)) {
                    const len = saved.length;
                    const isMenu = scope === 'menu';
                    for (let i = 0; i < len; i++) {
                        const val = saved[i];
                        if (isMenu) {
                            if (val) set.add(String(val));
                        } else {
                            if (val === null || val === undefined) continue;
                            const num = Number(val);
                            if (Number.isFinite(num)) set.add(num);
                        }
                    }
                }
            } catch {}
            return set;
        },

        _persist(scope) {
            const data = Alpine.raw(this.lists[scope]);
            if (!data) return;

            queueMicrotask(() => {
                try {
                    localStorage.setItem(KEYS[scope], JSON.stringify(data));
                } catch {}
            });
        },

        isPinned(id, scope = 'contact') {
            const s = this.sets[scope];
            if (!s) return false;
            return s.has(scope === 'menu' ? String(id) : Number(id));
        },

        _fresh(scope, fallback) {
            try {
                if (localStorage.getItem(KEYS[scope]) === null) return fallback;
            } catch {
                return fallback;
            }

            return this._load(scope);
        },

        togglePin(id, scope = 'contact') {
            const current = Alpine.raw(this.sets[scope]);
            if (!current) return;

            const rawSet = this._fresh(scope, current);

            const key = scope === 'menu' ? String(id) : Number(id);
            const nextSet = new Set(rawSet);

            if (nextSet.has(key)) {
                nextSet.delete(key);
            } else {
                nextSet.add(key);
            }

            this.sets[scope] = nextSet;
            this.lists[scope] = Array.from(nextSet);

            this._persist(scope);
        },

        isEmpty(scope) {
            return this.getPinned(scope).length === 0;
        },

        getPinned(scope = 'contact') {
            return this.lists[scope] || [];
        }
    });
}
