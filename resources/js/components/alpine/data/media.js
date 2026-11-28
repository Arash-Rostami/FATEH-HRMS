import playerMixin from '../mixins/player.js';
import maximizeMixin from '../mixins/maximize.js';
import sidebarRailMixin from '../mixins/sidebarRail.js';
import lastOpenMixin from '../mixins/lastOpen.js';
import persistentStateMixin from '../mixins/persistentState.js';
import highlightMatchMixin from '../mixins/highlightMatch.js';
import clipboardMixin from '../mixins/clipboard.js';
import tableToolsMixin from '../mixins/tableTools.js';
import chatScaleMixin from '../mixins/chatScale.js';
import { normalize } from './memberPicker.js';

const PROGRESS_KEY = 'media_progress';
const RECENT_LIMIT = 5;
const ACCENTS = { p: 'primary', t: 'tertiary', ok: 'success', w: 'warning', sec: 'secondary' };
const accentStyles = new Map();
const inProgress = progress => progress > 0 && progress < 1;

const memoNormalize = () => {
    let last = {}, result;
    return value => value === last ? result : (result = normalize(last = value));
};

export default function media() {
    let observer, controller, unlockTimer, trackHost, trackSequence = 0;
    let clockOffset = 0, hydrated = false, destroyed = false, unlockFailures = 0;
    const unlocked = new Set();
    const searchIndex = new Map();
    const byId = new Map();
    const hitKeys = new Map();
    const owners = new Map();
    const queryKey = memoNormalize();
    const transcriptKey = memoNormalize();
    let loadedTracksKey = null, lastPayload = null, departmentList = [], cueKeys = null, cueKeysFor = null;
    const lookup = (vm, id) => (vm.items, byId.get(id));
    const player = playerMixin();
    const root = Object.defineProperties({}, Object.getOwnPropertyDescriptors(player));

    return Object.defineProperties(root, Object.getOwnPropertyDescriptors({
        ...maximizeMixin('media'),
        ...sidebarRailMixin('media'),
        ...lastOpenMixin('media'),
        ...persistentStateMixin(),
        ...highlightMatchMixin(),
        ...clipboardMixin(),
        ...tableToolsMixin('media'),
        ...chatScaleMixin(),
        items: [],
        libraryNotes: {},
        selectedId: null,
        viewedEpisodeIndex: 0,
        episodePicked: false,
        pendingMoment: null,
        pendingSeek: null,
        mobileDetail: false,
        query: '',
        filter: 'all',
        filtersOpen: false,
        department: '',
        onlySaved: false,
        tab: 'episodes',
        legendTab: 'playback',
        noteBody: '',
        noteBusy: false,
        transcriptQuery: '',
        cues: [],
        chapters: [],
        tracksBusy: false,
        tracksError: '',
        ranks: {},
        rowLimit: 30,
        refreshing: false,
        refreshError: '',

        init() {
            controller = new AbortController();
            observer = new MutationObserver(() => this.readLibrary());
            observer.observe(this.$el, { attributes: true, attributeFilter: ['data-library'] });
            this.bindPlayerKeys('[data-media-root]');
            this.readLibrary();
            this.initMaximize();
            this.initTableTools();
            this.initChatScale();
            window.addEventListener('media:state', () => this.syncTrackContext(), { signal: controller.signal });
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.scheduleUnlock();
            }, { signal: controller.signal });
            this.$watch('tab', () => this.loadTracks());
        },

        readLibrary() {
            const raw = this.$el.dataset.library;
            if (!raw || raw === lastPayload) return;
            this.progressUrl = this.$el.dataset.progressUrl ?? '';
            this.userId = this.$el.dataset.userId ?? '';
            try { this.sync = { ...this.sync, ...JSON.parse(this.$el.dataset.sync || '{}') }; } catch {}
            let payload;
            try { payload = JSON.parse(raw); } catch { return; }
            if (!Array.isArray(payload.items)) return;
            lastPayload = raw;
            const known = this.library.progress ?? {};
            payload.progress = { ...(payload.progress ?? {}) };
            for (const [id, row] of Object.entries(known)) {
                if (row.position > (payload.progress[id]?.position ?? 0)) payload.progress[id] = row;
            }
            clockOffset = Number(payload.serverNow) * 1000 - Date.now();
            this.items = payload.items;
            this.libraryNotes = payload.notes ?? {};
            searchIndex.clear();
            byId.clear();
            hitKeys.clear();
            owners.clear();
            departmentList = [...new Set(payload.items.map(item => item.department).filter(Boolean))];
            for (const [index, item] of this.items.entries()) {
                const plain = payload.items[index];
                byId.set(item.id, item);
                searchIndex.set(item.id, normalize([plain.title, plain.department, plain.unit, ...plain.episodes.map(ep => `${ep.title} ${ep.topic}`)].join(' ')));
                for (const ep of item.episodes) owners.set(ep.id, [item, ep]);
            }
            this.loadLibrary(payload);
            this.pruneLocalProgress();
            if (this.cur?.sid && !this.accessible(this.cur.sid, this.cur.idx)) {
                if (this.playing) this.toggle();
                this.cur = { sid: null, idx: 0 };
                this.playing = false;
            }
            if (!hydrated) {
                hydrated = true;
                const params = new URLSearchParams(location.search);
                const requested = Number(this.$el.dataset.open ?? params.get('open'));
                const remembered = this._readLastOpenId();
                const found = this.items.find(item => item.id === requested || item.episodes.some(ep => ep.id === requested));
                const fallback = byId.get(remembered) ?? this.items[0];
                const item = found ?? byId.get(this.cur?.sid) ?? fallback;
                if (item) {
                    this.choose(item.id, !!found);
                    const idx = found?.episodes.findIndex(ep => ep.id === requested) ?? -1;
                    const seconds = params.has('t') ? Number(params.get('t')) : NaN;
                    if (found && (idx >= 0 || Number.isFinite(seconds) && seconds > 0)) {
                        const index = idx >= 0 ? idx : 0;
                        if (found.episodes[index]?.file_url && !this.isLocked(found.episodes[index])) {
                            this.viewedEpisodeIndex = index;
                            this.episodePicked = true;
                            this.pendingMoment = { sid: found.id, idx: index, seconds: Number.isFinite(seconds) ? Math.max(0, Math.min(seconds, found.episodes[index].duration || seconds)) : this.resumeAt(found.id, index) };
                        }
                    }
                }
            } else if (!byId.has(this.selectedId)) {
                this.selectedId = null;
                this.mobileDetail = false;
            }
            this.scheduleUnlock();
        },

        pruneLocalProgress() {
            if (!owners.size) return;
            const stored = this._loadState(this._k(PROGRESS_KEY), {}) ?? {};
            const kept = Object.entries(stored).filter(([id]) => owners.has(Number(id)));
            if (kept.length !== Object.keys(stored).length) this._saveState(this._k(PROGRESS_KEY), Object.fromEntries(kept));
        },

        get selected() {
            return lookup(this, this.selectedId) ?? null;
        },

        get contextEpisode() {
            const item = this.selected;
            if (!item) return null;
            return item.episodes[this.cur?.sid === item.id ? this.cur.idx : this.viewedEpisodeIndex] ?? null;
        },

        get aboutEpisode() {
            const item = this.selected;
            if (!item || item.type !== 'series') return null;
            const soleEpisode = !item.description_html && item.episodes.length === 1;
            return this.episodePicked || this.cur?.sid === item.id || soleEpisode ? this.contextEpisode : null;
        },

        get currentNotes() {
            return this.libraryNotes[this.contextEpisode?.id] ?? [];
        },

        get departments() {
            this.items;
            return departmentList;
        },

        get visibleItems() {
            const query = queryKey(this.query);
            const { department, onlySaved, filter } = this;
            const store = this.$store.pinned;
            const pinnedRows = [], rows = [];
            for (const item of this.items) {
                if (query && !searchIndex.get(item.id)?.includes(query)) continue;
                if (department && item.department !== department) continue;
                if (onlySaved && !store.isPinned(item.id, 'media')) continue;
                if (!(filter === 'all'
                    || filter === 'series' && item.type === 'series'
                    || filter === 'continue' && inProgress(this.progressOf(item.id))
                    || filter === item.kind)) continue;
                (this.pinned(item) ? pinnedRows : rows).push(item);
            }
            return pinnedRows.length ? pinnedRows.concat(rows) : rows;
        },

        get upNext() {
            const item = this.item();
            const next = item?.type === 'series' ? item.episodes[this.cur.idx + 1] : null;
            return next?.file_url && !this.isLocked(next) ? next : null;
        },

        get recentItems() {
            const touched = this.library.progress ?? {};
            const stamps = Object.entries(this._loadState(this._k(PROGRESS_KEY), {}) ?? {})
                .filter(([id]) => id in touched)
                .sort(([, a], [, b]) => (b.updatedAt ?? 0) - (a.updatedAt ?? 0));
            const picked = new Map();
            for (const [id] of stamps) {
                const [item, ep] = owners.get(Number(id)) ?? [];
                if (item && !this.isLocked(ep) && !picked.has(item.id)) picked.set(item.id, item);
                if (picked.size === RECENT_LIMIT) break;
            }
            return [...picked.values()];
        },

        rankRows() {
            this.ranks = Object.fromEntries(this.visibleItems.map((item, index) => [item.id, index]));
        },

        pinned(item) {
            return item.pinned || this.$store.pinned.isPinned(item.id, 'media-pin');
        },

        item(sid) {
            return lookup(this, sid ?? this.cur.sid) ?? null;
        },

        played(id) {
            return !!this.item(id)?.episodes.some(ep => this.library.progress?.[ep.id]);
        },

        choose(id, detail = true) {
            const item = lookup(this, Number(id));
            if (!item) return;
            this.selectedId = item.id;
            this.viewedEpisodeIndex = 0;
            this.episodePicked = false;
            this.pendingMoment = null;
            this.mobileDetail = detail;
            this._persistLastOpen(item.id);
            this.tab = 'episodes';
            this.noteBody = '';
            this.transcriptQuery = '';
            this.clearTracks();
        },

        episodeHit(id) {
            const query = queryKey(this.query);
            if (!query) return null;
            const item = lookup(this, Number(id));
            if (item?.type !== 'series') return null;
            let keys = hitKeys.get(item.id);
            if (!keys) hitKeys.set(item.id, keys = { title: normalize(item.title), eps: item.episodes.map(ep => normalize(`${ep.title} ${ep.topic}`)) });
            if (keys.title.includes(query)) return null;
            const index = keys.eps.findIndex(key => key.includes(query));
            return index < 0 ? null : { index, title: item.episodes[index].title };
        },

        chooseEpisode(id) {
            const hit = this.episodeHit(id);
            this.choose(id);
            if (hit) {
                this.viewedEpisodeIndex = hit.index;
                this.episodePicked = true;
            }
        },

        undoSwitch() {
            const back = this.undo;
            player.undoSwitch.call(this);
            if (!back) return;
            if (this.selectedId !== back.sid) this.choose(back.sid, false);
            this.viewedEpisodeIndex = back.idx;
            this.episodePicked = true;
        },

        backToList() { this.mobileDetail = false; },
        setFilter(value) { this.filter = value; },
        setTab(value) { this.tab = value; },
        showEpisodes() { this.setTab('episodes'); },
        showTranscript() { this.setTab('transcript'); },
        showNotes() { this.setTab('notes'); },
        showMaterials() { this.setTab('materials'); },
        setLegendTab(value) { this.legendTab = value; },
        clearSearch() { this.query = ''; },
        showLegend() { this.$dispatch('open-modal', { name: 'media-legend' }); },
        showBadgeLegend() { this.$dispatch('open-modal', { name: 'media-badge-legend' }); },

        isLocked(episode) {
            return !!episode?.unlock_at && (episode.unlock_at * 1000 > Date.now() + clockOffset || !episode.file_url);
        },

        accessible(sid, index) {
            const episode = lookup(this, sid)?.episodes[index];
            return !!episode?.file_url && !this.isLocked(episode);
        },

        scheduleUnlock() {
            if (destroyed || this.refreshing) return;
            clearTimeout(unlockTimer);
            const now = Date.now() + clockOffset;
            const waiting = this.items.flatMap(item => item.episodes).filter(ep => ep.unlock_at && !ep.file_url && !unlocked.has(ep.unlock_at));
            const due = waiting.filter(ep => ep.unlock_at * 1000 <= now);
            if (due.length) {
                this.refreshLibrary(due.map(ep => ep.unlock_at));
                return;
            }
            const next = Math.min(...waiting.map(ep => ep.unlock_at * 1000));
            if (Number.isFinite(next)) unlockTimer = setTimeout(() => this.scheduleUnlock(), Math.min(2147483647, Math.max(1, next - now)));
        },

        async refreshLibrary(stamps = []) {
            if (this.refreshing || destroyed) return;
            this.refreshing = true;
            this.refreshError = '';
            let failed = false;
            try {
                await this.$wire.$refresh();
                if (destroyed) return;
                for (const stamp of stamps) unlocked.add(stamp);
                unlockFailures = 0;
            } catch {
                failed = true;
                this.refreshError = 'کتابخانه به‌روز نشد. دوباره تلاش کنید.';
            } finally {
                this.refreshing = false;
            }
            if (destroyed) return;
            if (failed && stamps.length) {
                clearTimeout(unlockTimer);
                unlockTimer = setTimeout(() => this.scheduleUnlock(), Math.min(240000, 30000 * 2 ** unlockFailures++));
            } else if (!failed) {
                this.readLibrary();
                this.scheduleUnlock();
            }
        },

        startEpisode(index) {
            const item = this.selected;
            const episode = item?.episodes[index];
            if (episode && this.isLocked(episode)) return this.toast(`این جلسه در ${episode.unlock_label} منتشر می‌شود`);
            if (!episode?.file_url) return;
            this.clearTracks();
            this.viewedEpisodeIndex = index;
            this.episodePicked = true;
            const moment = this.pendingMoment?.sid === item.id && this.pendingMoment.idx === index ? this.pendingMoment : null;
            this.pendingMoment = null;
            if (this.cur?.sid === item.id && this.cur.idx === index && !this.error) {
                if (moment) this.seek(moment.seconds);
                this.toggle();
            } else {
                this.pendingSeek = moment;
                this.playEpisode(item.id, index);
                this.pendingSeek = null;
            }
        },

        resumeAt(sid, index) {
            if (this.pendingSeek?.sid === sid && this.pendingSeek.idx === index) return this.pendingSeek.seconds;
            return player.resumeAt.call(this, sid, index);
        },

        contextTime() {
            return this.cur?.sid === this.selectedId ? this.t : this.pendingMoment?.seconds ?? 0;
        },

        resumeIndex() {
            return this.selected?.episodes.findIndex((ep, idx) => ep.file_url && !this.isLocked(ep) && this.episodeState(this.selectedId, idx) !== 'done') ?? -1;
        },

        heroPlay() {
            if (this.cur?.sid === this.selectedId && this.playing) return this.toggle();
            if (this.cur?.sid === this.selectedId && this.currentEpisode?.file_url && !this.pendingMoment && !this.error) return this.toggle();
            let index = this.pendingMoment?.sid === this.selectedId ? this.pendingMoment.idx : this.resumeIndex();
            if (index < 0) index = this.selected?.episodes.findIndex(ep => ep.file_url && !this.isLocked(ep)) ?? -1;
            if (index >= 0) this.startEpisode(index);
        },

        heroLabel() {
            if (this.cur?.sid === this.selectedId && this.playing) return 'توقف';
            const index = this.resumeIndex();
            return index >= 0 && this.progressOf(this.selectedId) > 0 ? `ادامه از جلسهٔ ${this.number(index + 1)} · ${this.time(this.resumeAt(this.selectedId, index))}` : 'پخش';
        },

        doneCount() {
            return this.selected?.episodes.filter((ep, idx) => this.episodeState(this.selectedId, idx) === 'done').length ?? 0;
        },

        number(value) { return this.fa(Number(value ?? 0)); },

        time(seconds) { return this.faTime(Number(seconds)); },

        accentStyle(accent) {
            let style = accentStyles.get(accent);
            if (!style) {
                const color = `var(--md-sys-color-${ACCENTS[accent] ?? 'primary'})`;
                accentStyles.set(accent, style = { background: `linear-gradient(145deg,color-mix(in srgb,${color} 58%,var(--md-sys-color-surface)),color-mix(in srgb,${color} 22%,var(--md-sys-color-surface)))` });
            }
            return style;
        },

        percent(fraction) {
            return Math.round(Math.max(0, Math.min(1, Number(fraction) || 0)) * 100);
        },

        glyph(sid, index) {
            if (this.isLocked(lookup(this, sid)?.episodes[index])) return 'lock';
            if (this.playing && this.cur.sid === sid && this.cur.idx === index) return 'eq';
            return this.episodeState(sid, index) === 'done' ? 'done' : 'num';
        },

        activeCue(cue) {
            return this.cur?.sid === this.selectedId && this.t >= cue.start && this.t < cue.end;
        },

        seekContext(seconds) {
            const episode = this.contextEpisode;
            if (!episode?.file_url || this.isLocked(episode)) return;
            if (this.cur?.sid !== this.selectedId) {
                this.pendingMoment = { sid: this.selectedId, idx: this.viewedEpisodeIndex, seconds: Number(seconds) };
                this.startEpisode(this.viewedEpisodeIndex);
                return;
            }
            this.seek(Number(seconds));
        },

        episodeProgress(sid, index) {
            const episode = lookup(this, sid)?.episodes[index];
            const progress = this.library.progress?.[episode?.id];
            return episode?.duration ? (progress?.completed ? 1 : Math.min(1, (progress?.position ?? 0) / episode.duration)) : 0;
        },

        copyMoment() {
            if (!this.contextEpisode) return;
            const url = new URL(location.href);
            url.search = '';
            url.searchParams.set('tab', 'media');
            url.searchParams.set('open', this.contextEpisode.id);
            url.searchParams.set('t', Math.floor(this.contextTime()));
            this.copyText(url.href, 'پیوند این لحظه کپی شد.');
        },

        copyNotes() {
            if (!this.currentNotes.length) return;
            this.copyText(this.currentNotes.map(note => `${this.time(note.position)} — ${note.body}`).join('\n'), 'یادداشت‌ها کپی شد.');
        },

        async noteRequest(action, data) {
            const response = await fetch(this.$el.dataset.notesUrl, {
                method: 'POST', credentials: 'same-origin', signal: controller?.signal,
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ _token: document.querySelector('meta[name="csrf-token"]')?.content, action, ...data }),
            });
            if (!response.ok) {
                const failure = new Error(String(response.status));
                failure.reason = (await response.json().catch(() => null))?.reason;
                throw failure;
            }
            return response.status === 204 ? null : response.json();
        },

        noteFailed(error, fallback) {
            if (error.name === 'AbortError') return;
            this.toast({
                401: 'برای ادامه، دوباره وارد سامانه شوید.',
                419: 'صفحه را دوباره بارگذاری کنید و تلاش کنید.',
                422: error.reason === 'cap' ? 'تعداد یادداشت‌های این جلسه به حد مجاز رسیده است.' : 'متن یادداشت را بررسی کنید.',
            }[error.message] ?? fallback, 'error');
        },

        async addNote() {
            const episode = this.contextEpisode;
            const body = this.noteBody.trim();
            if (this.noteBusy || !episode?.file_url || this.isLocked(episode) || !body || [...body].length > 500) return;
            this.noteBusy = true;
            const position = Math.floor(this.contextTime());
            try {
                const note = await this.noteRequest('add', { media_id: episode.id, position, body });
                if (destroyed) return;
                this.libraryNotes = { ...this.libraryNotes, [episode.id]: [...(this.libraryNotes[episode.id] ?? []), note].sort((a, b) => a.position - b.position) };
                if (this.contextEpisode?.id === episode.id && this.noteBody.trim() === body) this.noteBody = '';
            } catch (error) {
                this.noteFailed(error, 'یادداشت ذخیره نشد. دوباره تلاش کنید.');
            } finally { this.noteBusy = false; }
        },

        async deleteNote(id) {
            if (this.noteBusy || !this.currentNotes.some(note => note.id === Number(id))) return;
            const episodeId = this.contextEpisode.id;
            this.noteBusy = true;
            try {
                await this.noteRequest('delete', { id });
                if (!destroyed) this.libraryNotes = { ...this.libraryNotes, [episodeId]: (this.libraryNotes[episodeId] ?? []).filter(note => note.id !== Number(id)) };
            } catch (error) {
                this.noteFailed(error, 'یادداشت حذف نشد. دوباره تلاش کنید.');
            } finally { this.noteBusy = false; }
        },

        _mountTracks() {},

        clearTracks() {
            trackSequence++;
            trackHost?.removeAttribute('src');
            trackHost?.load();
            trackHost?.remove();
            trackHost = null;
            loadedTracksKey = null;
            cueKeys = cueKeysFor = null;
            this.cues = [];
            this.chapters = [];
            this.tracksError = '';
            this.tracksBusy = false;
        },

        syncTrackContext() {
            if (trackHost) {
                if (Number(trackHost.dataset.episode) === this.contextEpisode?.id) return;
                this.clearTracks();
            }
            this.loadTracks();
        },

        loadTracks() {
            if (this.tab !== 'transcript' || trackHost) return;
            const episode = this.contextEpisode;
            if (!episode || this.isLocked(episode) || !episode.transcript_url && !episode.chapters_url) return;
            const key = `${episode.id}:${episode.transcript_url}:${episode.chapters_url}`;
            if (loadedTracksKey === key) return;
            if (!this.$refs.mediaTracks) return;
            loadedTracksKey = key;
            const sequence = ++trackSequence;
            this.tracksBusy = true;
            this.tracksError = '';
            trackHost = document.createElement('video');
            trackHost.dataset.episode = episode.id;
            trackHost.hidden = true;
            trackHost.crossOrigin = 'anonymous';
            this.$refs.mediaTracks.appendChild(trackHost);
            const urls = [['subtitles', episode.transcript_url], ['chapters', episode.chapters_url]].filter(([, url]) => url);
            let remaining = urls.length;
            for (const [kind, url] of urls) {
                const track = this._track(kind, url);
                track.label = kind === 'chapters' ? 'بخش‌های جلسه' : 'متن جلسه';
                track.addEventListener('load', () => {
                    if (destroyed || sequence !== trackSequence) return;
                    const cues = Array.from(track.track.cues ?? []).map(cue => ({ start: cue.startTime, end: cue.endTime, text: cue.text }));
                    if (kind === 'chapters') this.chapters = cues;
                    else this.cues = cues;
                    this.tracksBusy = --remaining > 0;
                }, { once: true });
                track.addEventListener('error', () => {
                    if (destroyed || sequence !== trackSequence) return;
                    this.tracksError = 'متن یا بخش‌های جلسه بارگذاری نشد. دوباره تلاش کنید.';
                    this.tracksBusy = --remaining > 0;
                }, { once: true });
                trackHost.appendChild(track);
                track.track.mode = 'hidden';
            }
        },

        get visibleCues() {
            const cues = this.cues;
            const query = transcriptKey(this.transcriptQuery);
            if (!query) return cues;
            if (cueKeysFor !== cues) {
                cueKeys = cues.map(cue => normalize(cue.text));
                cueKeysFor = cues;
            }
            return cues.filter((_, index) => cueKeys[index].includes(query));
        },

        destroy() {
            destroyed = true;
            observer?.disconnect();
            controller?.abort();
            clearTimeout(unlockTimer);
            this.clearTracks();
            this.destroyPlayer();
            this.destroyTableTools();
            if (this.maxOn) this.applyMaximize(false);
        },
    }));
}
