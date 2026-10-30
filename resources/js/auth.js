const DB_NAME = 'auth-media';
const DB_STORE = 'media';
const FADE = 1.2;
const NOTE_MS = 4000;
const MAX_IMAGE = 5 * 1024 * 1024;
const MAX_VIDEO = 20 * 1024 * 1024;

let dbPromise;
const openDb = () => dbPromise ??= new Promise((res, rej) => {
    const r = indexedDB.open(DB_NAME, 1);
    r.onupgradeneeded = () => r.result.createObjectStore(DB_STORE);
    r.onsuccess = () => res(r.result);
    r.onerror = () => rej(r.error);
});

const idbGet = async key => {
    try {
        const db = await openDb();
        return await new Promise((res, rej) => {
            const rq = db.transaction(DB_STORE).objectStore(DB_STORE).get(key);
            rq.onsuccess = () => res(rq.result ?? null);
            rq.onerror = () => rej(rq.error);
        });
    } catch {
        return null;
    }
};

const idbWrite = async run => {
    const db = await openDb();
    await new Promise((res, rej) => {
        const tx = db.transaction(DB_STORE, 'readwrite');
        run(tx.objectStore(DB_STORE));
        tx.oncomplete = res;
        tx.onerror = () => rej(tx.error);
        tx.onabort = () => rej(tx.error);
    });
};

const mediaPut = (key, blob) => idbWrite(s => s.put(blob, key));
const mediaDel = key => idbWrite(s => s.delete(key));

const authBackground = (videos = []) => ({
    theme: localStorage.getItem('user-theme') || 'default',
    videoBrightness: +localStorage.getItem('video-brightness') || 60,
    useVideo: localStorage.getItem('use-video') !== 'false',
    loopVideo: localStorage.getItem('video-loop') !== 'false',
    videos,
    active: 0,
    curIdx: 0,
    nextIdx: 0,
    triggered: false,
    fade: FADE,
    rate: 1,
    customImage: null,
    customImageSize: 0,
    customVideoUrl: null,
    customVideoSize: 0,
    mediaNote: '',
    mediaDefaults: null,

    _v: [null, null],
    _t1: 0,
    _t2: 0,
    _noteTimer: 0,

    init() {
        this.$watch('videoBrightness', v => localStorage.setItem('video-brightness', v));
        this.$watch('loopVideo', v => localStorage.setItem('video-loop', v));
        this.$watch('useVideo', v => {
            localStorage.setItem('use-video', v);
            if (!v) this.pauseBoth();
        });
        this.loadMedia().finally(() => this.initVideo());
    },

    pauseBoth() {
        this._v[0]?.pause();
        this._v[1]?.pause();
    },

    clearTimers() {
        clearTimeout(this._t1);
        clearTimeout(this._t2);
    },

    initVideo(revokeUrl = null) {
        if (!this.videos.length) {
            this.useVideo = false;
            return;
        }
        if (!this.useVideo) return;

        this.clearTimers();
        this.pauseBoth();

        this.active = 0;
        this.triggered = false;
        this.curIdx = (Math.random() * this.videos.length) | 0;

        this.$nextTick(() => {
            const v = this.$refs.v0;
            if (!v) return;
            this._v[0] = v;
            this._v[1] = this.$refs.v1;

            v.preload = 'auto';
            v.src = this.videos[this.curIdx];
            v.playbackRate = this.rate;

            const start = () => {
                if (!this.useVideo) return;
                v.play().finally(() => revokeUrl && URL.revokeObjectURL(revokeUrl)).catch(() => {});
                this.preroll(1);
            };
            const fail = () => {
                this.useVideo = false;
                revokeUrl && URL.revokeObjectURL(revokeUrl);
            };

            v.addEventListener('canplay', start, { once: true });
            v.addEventListener('error', fail, { once: true });
        });
    },

    preroll(b) {
        const n = this.videos.length;
        this.nextIdx = n <= 1 ? this.curIdx : this.pickOther(this.curIdx, n);

        const el = this._v[b] || this.$refs['v' + b];
        if (!el) return;
        this._v[b] = el;
        el.preload = 'auto';
        el.src = this.videos[this.nextIdx];
    },

    pickOther(cur, n) {
        let idx;
        do { idx = (Math.random() * n) | 0; } while (idx === cur);
        return idx;
    },

    check(i) {
        if (i !== this.active || this.triggered) return;
        const el = this._v[i];
        if (!el || !el.duration) return;

        const nxt = this._v[i ^ 1];
        if ((el.duration - el.currentTime) / el.playbackRate <= this.fade && nxt?.readyState >= 3) {
            this.swap(i);
        }
    },

    swap(i) {
        if (!this.loopVideo || this.triggered || i !== this.active) return;
        this.triggered = true;

        const next = i ^ 1;
        const el = this._v[next];
        if (!el) {
            this.triggered = false;
            return;
        }

        el.playbackRate = this.rate;

        el.play().then(() => {
            this._v[i].pause();
            this.active = next;
            this.curIdx = this.nextIdx;
            this._t1 = setTimeout(() => { this.triggered = false; }, this.fade * 1000);
            this._t2 = setTimeout(() => this.preroll(i), this.fade * 1000 + 50);
        }).catch(() => { this.triggered = false; });
    },

    async loadMedia() {
        this.mediaDefaults = this.videos.slice();
        const [img, vid] = await Promise.all([idbGet('image'), idbGet('video')]);
        if (img) {
            this.customImage = URL.createObjectURL(img);
            this.customImageSize = img.size;
        }
        if (vid) {
            this.customVideoUrl = URL.createObjectURL(vid);
            this.customVideoSize = vid.size;
            this.videos = [this.customVideoUrl];
        }
    },

    flashMediaNote(msg) {
        clearTimeout(this._noteTimer);
        this.mediaNote = msg;
        this._noteTimer = setTimeout(() => { this.mediaNote = ''; }, NOTE_MS);
    },

    async pickMedia(kind, ev) {
        const f = ev.target.files[0];
        ev.target.value = '';
        if (!f) return;

        const max = kind === 'image' ? MAX_IMAGE : MAX_VIDEO;
        if (f.size > max) {
            this.flashMediaNote(`حجم فایل بیش از حد مجاز است (حداکثر ${kind === 'image' ? '۵' : '۲۰'} مگابایت)`);
            return;
        }

        try {
            await mediaPut(kind, f);
        } catch {
            this.flashMediaNote('فضای ذخیره‌سازی مرورگر کافی نیست');
            return;
        }

        if (kind === 'image') {
            this.customImage && URL.revokeObjectURL(this.customImage);
            this.customImage = URL.createObjectURL(f);
            this.customImageSize = f.size;
            this.useVideo = false;
        } else {
            const old = this.customVideoUrl;
            this.customVideoUrl = URL.createObjectURL(f);
            this.customVideoSize = f.size;
            this.videos = [this.customVideoUrl];
            this.useVideo = true;
            this.initVideo(old);
        }
    },

    async clearMedia(kind) {
        try {
            await mediaDel(kind);
        } catch {
            this.flashMediaNote('حذف با خطا مواجه شد، دوباره تلاش کنید');
            return;
        }

        if (kind === 'image') {
            this.customImage && URL.revokeObjectURL(this.customImage);
            this.customImage = null;
            this.customImageSize = 0;
        } else {
            const old = this.customVideoUrl;
            this.customVideoUrl = null;
            this.customVideoSize = 0;
            this.videos = this.mediaDefaults;
            this.initVideo(old);
        }
    },
});

const authMusic = src => ({
    playing: false,
    audio: null,

    persist() {
        localStorage.setItem('auth-music', this.playing ? 'on' : 'off');
    },

    toggle() {
        if (!this.audio) {
            this.audio = new Audio(src);
            this.audio.loop = true;
        }
        if (this.playing) {
            this.audio.pause();
            this.playing = false;
        } else {
            this.audio.play().then(() => { this.playing = true; }).catch(() => {});
        }
        this.persist();
    },

    init() {
        if (localStorage.getItem('auth-music') !== 'on') return;
        const resume = () => {
            document.removeEventListener('click', resume);
            document.removeEventListener('keydown', resume);
            if (localStorage.getItem('auth-music') === 'on' && !this.playing) this.toggle();
        };
        document.addEventListener('click', resume);
        document.addEventListener('keydown', resume);
    },
});

document.addEventListener('alpine:init', () => {
    Alpine.data('authBackground', authBackground);
    Alpine.data('authMusic', authMusic);
});
