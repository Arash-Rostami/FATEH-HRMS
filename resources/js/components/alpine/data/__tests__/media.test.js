import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('../../mixins/player.js', () => ({
    default: () => ({
        cur: { sid: null, idx: 0 }, t: 0, playing: false,
        loadLibrary: vi.fn(), selectItem: vi.fn(), playEpisode: vi.fn(),
        toggle: vi.fn(), seek: vi.fn(), destroyPlayer: vi.fn(),
        fa: value => Number(value).toLocaleString('fa-IR'), faTime: value => String(value),
        progressOf: () => 0, episodeState: () => 'new', resumeAt: () => 0,
    }),
}));

import media from '../media.js';

const episode = (id, values = {}) => ({ id, title: 'جلسه', topic: '', duration: 300, file_url: '/storage/example.mp3', unlock_at: null, ...values });
const item = (id, values = {}) => ({ id, title: `رسانه ${id}`, kind: 'audio', type: 'single', department: 'فناوری', episodes: [episode(id)], materials: [], total_duration: 300, ...values });

function component(items) {
    const root = media();
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

    it('rejects empty and oversized notes without a request', async () => {
        const root = component([item(1)]);
        root.noteRequest = vi.fn();
        root.noteBody = '   ';
        await root.addNote();
        root.noteBody = 'الف'.repeat(501);
        await root.addNote();
        expect(root.noteRequest).not.toHaveBeenCalled();
    });
});
