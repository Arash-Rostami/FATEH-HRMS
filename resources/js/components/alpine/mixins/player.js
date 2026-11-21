import persistentStateMixin from "./persistentState.js";

const EVENT_PAUSE_REQ = 'audio-pause-request';
const EVENT_STATE = 'media:state';
const EVENT_ENDED = 'media:ended';
const PROGRESS_KEY = 'media_progress';
const PENDING_KEY = 'media_progress_pending';
const SPEEDS = [1, 1.25, 1.5, 2, 0.75];
const FA_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
const DIGIT_REGEX = /[0-9]/g;
const INPUT_SEL = 'input, textarea, select, [contenteditable]';
const SAFETY_TICK_MS = 15000;

const shared = { element: null, kind: null, prebuffer: null, ui: null, bound: false, keydown: null, interval: null, stateRaf: null };

const fmt = (s) => {
    if (!Number.isFinite(s) || s <= 0) return '0:00';
    const total = Math.floor(s);
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const c = total % 60;
    return (h ? h + ':' + String(m).padStart(2, '0') : m) + ':' + String(c).padStart(2, '0');
};

const fa = (s) => String(s).replace(DIGIT_REGEX, (d) => FA_DIGITS[d]);

const faTime = (s) => fa(fmt(s));

const live = () => !!(shared.element && !shared.element.paused && !shared.element.ended);

const emitState = (ui) => {
    window.dispatchEvent(new CustomEvent(EVENT_STATE, {
        detail: { playing: ui.playing, sid: ui.cur.sid, idx: ui.cur.idx, t: ui.t, duration: ui.duration },
    }));
};

const emitStateCoalesced = (ui) => {
    if (shared.stateRaf) return;
    shared.stateRaf = requestAnimationFrame(() => {
        shared.stateRaf = null;
        emitState(ui);
    });
};

function ensureElement(ui, kind) {
    if (shared.element && shared.kind === kind) {
        return shared.element;
    }
    if (shared.element) shared.element.pause();
    shared.element = kind === 'video' ? document.createElement('video') : new Audio();
    shared.kind = kind;
    shared.element.preload = 'metadata';
    shared.element.volume = ui.vol;
    shared.element.muted = ui.muted;
    bindElement(ui);
    return shared.element;
}

function bindElement(ui) {
    const el = shared.element;
    if (!el || shared.bound) return;
    shared.bound = true;

    el.addEventListener('playing', () => {
        if (!shared.ui) return;
        shared.ui.buffering = false;
        shared.ui.playing = true;
        emitState(shared.ui);
        startSafetyInterval();
    });

    el.addEventListener('pause', () => {
        if (!shared.ui) return;
        shared.ui.playing = false;
        stopSafetyInterval();
        shared.ui.flush('pause');
        emitState(shared.ui);
    });

    el.addEventListener('ended', () => {
        if (!shared.ui) return;
        shared.ui.playing = false;
        stopSafetyInterval();
        emitState(shared.ui);
        window.dispatchEvent(new CustomEvent(EVENT_ENDED, { detail: { sid: shared.ui.cur.sid, idx: shared.ui.cur.idx } }));
        shared.ui.next(true);
    });

    el.addEventListener('error', () => {
        if (!shared.ui) return;
        shared.ui.playing = false;
        shared.ui.buffering = false;
        shared.ui.error = true;
        stopSafetyInterval();
        emitState(shared.ui);
    });

    el.addEventListener('waiting', () => { if (shared.ui) shared.ui.buffering = true; });
    el.addEventListener('canplay', () => { if (shared.ui) shared.ui.buffering = false; });

    el.addEventListener('timeupdate', () => {
        if (!shared.ui) return;
        shared.ui.t = el.currentTime;
        if (Number.isFinite(el.duration) && el.duration > 0) shared.ui.duration = el.duration;
        if (shared.ui.playing && shared.ui.t - shared.ui._lastPersist >= (shared.ui.sync.local_seconds || 5)) {
            shared.ui._lastPersist = shared.ui.t;
            shared.ui._storeLocal(shared.ui.episode()?.id, Math.floor(shared.ui.t));
        }
        shared.ui.maybePrebuffer();
        emitStateCoalesced(shared.ui);
    });

    el.addEventListener('loadedmetadata', () => {
        if (!shared.ui) return;
        if (Number.isFinite(el.duration) && el.duration > 0 && !shared.ui.duration) shared.ui.duration = el.duration;
    });
}

function startSafetyInterval() {
    if (shared.interval) return;
    shared.interval = setInterval(() => {
        if (!live() || !shared.ui) {
            stopSafetyInterval();
            return;
        }
        if (shared.ui.t - shared.ui._lastFlush >= (shared.ui.sync.safety_seconds || 90)) shared.ui.flush('safety');
    }, SAFETY_TICK_MS);
}

function stopSafetyInterval() {
    if (shared.interval) {
        clearInterval(shared.interval);
        shared.interval = null;
    }
}

window.addEventListener(EVENT_PAUSE_REQ, () => {
    if (live()) shared.element.pause();
});

document.addEventListener('visibilitychange', () => {
    if (document.hidden && shared.ui?.playing) shared.ui.flush('hidden');
});

window.addEventListener('pagehide', () => { if (shared.ui) shared.ui.flush('pagehide'); });
window.addEventListener('freeze', () => { if (shared.ui) shared.ui.flush('freeze'); });

window.addEventListener('pageshow', (e) => {
    if (e.persisted && shared.ui) shared.ui.flush('bfcache');
});

document.addEventListener('livewire:navigating', () => {
    if (!shared.ui) return;
    shared.ui.flush('navigating');
    if (shared.ui.playing) shared.element.pause();
});

export default function playerMixin() {
    return {
        ...persistentStateMixin(),
        library: { items: [], progress: {}, notes: {} },
        cur: { sid: null, idx: 0 },
        t: 0,
        duration: 0,
        playing: false,
        buffering: false,
        speed: 1,
        vol: 0.8,
        muted: false,
        ready: false,
        error: false,
        tapToPlay: false,
        progressUrl: '',
        sync: { local_seconds: 5, safety_seconds: 90, min_delta: 10, prebuffer_remaining_seconds: 120 },
        _lastPersist: 0,
        _lastFlush: 0,
        _csrf: '',

        get timeLabel() {
            return faTime(this.t) + ' / ' + faTime(this.duration);
        },

        get progressRatio() {
            return this.duration > 0 ? Math.max(0, Math.min(1, this.t / this.duration)) : 0;
        },

        get currentEpisode() {
            return this.episode();
        },

        loadLibrary(payload) {
            this.library = payload ?? { items: [], progress: {}, notes: {} };
            this.ready = true;
        },

        item(sid) {
            const id = sid ?? this.cur.sid;
            return this.library?.items?.find((i) => i.id === id) ?? null;
        },

        episode(sid, idx) {
            return this.item(sid)?.episodes?.[idx ?? this.cur.idx] ?? null;
        },

        episodeState(sid, idx) {
            const ep = this.episode(sid, idx);
            if (!ep) return 'new';
            if (ep.unlock_at) return 'locked';
            const p = this.library.progress?.[ep.id];
            if (p?.completed) return 'done';
            if (this.cur.sid === (sid ?? this.cur.sid) && this.cur.idx === (idx ?? this.cur.idx)) return 'current';
            return 'new';
        },

        progressOf(sid) {
            const item = this.item(sid);
            if (!item?.episodes?.length) return 0;
            let total = 0;
            let done = 0;
            for (const ep of item.episodes) {
                total += ep.duration || 0;
                const p = this.library.progress?.[ep.id];
                done += Math.min(p?.position ?? 0, ep.duration || p?.position || 0);
            }
            return total > 0 ? Math.min(1, done / total) : 0;
        },

        resumeAt(sid, idx) {
            const ep = this.episode(sid, idx);
            if (!ep) return 0;
            const local = this._loadState(PROGRESS_KEY, {})[ep.id]?.position ?? 0;
            const server = this.library.progress?.[ep.id];
            const serverPos = server?.position ?? 0;
            const furthest = Math.max(local, serverPos);
            if (furthest < 5) return 0;
            return server?.last_position ?? furthest;
        },

        selectItem(id) {
            if (!id || this.cur.sid === id) return;
            this.cur = { sid: id, idx: 0 };
        },

        playEpisode(sid, idx) {
            const item = this.item(sid);
            const ep = this.episode(sid, idx);
            if (!item || !ep || ep.unlock_at) return;
            if (this.playing && this.cur.sid === sid && this.cur.idx === idx) {
                this.toggle();
                return;
            }
            if (live() || this.playing) this.flush('switch');
            window.dispatchEvent(new CustomEvent(EVENT_PAUSE_REQ));
            const el = ensureElement(this, item.kind === 'video' ? 'video' : 'audio');
            shared.ui = this;
            this.cur = { sid, idx };
            this.t = 0;
            this.duration = ep.duration || 0;
            this.error = false;
            this.buffering = true;
            this.tapToPlay = false;
            this._lastPersist = 0;
            this._lastFlush = 0;
            this._releasePrebuffer();

            if (!this.canPlay(ep.mime)) {
                this.error = true;
                this.buffering = false;
                return;
            }

            el.src = ep.file_url;
            this._mountTracks(el, ep);
            el.load();
            const at = this.resumeAt(sid, idx);
            if (at > 0) this._seekOnMeta(el, at);
            el.playbackRate = this.speed;
            this.initMediaSession();

            el.play().then(() => {
                this.playing = true;
                emitState(this);
            }).catch(() => {
                this.playing = false;
                this.tapToPlay = true;
                emitState(this);
            });
            emitState(this);
        },

        toggle() {
            if (!shared.element || !this.cur.sid || this.error) return;
            if (live()) {
                shared.element.pause();
            } else {
                shared.element.play().catch(() => { this.tapToPlay = true; });
            }
        },

        seek(seconds) {
            if (!shared.element || !Number.isFinite(seconds)) return;
            const target = this.duration > 0 ? Math.max(0, Math.min(seconds, this.duration)) : Math.max(0, seconds);
            shared.element.currentTime = target;
            this.t = target;
        },

        skip(delta) {
            this.seek(this.t + delta);
        },

        seekFromEvent(e, rectEl) {
            const rect = (rectEl || e.currentTarget).getBoundingClientRect();
            const fraction = Math.min(1, Math.max(0, (rect.right - e.clientX) / rect.width));
            this.seek(this.duration * fraction);
        },

        next(auto = false) {
            const item = this.item();
            const nextIdx = this._nextPlayable(this.cur.idx + 1);
            if (!item || nextIdx === null) {
                this.playing = false;
                return;
            }
            this.playEpisode(this.cur.sid, nextIdx);
            if (!auto && item.episodes[nextIdx]) this.toast('جلسهٔ بعد: ' + item.episodes[nextIdx].title);
        },

        prev() {
            if (this.t > 5) {
                this.seek(0);
                return;
            }
            const prevIdx = this._prevPlayable(this.cur.idx - 1);
            if (prevIdx !== null) this.playEpisode(this.cur.sid, prevIdx);
        },

        setSpeed(v) {
            const next = SPEEDS.includes(Number(v)) ? Number(v) : SPEEDS[(SPEEDS.indexOf(this.speed) + 1) % SPEEDS.length];
            this.speed = next;
            if (shared.element) shared.element.playbackRate = next;
        },

        setVolume(v) {
            this.vol = v < 0 ? 0 : (v > 1 ? 1 : Number(v) || 0);
            if (this.vol > 0) this.muted = false;
            if (shared.element) {
                shared.element.volume = this.vol;
                shared.element.muted = this.muted;
            }
        },

        toggleMute() {
            this.muted = !this.muted;
            if (shared.element) shared.element.muted = this.muted;
        },

        canPlay(mime) {
            if (!mime) return true;
            const el = shared.element;
            return !el || el.canPlayType(mime) !== '';
        },

        flush(reason) {
            if (!this.cur.sid) return;
            const ep = this.episode();
            if (!ep) return;
            const tolerance = 5;
            const cap = (ep.duration || this.duration || 0) + tolerance;
            const position = Math.max(0, Math.min(Math.floor(Math.max(this.t, this._storedPos(ep.id))), cap));
            const lastPosition = Math.max(0, Math.floor(this.t));
            const storedPos = Math.floor(this._loadState(PROGRESS_KEY, {})[ep.id]?.position ?? 0);
            if (position === storedPos && reason !== 'pagehide' && reason !== 'freeze') {
                this._lastFlush = this.t;
                return;
            }
            const ratio = ep.duration ? position / ep.duration : 0;
            const completed = ratio >= 0.95;
            const entry = {
                type: 'media',
                id: ep.id,
                position,
                last_position: lastPosition,
                completed,
                duration: Math.floor(this.duration) || null,
            };
            this._storeLocal(ep.id, position);
            this._queuePending([entry]);
            this._lastFlush = this.t;
            this._sendProgress(this.progressUrl, { items: [entry] });
        },

        destroyPlayer() {
            this.flush('destroy');
            stopSafetyInterval();
            this._releasePrebuffer();
            if (shared.ui === this) {
                shared.ui = null;
                if (shared.element) shared.element.pause();
            }
        },

        bindPlayerKeys(rootSelector) {
            if (shared.keydown) document.removeEventListener('keydown', shared.keydown);
            shared.keydown = (e) => {
                const root = document.querySelector(rootSelector);
                if (!root || !root.contains(e.target) || e.target.closest?.(INPUT_SEL)) return;
                if (e.code === 'Space' && !e.target.closest('button, [role="button"], a')) {
                    e.preventDefault();
                    this.toggle();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.skip(10);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.skip(-10);
                } else if (e.key === 'n' || e.key === 'N') {
                    this.next();
                } else if (e.key === 'p' || e.key === 'P') {
                    this.prev();
                } else if (e.key === 'j' || e.key === 'J') {
                    this.skip(-10);
                } else if (e.key === 'l' || e.key === 'L') {
                    this.skip(10);
                }
            };
            document.addEventListener('keydown', shared.keydown);
        },

        toast(message, type = 'info') {
            if (!message) return;
            window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
        },

        faTime,

        _storedPos(id) {
            const server = this.library.progress?.[id]?.position ?? 0;
            const local = this._loadState(PROGRESS_KEY, {})[id]?.position ?? 0;
            return Math.max(server, local);
        },

        _storeLocal(id, position) {
            const stored = this._loadState(PROGRESS_KEY, {});
            const prev = stored[id];
            if (prev && prev.position >= position && (Date.now() - prev.updatedAt) < 86400000) return;
            this._saveState(PROGRESS_KEY, { ...stored, [id]: { position, updatedAt: Date.now() } });
        },

        _nextPlayable(from) {
            const episodes = this.item()?.episodes ?? [];
            for (let i = from; i < episodes.length; i++) {
                if (!episodes[i].unlock_at) return i;
            }
            return null;
        },

        _prevPlayable(from) {
            const episodes = this.item()?.episodes ?? [];
            for (let i = from; i >= 0; i--) {
                if (!episodes[i].unlock_at) return i;
            }
            return null;
        },

        _seekOnMeta(el, at) {
            const seek = () => {
                el.currentTime = at;
                el.removeEventListener('loadedmetadata', seek);
            };
            if (el.readyState >= 1) {
                el.currentTime = at;
                return;
            }
            el.addEventListener('loadedmetadata', seek);
        },

        _mountTracks(el, ep) {
            el.querySelectorAll('track').forEach((n) => n.remove());
            if (ep.transcript_url) {
                el.appendChild(this._track('subtitles', ep.transcript_url));
            }
            if (ep.chapters_url) {
                el.appendChild(this._track('chapters', ep.chapters_url));
            }
        },

        _track(kind, src) {
            const tr = document.createElement('track');
            tr.kind = kind;
            tr.src = src;
            tr.srclang = 'fa';
            return tr;
        },

        _queuePending(items) {
            const pending = this._loadState(PENDING_KEY, []);
            this._saveState(PENDING_KEY, [...pending, ...items].slice(-100));
        },

        _drainPending() {
            const pending = this._loadState(PENDING_KEY, []);
            if (!pending.length) return;
            this._sendProgress(this.progressUrl, { items: pending }, true);
            this._saveState(PENDING_KEY, []);
        },

        _sendProgress(url, body, keepalive = false) {
            if (!url) return false;
            const payload = { ...body, _token: this._csrf };
            if (!keepalive && navigator.sendBeacon) {
                try {
                    return navigator.sendBeacon(url, new Blob([JSON.stringify(payload)], { type: 'application/json' }));
                } catch {
                }
            }
            fetch(url, {
                method: 'POST',
                keepalive: true,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            }).then((r) => {
                if (!r.ok && r.status === 419) this._queuePending(body.items ?? []);
            }).catch(() => {});
            return true;
        },

        _releasePrebuffer() {
            if (!shared.prebuffer) return;
            shared.prebuffer.removeAttribute('src');
            shared.prebuffer.load();
            shared.prebuffer = null;
        },

        maybePrebuffer() {
            if (!this.playing || shared.prebuffer) return;
            if (navigator.connection?.saveData) return;
            const remaining = this.duration - this.t;
            if (remaining > (this.sync.prebuffer_remaining_seconds || 120)) return;
            const nextIdx = this._nextPlayable(this.cur.idx + 1);
            const nextEp = nextIdx === null ? null : this.item()?.episodes?.[nextIdx];
            if (!nextEp?.file_url) return;
            shared.prebuffer = new Audio();
            shared.prebuffer.preload = 'auto';
            shared.prebuffer.src = nextEp.file_url;
            shared.prebuffer.load();
        },

        initMediaSession() {
            if (!('mediaSession' in navigator) || !this.cur.sid) return;
            const item = this.item();
            const ep = this.episode();
            if (!item || !ep) return;
            try {
                navigator.mediaSession.metadata = new MediaMetadata({
                    title: ep.title,
                    artist: item.title,
                    album: item.title,
                });
                navigator.mediaSession.setActionHandler('play', () => this.toggle());
                navigator.mediaSession.setActionHandler('pause', () => this.toggle());
                navigator.mediaSession.setActionHandler('seekbackward', () => this.skip(-10));
                navigator.mediaSession.setActionHandler('seekforward', () => this.skip(10));
                navigator.mediaSession.setActionHandler('nexttrack', () => this.next());
                navigator.mediaSession.setActionHandler('previoustrack', () => this.prev());
            } catch {
            }
        },
    };
}
