import persistentStateMixin from "./persistentState.js";

const EVENT_PAUSE_REQ = 'audio-pause-request';
const EVENT_PLAY_STATE = 'audio-play-state';
const EVENT_STATE = 'media:state';
const EVENT_ENDED = 'media:ended';
const PROGRESS_KEY = 'media_progress';
const PENDING_KEY = 'media_progress_pending';
const PREFS_KEY = 'media_prefs';
const UNDO_MS = 8000;
const SPEEDS = [1, 1.25, 1.5, 2, 0.75];
const FA_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
const DIGIT_REGEX = /[0-9]/g;
const INPUT_SEL = 'input, textarea, select, [contenteditable]';
const SAFETY_TICK_MS = 15000;
const PENDING_CAP = 100;
const CHUNK_SIZE = 50;

const shared = {
    element: null, kind: null, prebuffer: null, ui: null, bound: false,
    keydown: null, interval: null, stateRaf: null, resumeSeek: null, host: null, selfPause: false, userId: null,
    wakeLock: null, wakePending: false, undoTimer: null,
};

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

const isVideoEp = (ep, item) => (ep?.mime ? ep.mime.startsWith('video/') : item?.kind === 'video');

const live = () => !!(shared.element && !shared.element.paused && !shared.element.ended);

const wantsWake = () => live() && shared.kind === 'video';

function releaseWakeLock() {
    const lock = shared.wakeLock;
    shared.wakeLock = null;
    Promise.resolve(lock?.release()).catch(() => {});
}

async function syncWakeLock() {
    if (!wantsWake()) return releaseWakeLock();
    if (shared.wakeLock || shared.wakePending || document.hidden || !navigator.wakeLock) return;
    shared.wakePending = true;
    try {
        const lock = await navigator.wakeLock.request('screen');
        lock.addEventListener('release', () => { if (shared.wakeLock === lock) shared.wakeLock = null; });
        shared.wakeLock = lock;
        if (!wantsWake()) releaseWakeLock();
    } catch {
    } finally {
        shared.wakePending = false;
    }
}

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

const rev = (i) => i.id + ':' + i.position + ':' + i.last_position + ':' + (i.completed ? 1 : 0);

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function ensureElement(ui, kind) {
    if (shared.element && shared.kind === kind) {
        return shared.element;
    }
    if (shared.element) {
        if (shared.kind === 'video' && shared.element.parentNode) shared.element.parentNode.removeChild(shared.element);
        shared.element.pause();
    }
    shared.element = kind === 'video' ? document.createElement('video') : new Audio();
    shared.kind = kind;
    shared.bound = false;
    shared.element.preload = 'metadata';
    shared.element.volume = ui.vol;
    shared.element.muted = ui.muted;
    shared.element.setAttribute('playsinline', '');
    bindElement(ui);
    if (kind === 'video' && shared.host) shared.host.appendChild(shared.element);
    syncWakeLock();
    return shared.element;
}

function bindElement(ui) {
    const el = shared.element;
    if (!el || shared.bound) return;
    shared.bound = true;

    el.addEventListener('playing', () => {
        if (!shared.ui || shared.element !== el) return;
        shared.ui.buffering = false;
        shared.ui.playing = true;
        emitState(shared.ui);
        startSafetyInterval();
        syncWakeLock();
    });

    el.addEventListener('pause', () => {
        if (!shared.ui || shared.element !== el || !el.paused) return;
        shared.ui.playing = false;
        stopSafetyInterval();
        releaseWakeLock();
        shared.ui.flush('pause');
        emitState(shared.ui);
    });

    el.addEventListener('ended', () => {
        if (!shared.ui || shared.element !== el) return;
        shared.ui.playing = false;
        stopSafetyInterval();
        releaseWakeLock();
        if (Number.isFinite(el.duration) && el.duration > 0) shared.ui.t = el.duration;
        shared.ui.flush('ended');
        emitState(shared.ui);
        window.dispatchEvent(new CustomEvent(EVENT_ENDED, { detail: { sid: shared.ui.cur.sid, idx: shared.ui.cur.idx } }));
        shared.ui.next(true);
    });

    el.addEventListener('error', () => {
        if (!shared.ui || shared.element !== el) return;
        shared.ui.playing = false;
        shared.ui.buffering = false;
        shared.ui.error = true;
        stopSafetyInterval();
        releaseWakeLock();
        emitState(shared.ui);
    });

    el.addEventListener('waiting', () => { if (shared.ui) shared.ui.buffering = true; });
    el.addEventListener('canplay', () => { if (shared.ui) shared.ui.buffering = false; });

    el.addEventListener('timeupdate', () => {
        if (!shared.ui || shared.element !== el) return;
        shared.ui.t = el.currentTime;
        if (Number.isFinite(el.duration) && el.duration > 0) shared.ui.duration = el.duration;
        const ep = shared.ui.episode();
        if (ep && shared.ui.playing) {
            if (shared.ui.t > 0) shared.ui._touched[ep.id] = true;
            if (Math.abs(shared.ui.t - shared.ui._lastPersist) >= (shared.ui.sync.local_seconds || 5)) {
                shared.ui._lastPersist = shared.ui.t;
                shared.ui._trackLocal(ep, shared.ui.t);
            }
        }
        shared.ui.maybePrebuffer();
        emitStateCoalesced(shared.ui);
    });

    el.addEventListener('enterpictureinpicture', () => {
        if (shared.ui && shared.element === el) shared.ui.pictureInPicture = true;
    });

    el.addEventListener('leavepictureinpicture', () => {
        if (shared.ui) shared.ui.pictureInPicture = false;
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

function cancelResumeSeek(el) {
    if (shared.resumeSeek) {
        el.removeEventListener('loadedmetadata', shared.resumeSeek);
        shared.resumeSeek = null;
    }
}

window.addEventListener(EVENT_PAUSE_REQ, () => {
    if (!shared.selfPause && live()) shared.element.pause();
});

window.addEventListener(EVENT_PLAY_STATE, (e) => {
    if (e.detail === true && live()) shared.element.pause();
});

document.addEventListener('visibilitychange', () => {
    if (document.hidden && shared.ui?.playing) shared.ui.flush('hidden');
    if (!document.hidden) syncWakeLock();
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
        pictureInPicture: false,
        fullscreen: false,
        seeking: false,
        progressUrl: '',
        userId: '',
        stopAfter: false,
        undo: null,
        sync: { local_seconds: 5, safety_seconds: 90, min_delta: 10, prebuffer_remaining_seconds: 120, resume_rewind: 10, completion_ratio: 0.95, position_tolerance: 5 },
        _lastPersist: 0,
        _lastFlush: 0,
        _loadedAt: 0,
        _host: null,
        _synced: {},
        _touched: {},
        _inflight: {},

        get timeLabel() {
            return faTime(this.t) + ' / ' + faTime(this.duration);
        },

        get progressRatio() {
            return this.duration > 0 ? Math.max(0, Math.min(1, this.t / this.duration)) : 0;
        },

        get currentEpisode() {
            return this.episode();
        },

        get isVideo() {
            return isVideoEp(this.episode(), this.item());
        },

        get fa() {
            return fa;
        },

        get canPictureInPicture() {
            return !!document.pictureInPictureEnabled;
        },

        loadLibrary(payload) {
            this.library = payload ?? { items: [], progress: {}, notes: {} };
            this._loadPrefs();
            this.ready = true;
            this._loadedAt = Date.now();
            if (shared.ui && shared.ui !== this) {
                if (shared.userId === this.userId) this.adoptPlayback();
                else this._releasePlayback();
            }
            this._drainPending();
        },

        _releasePlayback() {
            shared.ui._synced = {};
            shared.ui = null;
            shared.userId = null;
            shared.element?.pause();
            stopSafetyInterval();
            this._releasePrebuffer();
        },

        adoptPlayback() {
            const prev = shared.ui;
            if (!shared.element || !prev || prev === this) return false;
            this._synced = prev._synced;
            this._touched = prev._touched;
            this._inflight = prev._inflight;
            this._lastFlush = prev._lastFlush;
            this.cur = { ...prev.cur };
            shared.ui = this;
            this.t = shared.element.currentTime;
            this.duration = Number.isFinite(shared.element.duration) ? shared.element.duration : this.duration;
            this.playing = live();
            this.error = false;
            if (this.playing) startSafetyInterval();
            return true;
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

        canMarkDone(sid, idx) {
            const ep = this.episode(sid, idx);
            return !!ep?.file_url && ep.duration > 0 && !this.library.progress?.[ep.id]?.completed;
        },

        markDone(sid, idx) {
            if (!this.canMarkDone(sid, idx)) return;
            const ep = this.episode(sid, idx);
            const position = Math.floor(ep.duration);
            this._storeLocal(ep.id, position);
            this._markProgress(ep.id, position, position, true);
            this._queuePending([{ type: 'media', id: ep.id, position, last_position: position, completed: true, duration: position }]);
            this._drainPending();
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
            const local = this._loadState(this._k(PROGRESS_KEY), {})[ep.id];
            const server = this.library.progress?.[ep.id];
            if (Math.max(local?.position ?? 0, server?.position ?? 0) < 5) return 0;
            const localStop = local?.last_position ?? local?.position ?? 0;
            const serverAt = server?.updated_at ? server.updated_at * 1000 : this._loadedAt;
            const stop = local && local.updatedAt > serverAt ? localStop : (server?.last_position ?? localStop);
            const back = this.sync.resume_rewind ?? 10;
            if (ep.duration > 0 && stop >= ep.duration - back) return 0;
            return stop > back ? stop - back : stop;
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
            if (this.playing && this.cur.sid) this._offerUndo();
            if (shared.ui?.cur.sid) shared.ui.flush('switch');
            shared.selfPause = true;
            window.dispatchEvent(new CustomEvent(EVENT_PAUSE_REQ));
            shared.selfPause = false;
            const el = ensureElement(this, isVideoEp(ep, item) ? 'video' : 'audio');
            shared.ui = this;
            shared.userId = this.userId;
            delete this._touched[ep.id];
            this.cur = { sid, idx };
            this.t = 0;
            this.duration = ep.duration || 0;
            this.error = false;
            this.buffering = true;
            this.tapToPlay = false;
            this._lastPersist = 0;
            this._lastFlush = 0;
            this._releasePrebuffer();
            cancelResumeSeek(el);

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
            this.tapToPlay = false;
            if (live()) {
                shared.element.pause();
            } else {
                shared.element.play().catch(() => { this.tapToPlay = true; });
            }
        },

        async togglePictureInPicture() {
            const el = shared.element;
            if (!el || shared.kind !== 'video') return;
            try {
                if (document.pictureInPictureElement === el) await document.exitPictureInPicture();
                else await el.requestPictureInPicture();
            } catch {
                this.toast('نمایش در پنجرهٔ شناور انجام نشد.', 'error');
            }
        },

        toggleFullscreen() {
            if (document.fullscreenElement) {
                document.exitFullscreen().catch(() => {});
                return;
            }
            const el = shared.element;
            if (!el || shared.kind !== 'video') return;
            const stage = document.querySelector('[data-video-stage]');
            const target = stage && stage.offsetParent !== null ? stage : el;
            const request = target.requestFullscreen ?? target.webkitRequestFullscreen;
            if (!request) {
                el.webkitEnterFullscreen?.();
                return;
            }
            Promise.resolve(request.call(target)).catch(() => this.toast('نمایش تمام‌صفحه انجام نشد.', 'error'));
        },

        seek(seconds) {
            if (!shared.element || !Number.isFinite(seconds)) return;
            const target = this.duration > 0 ? Math.max(0, Math.min(seconds, this.duration)) : Math.max(0, seconds);
            shared.element.currentTime = target;
            this.t = target;
            const ep = this.episode();
            if (ep) this._touched[ep.id] = true;
        },

        skip(delta) {
            this.seek(this.t + delta);
        },

        next(auto = false) {
            const item = this.item();
            const nextIdx = this._nextPlayable(this.cur.idx + 1);
            if (auto && this.stopAfter) {
                this.stopAfter = false;
                this.playing = false;
                return;
            }
            if (!item || nextIdx === null) {
                if (auto) this.playing = false;
                else this.toast('آخرین جلسه است');
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
            this._savePrefs();
        },

        toggleStopAfter() {
            this.stopAfter = !this.stopAfter;
        },

        undoSwitch() {
            const back = this.undo;
            this._clearUndo();
            if (!back) return;
            this.playEpisode(back.sid, back.idx);
            this._clearUndo();
            if (shared.element && back.position > 0) this._seekOnMeta(shared.element, back.position);
        },

        setVolume(v) {
            this.vol = v < 0 ? 0 : (v > 1 ? 1 : Number(v) || 0);
            if (this.vol > 0) this.muted = false;
            if (shared.element) {
                shared.element.volume = this.vol;
                shared.element.muted = this.muted;
            }
            this._savePrefs();
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
            if (!this.cur.sid || !(shared.ui === this) || !shared.element?.src) return;
            const ep = this.episode();
            if (!ep || (this.t <= 0 && !this._touched[ep.id])) return;
            const tolerance = this.sync.position_tolerance;
            const cap =(ep.duration || this.duration || 0) + tolerance;
            const position = Math.max(0, Math.min(Math.floor(Math.max(this.t, this._storedPos(ep.id))), cap));
            const lastPosition = Math.max(0, Math.floor(this.t));
            const ack = this._synced[ep.id];
            const minDelta = this.sync.min_delta || 10;
            if (ack && reason !== 'pagehide' && reason !== 'freeze') {
                if (position === ack.position && lastPosition === ack.last_position) {
                    this._lastFlush = this.t;
                    return;
                }
                if (reason === 'safety' && position - ack.position < minDelta) {
                    this._lastFlush = this.t;
                    return;
                }
            }
            const state = position + ':' + lastPosition;
            const direct = reason === 'pagehide' || reason === 'freeze';
            if (!direct && this._inflight[ep.id] === state) {
                this._lastFlush = this.t;
                return;
            }
            this._inflight[ep.id] = state;
            const ratio = ep.duration ? position / ep.duration : 0;
            const completed = ratio >= this.sync.completion_ratio;
            const entry = {
                type: 'media',
                id: ep.id,
                position,
                last_position: lastPosition,
                completed,
                duration: Math.floor(this.duration) || null,
            };
            this._storeLocal(ep.id, position, lastPosition);
            this._markProgress(ep.id, position, lastPosition, completed);
            this._lastFlush = this.t;
            this._sendProgress(this.progressUrl, { items: [entry] }, reason === 'pagehide' || reason === 'freeze');
        },

        destroyPlayer() {
            this.flush('destroy');
            this._clearUndo();
            releaseWakeLock();
            this._releasePrebuffer();
            if (shared.keydown) {
                document.removeEventListener('keydown', shared.keydown);
                shared.keydown = null;
            }
            if (shared.kind === 'video' && shared.element?.parentNode) {
                if (document.pictureInPictureElement === shared.element) document.exitPictureInPicture?.().catch(() => {});
                shared.element.parentNode.removeChild(shared.element);
            }
            if (this._host && shared.host === this._host) shared.host = null;
            this._host = null;
            if (shared.ui === this) {
                shared.ui = null;
                stopSafetyInterval();
                if (shared.element) shared.element.pause();
            }
        },

        mountVideo(host) {
            const attach = (node) => {
                if (!node) return;
                shared.host = node;
                this._host = node;
                if (shared.kind === 'video' && shared.element && shared.element.parentNode !== node) {
                    node.appendChild(shared.element);
                }
            };
            if (host) attach(host);
            else this.$nextTick(() => attach(this.$refs.videoHost));
        },

        seekFromEvent(e, track) {
            if (!track || !(this.duration > 0)) return;
            const apply = (ev) => {
                const rect = track.getBoundingClientRect();
                if (!rect.width) return;
                const raw = (ev.clientX - rect.left) / rect.width;
                const ratio = getComputedStyle(track).direction === 'rtl' ? 1 - raw : raw;
                this.seek(Math.max(0, Math.min(1, ratio)) * this.duration);
            };
            const stop = () => {
                this.seeking = false;
                window.removeEventListener('pointermove', apply);
                window.removeEventListener('pointerup', stop);
                window.removeEventListener('pointercancel', stop);
            };
            this.seeking = true;
            apply(e);
            window.addEventListener('pointermove', apply);
            window.addEventListener('pointerup', stop);
            window.addEventListener('pointercancel', stop);
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
                    e.stopPropagation();
                    this.skip(-10);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    e.stopPropagation();
                    this.skip(10);
                } else if (e.key === 'f' || e.key === 'F') {
                    this.toggleFullscreen();
                } else if (e.key === 'm' || e.key === 'M') {
                    this.toggleMute();
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

        _k(key) {
            return this.userId ? key + ':' + this.userId : key;
        },

        _loadPrefs() {
            const prefs = this._loadState(this._k(PREFS_KEY), {}) ?? {};
            const vol = Number(prefs.vol);
            if (SPEEDS.includes(prefs.speed)) this.speed = prefs.speed;
            if (prefs.vol !== undefined && Number.isFinite(vol)) this.vol = Math.max(0, Math.min(1, vol));
        },

        _savePrefs() {
            this._saveState(this._k(PREFS_KEY), { speed: this.speed, vol: this.vol });
        },

        _offerUndo() {
            this._clearUndo();
            this.undo = { sid: this.cur.sid, idx: this.cur.idx, position: Math.floor(this.t) };
            shared.undoTimer = setTimeout(() => { this.undo = null; }, UNDO_MS);
        },

        _clearUndo() {
            clearTimeout(shared.undoTimer);
            shared.undoTimer = null;
            this.undo = null;
        },

        _trackLocal(ep, t) {
            const position = Math.floor(t);
            this._storeLocal(ep.id, position);
            this._markProgress(ep.id, position, position, !!ep.duration && position / ep.duration >= 0.95);
        },

        _storedPos(id) {
            const server = this.library.progress?.[id]?.position ?? 0;
            const local = this._loadState(this._k(PROGRESS_KEY), {})[id]?.position ?? 0;
            return Math.max(server, local);
        },

        _storeLocal(id, position, lastPosition = position) {
            const stored = this._loadState(this._k(PROGRESS_KEY), {});
            const furthest = Math.max(stored[id]?.position ?? 0, position);
            this._saveState(this._k(PROGRESS_KEY), { ...stored, [id]: { position: furthest, last_position: lastPosition, updatedAt: Date.now() } });
        },

        _markProgress(id, position, lastPosition, completed) {
            const prev = this.library.progress?.[id];
            this.library.progress = {
                ...(this.library.progress ?? {}),
                [id]: {
                    position: Math.max(prev?.position ?? 0, position),
                    last_position: lastPosition,
                    completed: completed || !!(prev?.completed),
                },
            };
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
            cancelResumeSeek(el);
            if (el.readyState >= 1) {
                el.currentTime = at;
                return;
            }
            const seek = () => {
                el.currentTime = at;
                cancelResumeSeek(el);
            };
            shared.resumeSeek = seek;
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
            try {
                const url = new URL(src, location.href);
                tr.src = url.origin !== location.origin && url.pathname.startsWith('/storage/') ? url.pathname + url.search : src;
            } catch {
                tr.src = src;
            }
            tr.srclang = 'fa';
            return tr;
        },

        _coalescePending(pending) {
            const byId = {};
            for (const item of pending) {
                byId[item.id] = item;
            }
            return Object.values(byId);
        },

        _queuePending(items) {
            const merged = this._coalescePending([...this._loadState(this._k(PENDING_KEY), []), ...items]);
            this._saveState(this._k(PENDING_KEY), merged.slice(-PENDING_CAP));
        },

        _dequeue(items) {
            const sent = new Set(items.map(rev));
            this._saveState(this._k(PENDING_KEY), this._loadState(this._k(PENDING_KEY), []).filter((i) => !sent.has(rev(i))));
        },

        async _drainPending() {
            if (!this.progressUrl) return;
            const queued = this._coalescePending(this._loadState(this._k(PENDING_KEY), []));
            const stale = (item) => item.type === 'media' && !item.completed && (this.library.progress?.[item.id]?.position ?? 0) >= item.position;
            const dropped = queued.filter(stale);
            if (dropped.length) this._dequeue(dropped);
            const pending = queued.filter((item) => !stale(item));
            for (let i = 0; i < pending.length; i += CHUNK_SIZE) {
                const chunk = pending.slice(i, i + CHUNK_SIZE);
                if (await this._post(this.progressUrl, chunk) === 'retry') return;
                this._dequeue(chunk);
            }
        },

        _post(url, items) {
            return fetch(url, {
                method: 'POST',
                keepalive: true,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ items, _token: csrfToken() }),
            }).then((r) => (r.ok ? 'ok' : (r.status === 422 ? 'drop' : 'retry'))).catch(() => 'retry');
        },

        _sendProgress(url, body, beacon = false) {
            const items = body.items ?? [];
            const settle = () => items.forEach((i) => delete this._inflight[i.id]);
            if (!url) {
                settle();
                return false;
            }
            if (beacon && navigator.sendBeacon) {
                try {
                    const blob = new Blob([JSON.stringify({ ...body, _token: csrfToken() })], { type: 'application/json' });
                    if (navigator.sendBeacon(url, blob)) {
                        settle();
                        this._queuePending(items);
                        return true;
                    }
                } catch {
                }
            }
            const ids = new Set(items.map((i) => i.id));
            const older = this._loadState(this._k(PENDING_KEY), []).filter((i) => ids.has(i.id));
            this._post(url, items).then((res) => {
                settle();
                if (res === 'retry') {
                    this._queuePending(items);
                    return;
                }
                if (res === 'ok') this._ack(items);
                this._dequeue([...items, ...older]);
            });
            return true;
        },

        _ack(items) {
            for (const item of items ?? []) {
                this._synced[item.id] = { position: item.position, last_position: item.last_position };
            }
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
                    ...(item.cover_url ? { artwork: [{ src: item.cover_url }] } : {}),
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
