import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const mixin = vi.hoisted(() => ({ real: false }));

vi.mock('../../mixins/player.js', async importOriginal => {
    const actual = await importOriginal();
    return {
        default: () => mixin.real ? actual.default() : ({
            library: { items: [], progress: {}, notes: {} },
            cur: { sid: null, idx: 0 }, t: 0, playing: false,
            loadLibrary: vi.fn(), selectItem: vi.fn(), playEpisode: vi.fn(),
            toggle: vi.fn(), seek: vi.fn(), destroyPlayer: vi.fn(), toast: vi.fn(),
            faTime: value => String(value),
            progressOf: () => 0, episodeState: () => 'new', resumeAt: () => 0,
        }),
    };
});

import media from '../media.js';

const episode = (id, values = {}) => ({ id, title: 'جلسه', topic: '', duration: 300, file_url: '/storage/example.mp3', unlock_at: null, ...values });
const item = (id, values = {}) => ({ id, title: `رسانه ${id}`, kind: 'audio', type: 'single', department: 'فناوری', episodes: [episode(id)], materials: [], total_duration: 300, ...values });

function component(items, factory = media) {
    const root = factory();
    root.$el = document.createElement('div');
    root.$el.dataset.library = JSON.stringify({ serverNow: Date.now() / 1000, items, notes: {}, progress: {} });
    root.$store = { pinned: { isPinned: () => false } };
    root.$dispatch = vi.fn();
    root.$nextTick = callback => callback();
    root.$watch = vi.fn();
    root.$wire = { $refresh: vi.fn().mockResolvedValue() };
    root.readLibrary();
    return root;
}

describe('media library', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        localStorage.clear();
        history.replaceState({}, '', '/media');
    });
    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it('reuses Persian normalization for search without requesting the server', () => {
        const root = component([item(1, { title: 'فناوری كاربردي' }), item(2, { title: 'دیگر' })]);
        root.query = 'کاربردی';
        expect(root.visibleItems.map(row => row.id)).toEqual([1]);
        expect(root.$wire.$refresh).not.toHaveBeenCalled();
    });

    it('filters and sorts pinned first while preserving personal bookmarks', () => {
        const root = component([item(1), item(2, { pinned: true }), item(3, { kind: 'video' })]);
        root.$store.pinned.isPinned = id => id === 3;
        expect(root.visibleItems.map(row => row.id)).toEqual([2, 3, 1]);
        root.onlySaved = true;
        expect(root.visibleItems.map(row => row.id)).toEqual([3]);
        root.onlySaved = false;
        root.filter = 'audio';
        expect(root.visibleItems.map(row => row.id)).toEqual([2, 1]);
    });

    it('keeps player descriptors intact and selects locally', () => {
        const root = component([item(1), item(2)]);
        root.choose(2);
        expect(root.selectedId).toBe(2);
        expect(root.selectItem).not.toHaveBeenCalled();
        expect(root.$wire.$refresh).not.toHaveBeenCalled();
    });

    it('opens an episode moment without autoplay', () => {
        history.replaceState({}, '', '/media?open=12&t=45');
        const root = component([item(1, { type: 'series', episodes: [episode(11), episode(12)] })]);
        expect(root.selectedId).toBe(1);
        expect(root.pendingMoment).toEqual({ sid: 1, idx: 1, seconds: 45 });
        expect(root.contextEpisode.id).toBe(12);
        expect(root.playEpisode).not.toHaveBeenCalled();
    });

    it('does not play locked files or invent URLs at unlock', async () => {
        const unlock = Math.floor(Date.now() / 1000) + 10;
        const root = component([item(1, { episodes: [episode(1, { file_url: null, unlock_at: unlock })] })]);
        root.startEpisode(0);
        expect(root.playEpisode).not.toHaveBeenCalled();
        await vi.advanceTimersByTimeAsync(10001);
        expect(root.$wire.$refresh).toHaveBeenCalledTimes(1);
        root.scheduleUnlock();
        expect(root.$wire.$refresh).toHaveBeenCalledTimes(1);
        expect(root.isLocked(root.items[0].episodes[0])).toBe(true);
    });

    it('continues partially watched items only', () => {
        const root = component([item(1), item(2), item(3)]);
        root.progressOf = id => ({ 1: 0, 2: 0.5, 3: 1 })[id];
        root.filter = 'continue';
        expect(root.visibleItems.map(row => row.id)).toEqual([2]);
    });

    it('copies a real episode moment using the clipboard mixin', () => {
        const root = component([item(1)]);
        root.cur = { sid: 1, idx: 0 };
        root.t = 42.9;
        root.copyText = vi.fn();
        root.copyMoment();
        expect(root.copyText.mock.calls[0][0]).toContain('open=1&t=42');
    });

    it('captures note ownership context across selection changes', async () => {
        const root = component([item(1), item(2)]);
        let finish;
        root.noteRequest = vi.fn(() => new Promise(resolve => { finish = resolve; }));
        root.noteBody = 'یادداشت جلسهٔ اول';
        const pending = root.addNote();
        root.choose(2);
        root.noteBody = 'متن جلسهٔ دوم';
        finish({ id: 7, position: 0, body: 'یادداشت جلسهٔ اول' });
        await pending;
        expect(root.libraryNotes[1][0].id).toBe(7);
        expect(root.libraryNotes[2]).toBeUndefined();
        expect(root.noteBody).toBe('متن جلسهٔ دوم');
    });

    it('reuses the player resume hook for a moment link on first play', () => {
        history.replaceState({}, '', '/media?open=1&t=45');
        const root = component([item(1)]);
        root.playEpisode = vi.fn((sid, index) => {
            expect(root.resumeAt(sid, index)).toBe(45);
        });
        root.heroPlay();
        expect(root.playEpisode).toHaveBeenCalledWith(1, 0);
        expect(root.pendingSeek).toBeNull();
    });

    it('releases observers, timers and the player on teardown', () => {
        const root = component([item(1)]);
        root.initMaximize = vi.fn();
        root.bindPlayerKeys = vi.fn();
        root.init();
        root.destroy();
        expect(root.destroyPlayer).toHaveBeenCalledOnce();
    });

    it('formats digits itself', () => {
        expect(component([item(1)]).number(12)).toBe('۱۲');
    });

    it('toasts the unlock date for a locked lesson instead of failing silently', () => {
        const unlock = Math.floor(Date.now() / 1000) + 100;
        const root = component([item(1, { episodes: [episode(1, { file_url: null, unlock_at: unlock, unlock_label: '۷ آذر ۱۴۰۵' })] })]);
        root.startEpisode(0);
        expect(root.toast).toHaveBeenCalledWith('این جلسه در ۷ آذر ۱۴۰۵ منتشر می‌شود');
        expect(root.playEpisode).not.toHaveBeenCalled();
    });

    it('shows distinct note failures for 401, 419 and 422', async () => {
        const root = component([item(1)]);
        root.noteBody = 'متن';
        const messages = [];
        for (const [status, reason] of [['401'], ['419'], ['422'], ['422', 'cap'], ['500']]) {
            root.noteRequest = vi.fn().mockRejectedValue(Object.assign(new Error(status), { reason }));
            await root.addNote();
            messages.push(root.toast.mock.calls.at(-1)[0]);
        }
        expect(new Set(messages).size).toBe(5);
        expect(messages[3]).toBe('به سقف یادداشت‌های این جلسه رسیده‌اید.');
        expect(root.toast).toHaveBeenLastCalledWith(expect.any(String), 'error');
    });

    it('reads live progress only from the reactive library and keeps the higher local value on refresh', () => {
        const root = component([item(1)]);
        root.library.progress = { 1: { position: 150, last_position: 150, completed: false } };
        expect(root.episodeProgress(1, 0)).toBe(0.5);
        const payload = { serverNow: Date.now() / 1000, items: [item(1)], notes: {}, progress: { 1: { position: 60, last_position: 60, completed: false } } };
        root.$el.dataset.library = JSON.stringify(payload);
        root.readLibrary();
        expect(root.loadLibrary.mock.calls.at(-1)[0].progress[1].position).toBe(150);
    });

    it('derives row order and lesson glyphs from state', () => {
        const root = component([item(1), item(2, { pinned: true })]);
        root.rankRows();
        expect(root.ranks).toEqual({ 2: 0, 1: 1 });
        root.query = 'رسانه 1';
        root.rankRows();
        expect(root.ranks).toEqual({ 1: 0 });
        root.cur = { sid: 1, idx: 0 };
        root.playing = true;
        expect(root.glyph(1, 0)).toBe('eq');
        root.playing = false;
        root.episodeState = () => 'done';
        expect(root.glyph(1, 0)).toBe('done');
    });

    it('marks the cue under the playhead of the open item only', () => {
        const root = component([item(1)]);
        root.cur = { sid: 1, idx: 0 };
        root.t = 12;
        expect(root.activeCue({ start: 10, end: 15 })).toBe(true);
        expect(root.activeCue({ start: 15, end: 20 })).toBe(false);
        root.cur = { sid: 2, idx: 0 };
        expect(root.activeCue({ start: 10, end: 15 })).toBe(false);
    });

    it('includes the resume time in the continue label', () => {
        const root = component([item(1, { type: 'series', episodes: [episode(11), episode(12)] })]);
        root.progressOf = () => 0.4;
        root.resumeAt = () => 75;
        expect(root.heroLabel()).toBe('ادامه از جلسهٔ ۱ · 75');
    });

    it('rejects empty and oversized notes without a request', async () => {
        const root = component([item(1)]);
        root.noteRequest = vi.fn();
        root.noteBody = '   ';
        await root.addNote();
        root.noteBody = 'الف'.repeat(501);
        await root.addNote();
        expect(root.noteRequest).not.toHaveBeenCalled();
    });

    it('marks an unlock handled only after a successful refresh and backs off failed ones', async () => {
        const unlock = Math.floor(Date.now() / 1000) + 10;
        const root = component([item(1, { episodes: [episode(1, { file_url: null, unlock_at: unlock })] })]);
        root.$wire.$refresh.mockRejectedValueOnce(new Error('offline')).mockRejectedValueOnce(new Error('offline'));
        await vi.advanceTimersByTimeAsync(10001);
        expect(root.$wire.$refresh).toHaveBeenCalledTimes(1);
        await vi.advanceTimersByTimeAsync(28000);
        expect(root.$wire.$refresh).toHaveBeenCalledTimes(1);
        await vi.advanceTimersByTimeAsync(3000);
        expect(root.$wire.$refresh).toHaveBeenCalledTimes(2);
        await vi.advanceTimersByTimeAsync(57000);
        expect(root.$wire.$refresh).toHaveBeenCalledTimes(2);
        await vi.advanceTimersByTimeAsync(3000);
        expect(root.$wire.$refresh).toHaveBeenCalledTimes(3);
        await vi.advanceTimersByTimeAsync(600000);
        expect(root.$wire.$refresh).toHaveBeenCalledTimes(3);
    });

    it('stops retrying failed unlocks after teardown', async () => {
        const unlock = Math.floor(Date.now() / 1000) + 10;
        const root = component([item(1, { episodes: [episode(1, { file_url: null, unlock_at: unlock })] })]);
        root.$wire.$refresh.mockRejectedValue(new Error('offline'));
        await vi.advanceTimersByTimeAsync(10001);
        root.destroy();
        await vi.advanceTimersByTimeAsync(600000);
        expect(root.$wire.$refresh).toHaveBeenCalledTimes(1);
    });

    it('maximizes through the shared mixin without touching the focus store', () => {
        const root = component([item(1)]);
        let writes = 0;
        root.$store.focus = new Proxy({ active: false }, { set(target, key, value) { writes++; target[key] = value; return true; } });
        root.toggleMaximize();
        expect(root.max).toBe(true);
        root.toggleMaximize();
        root.destroy();
        expect(writes).toBe(0);
        expect(root.toggleTheatre).toBeUndefined();
    });
});

describe('media composed with the real player mixin', () => {
    let build, playing;

    beforeEach(async () => {
        mixin.real = true;
        vi.resetModules();
        ({ default: build } = await import('../media.js'));
        playing = false;
        localStorage.clear();
        history.replaceState({}, '', '/media');
        document.head.innerHTML = '<meta name="csrf-token" content="token-1">';
        vi.spyOn(HTMLMediaElement.prototype, 'paused', 'get').mockImplementation(() => !playing);
        vi.spyOn(HTMLMediaElement.prototype, 'canPlayType').mockReturnValue('maybe');
        vi.spyOn(HTMLMediaElement.prototype, 'load').mockImplementation(() => {});
        vi.spyOn(HTMLMediaElement.prototype, 'play').mockImplementation(() => { playing = true; return Promise.resolve(); });
        vi.spyOn(HTMLMediaElement.prototype, 'pause').mockImplementation(() => { playing = false; });
    });
    afterEach(() => {
        mixin.real = false;
        vi.restoreAllMocks();
        document.head.innerHTML = '';
    });

    const start = async items => {
        const old = component(items, build);
        old.playEpisode(1, 0);
        await Promise.resolve();
        old.destroy();
        return old;
    };

    it('keeps the mixin getters live after composition', () => {
        const root = component([item(1)], build);
        expect(typeof Object.getOwnPropertyDescriptor(root, 'progressRatio').get).toBe('function');
        root.t = 30;
        root.duration = 60;
        expect(root.progressRatio).toBe(0.5);
    });

    it('reads the csrf token at send time, not at composition time', () => {
        const root = component([item(1)], build);
        const fetchMock = vi.fn().mockResolvedValue({ ok: true });
        vi.stubGlobal('fetch', fetchMock);
        document.head.innerHTML = '<meta name="csrf-token" content="token-2">';
        root._sendProgress('/media/progress', { items: [{ id: 1, position: 10, last_position: 10 }] }, false);
        expect(JSON.parse(fetchMock.mock.calls[0][1].body)._token).toBe('token-2');
        vi.unstubAllGlobals();
    });

    it('updates the ring, the done count and the continue filter from the patched library progress', () => {
        const root = component([item(1)], build);
        expect(root.progressOf(1)).toBe(0);
        root._markProgress(1, 150, 150, false);
        expect(root.progressOf(1)).toBe(0.5);
        expect(root.episodeProgress(1, 0)).toBe(0.5);
        root.filter = 'continue';
        expect(root.visibleItems.map(row => row.id)).toEqual([1]);
        root._markProgress(1, 300, 300, true);
        expect(root.doneCount()).toBe(1);
        expect(root.episodeState(1, 0)).toBe('done');
        expect(root.visibleItems).toEqual([]);
    });

    it('re-adopts audio that is still playing when a new component mounts', async () => {
        await start([item(1)]);
        const fresh = component([item(1)], build);
        expect(fresh.cur).toEqual({ sid: 1, idx: 0 });
        expect(fresh.playing).toBe(true);
        expect(fresh.selectedId).toBe(1);
        fresh.toggle();
        expect(playing).toBe(false);
    });

    it('stops a playing episode that a library refresh makes inaccessible', async () => {
        await start([item(1)]);
        const fresh = component([item(1)], build);
        expect(fresh.playing).toBe(true);
        fresh.$el.dataset.library = JSON.stringify({ serverNow: Date.now() / 1000, items: [item(2)], notes: {}, progress: {} });
        fresh.readLibrary();
        expect(fresh.cur.sid).toBeNull();
        expect(playing).toBe(false);
    });

    it('does not restore a locked episode', async () => {
        await start([item(1)]);
        const locked = item(1, { episodes: [episode(1, { file_url: null, unlock_at: Math.floor(Date.now() / 1000) + 3600 })] });
        const fresh = component([locked], build);
        expect(fresh.cur.sid).toBeNull();
        expect(fresh.playing).toBe(false);
        expect(playing).toBe(false);
    });

    it('does not restore an episode that is no longer visible', async () => {
        await start([item(1)]);
        const fresh = component([item(2)], build);
        expect(fresh.cur.sid).toBeNull();
        expect(fresh.playing).toBe(false);
        expect(playing).toBe(false);
    });

    it('follows the current episode, not the series kind, for the stage and the element', () => {
        const series = item(1, { type: 'series', kind: 'video', episodes: [episode(11, { mime: 'video/mp4' }), episode(12, { mime: 'audio/mpeg' })] });
        const root = component([series], build);
        root.cur = { sid: 1, idx: 0 };
        expect(root.isVideo).toBe(true);
        root.cur = { sid: 1, idx: 1 };
        expect(root.isVideo).toBe(false);
        series.kind = 'audio';
        root.library = { ...root.library, items: [series] };
        root.cur = { sid: 1, idx: 0 };
        expect(root.isVideo).toBe(true);
    });
});
