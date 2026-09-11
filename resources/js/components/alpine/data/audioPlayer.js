import persistentStateMixin from "../mixins/persistentState.js";

const shared = { audio: null, url: null, src: null, name: null, custom: false, ui: null, bound: false, resumeSeek: null };
const STORAGE_KEY = 'audio_player_state';
const PERSIST_EVERY = 5;
const EVENT_PLAY_STATE = 'audio-play-state';
const EVENT_PAUSE_REQ = 'audio-pause-request';

const live = () => !!(shared.audio && !shared.audio.paused && !shared.audio.ended);

const emit = () => {
    window.dispatchEvent(new CustomEvent(EVENT_PLAY_STATE, { detail: live() }));
};

const channel = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('audio-player') : null;
let tabClaimed = false;

if (channel) {
    channel.onmessage = (e) => {
        if (e.data === 'playing') {
            tabClaimed = true;
        } else if (e.data === 'claim') {
            if (live()) shared.audio.pause();
        } else if (e.data === 'ping' && live()) {
            channel.postMessage('playing');
        }
    };
}

window.addEventListener(EVENT_PAUSE_REQ, () => {
    if (live()) shared.audio.pause();
});

const fmt = (s) => {
    if (!Number.isFinite(s) || s <= 0) return '0:00';
    const m = (s / 60) | 0;
    const c = (s % 60) | 0;
    return m + ':' + (c < 10 ? '0' + c : c);
};

function bind() {
    if (shared.bound || !shared.audio) return;
    shared.bound = true;
    const a = shared.audio;

    a.addEventListener('playing', () => {
        if (shared.ui) {
            shared.ui.playing = true;
            shared.ui.persist();
        }
        if (channel) channel.postMessage('claim');
        emit();
    });

    a.addEventListener('pause', () => {
        if (shared.ui) {
            shared.ui.playing = false;
            shared.ui.persist();
        }
        emit();
    });

    a.addEventListener('ended', () => {
        if (shared.ui) {
            shared.ui.playing = false;
            shared.ui.currentTime = 0;
            shared.ui.persist();
        }
        emit();
    });

    a.addEventListener('error', () => {
        if (shared.ui) {
            shared.ui.playing = false;
            shared.ui.currentTime = 0;
            shared.ui.duration = 0;
            shared.ui.persist();

            if (!shared.custom && shared.src !== shared.ui.defaultSrc) {
                shared.ui.load(shared.ui.defaultSrc, shared.ui.defaultName, false);
            }
        }
        emit();
    });

    let raf = null;

    a.addEventListener('timeupdate', () => {
        if (!shared.ui) return;
        if (raf) cancelAnimationFrame(raf);

        raf = requestAnimationFrame(() => {
            raf = null;
            const ui = shared.ui;
            if (!ui) return;

            const ct = a.currentTime;

            if (!ui.seeking) {
                ui.currentTime = ct;
            }

            ui.duration = a.duration || 0;

            if (ct - ui._lastPersist >= PERSIST_EVERY) {
                ui._lastPersist = ct;
                ui.persist();
            }
        });
    });
}

export default function audioPlayer(src, label, tracks = []) {
    return {
        ...persistentStateMixin(),
        defaultSrc: src,
        defaultName: label,
        tracks,
        currentSrc: src,
        trackName: label,
        isCustom: false,
        playing: false,
        muted: false,
        volume: 1,
        currentTime: 0,
        duration: 0,
        _lastPersist: 0,
        libraryOpen: false,
        volOpen: false,
        seeking: false,
        hoverProgress: null,
        _seekRect: null,
        _volT: null,

        get timeLabel() {
            return fmt(this.currentTime) + ' / ' + fmt(this.duration);
        },

        get hoverTimeLabel() {
            return fmt(this.duration * (this.hoverProgress || 0));
        },

        get progress() {
            return this.duration > 0 ? this.currentTime / this.duration : 0;
        },

        init() {
            shared.ui = this;
            const saved = this._loadState(STORAGE_KEY, {});
            this.muted = !!saved.muted;
            this.volume = typeof saved.volume === 'number' ? saved.volume : 1;

            if (shared.audio) {
                bind();
                this.trackName = shared.name;
                this.currentSrc = shared.src;
                this.isCustom = shared.custom;
                this.currentTime = shared.audio.currentTime;
                this.duration = shared.audio.duration;
                this.playing = live();
                this.muted = shared.audio.muted;
                this.volume = shared.audio.volume;
                queueMicrotask(emit);
                return;
            }

            this.load(saved.src || this.defaultSrc, saved.custom ? this.defaultName : (saved.name || this.defaultName), false, false);

            if (saved.time && !saved.custom) {
                this.resumeAt(saved.time);
            }

            if (saved.playing && !saved.custom) {
                if (channel) {
                    channel.postMessage('ping');
                    setTimeout(() => {
                        if (!tabClaimed) shared.audio.play().catch(() => {});
                    }, 100);
                } else {
                    shared.audio.play().catch(() => {});
                }
            }

            queueMicrotask(emit);
        },

        destroy() {
            if (shared.ui === this) {
                shared.ui = null;
            }
        },

        persist() {
            if (shared.custom) {
                this._saveState(STORAGE_KEY, { ...this._loadState(STORAGE_KEY, {}), muted: this.muted, volume: this.volume });
                return;
            }
            this._saveState(STORAGE_KEY, {
                src: this.currentSrc,
                name: this.trackName,
                custom: false,
                time: this.currentTime,
                playing: this.playing,
                muted: this.muted,
                volume: this.volume,
            });
        },

        clearResume() {
            if (shared.resumeSeek && shared.audio) {
                shared.audio.removeEventListener('loadedmetadata', shared.resumeSeek);
                shared.resumeSeek = null;
            }
        },

        resumeAt(t) {
            const a = shared.audio;
            this.clearResume();
            if (a.readyState >= 1) {
                a.currentTime = t;
                return;
            }

            const seek = () => {
                a.currentTime = t;
                a.removeEventListener('loadedmetadata', seek);
                shared.resumeSeek = null;
            };

            shared.resumeSeek = seek;
            a.addEventListener('loadedmetadata', seek);
        },

        load(src, name, custom, persist = true) {
            if (!shared.audio) {
                shared.audio = new Audio();
                shared.audio.preload = 'none';
                shared.audio.volume = this.volume;
                shared.audio.muted = this.muted;
                bind();
            }

            this.clearResume();

            if (shared.url && shared.url !== src) {
                URL.revokeObjectURL(shared.url);
            }

            shared.url = custom ? src : null;
            shared.src = src;
            shared.name = name;
            shared.custom = custom;

            if (shared.audio.src !== src) {
                shared.audio.src = src;
                this.currentTime = 0;
                this.duration = 0;
                this._lastPersist = 0;
            }

            this.trackName = name;
            this.currentSrc = src;
            this.isCustom = custom;

            if (persist) {
                this.persist();
            }
        },

        pick() {
            this.libraryOpen = false;
            this.$refs.file.click();
        },

        chooseTrack(t) {
            this.libraryOpen = false;
            if (!this.isCustom && this.currentSrc === t.src) return;

            const wasPlaying = this.playing;
            this.load(t.src, t.name, false);
            if (wasPlaying) {
                shared.audio.play().catch(() => {});
            }
        },

        setVolume(v) {
            this.volume = v < 0 ? 0 : (v > 1 ? 1 : Number(v) || 0);
            if (this.volume > 0) this.muted = false;

            if (shared.audio) {
                shared.audio.volume = this.volume;
                shared.audio.muted = this.muted;
            }

            if (this._volT) clearTimeout(this._volT);
            this._volT = setTimeout(() => this.persist(), 400);
        },

        onFile(e) {
            const file = e.target.files?.[0];
            e.target.value = '';
            if (!file) return;
            const url = URL.createObjectURL(file);
            this.load(url, file.name.replace(/\.[^.]+$/, ''), true);
            shared.audio.play().catch(() => {});
        },

        toggle() {
            if (!shared.audio) return;
            if (shared.audio.paused) {
                shared.audio.play().catch(() => {});
            } else {
                shared.audio.pause();
            }
        },

        toggleMute() {
            this.muted = !this.muted;
            if (shared.audio) {
                shared.audio.muted = this.muted;
            }
            this.persist();
        },

        _getFraction(e, rect) {
            const pos = (e.clientX - rect.left) / rect.width;
            return pos < 0 ? 0 : (pos > 1 ? 1 : pos);
        },

        seek(e, cachedRect = null) {
            const a = shared.audio;
            if (!a || !this.duration) return;

            const targetTime = this.duration * this._getFraction(e, cachedRect || this.$refs.seekTrack.getBoundingClientRect());
            a.currentTime = targetTime;
            this.currentTime = targetTime;
            this._lastPersist = 0;
        },

        onSeekDown(e) {
            if (!shared.audio || !this.duration) return;

            const rect = this.$refs.seekTrack.getBoundingClientRect();
            this._seekRect = { left: rect.left, width: rect.width };
            this.seeking = true;

            const fraction = this._getFraction(e, this._seekRect);
            this.hoverProgress = fraction;

            const targetTime = this.duration * fraction;
            shared.audio.currentTime = targetTime;
            this.currentTime = targetTime;
            this._lastPersist = 0;

            try { e.currentTarget.setPointerCapture(e.pointerId); } catch {}
        },

        onSeekMove(e) {
            if (!this.duration) return;

            const fraction = this._getFraction(e, this._seekRect || this.$refs.seekTrack.getBoundingClientRect());
            this.hoverProgress = fraction;

            if (this.seeking && shared.audio) {
                const targetTime = this.duration * fraction;
                shared.audio.currentTime = targetTime;
                this.currentTime = targetTime;
                this._lastPersist = 0;
            }
        },

        onSeekUp() {
            if (!this.seeking) return;
            this.seeking = false;
            this._seekRect = null;
            this._lastPersist = 0;
            this.persist();
        },

        reset() {
            if (!shared.audio) return;
            shared.audio.pause();
            this.load(this.defaultSrc, this.defaultName, false);
            shared.audio.currentTime = 0;
            this.currentTime = 0;
            this._lastPersist = 0;
            this.persist();
        }
    };
}
