import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import media from '../media.js';

const library = {
    serverNow: Date.now() / 1000,
    items: [{ id: 1, title: 'رسانه', kind: 'audio', type: 'single', department: 'فناوری', unit: 'دانش', total_duration: 300, materials: [], episodes: [{ id: 1, title: 'جلسه', topic: '', duration: 300, file_url: '/a.mp3', unlock_at: null }] }],
    progress: {},
    notes: {},
};

function component() {
    const root = media();
    root.$el = document.createElement('div');
    root.$el.dataset.library = JSON.stringify(library);
    root.$store = { pinned: { isPinned: () => false } };
    root.$dispatch = vi.fn();
    root.$watch = vi.fn();
    root.$wire = { $refresh: vi.fn() };
    root.readLibrary();
    return root;
}

describe('media composed with the real player mixin', () => {
    beforeEach(() => {
        localStorage.clear();
        history.replaceState({}, '', '/media');
        document.head.innerHTML = '<meta name="csrf-token" content="token-1">';
    });
    afterEach(() => {
        vi.restoreAllMocks();
        document.head.innerHTML = '';
    });

    it('keeps the mixin getters live after composition', () => {
        const root = component();
        expect(typeof Object.getOwnPropertyDescriptor(root, 'progressRatio').get).toBe('function');
        root.t = 30;
        root.duration = 60;
        expect(root.progressRatio).toBe(0.5);
    });

    it('reads the csrf token at send time, not at composition time', () => {
        const root = component();
        const fetchMock = vi.fn().mockResolvedValue({ ok: true });
        vi.stubGlobal('fetch', fetchMock);
        document.head.innerHTML = '<meta name="csrf-token" content="token-2">';
        root._sendProgress('/media/progress', { items: [{ id: 1, position: 10, last_position: 10 }] }, { keepalive: true });
        expect(JSON.parse(fetchMock.mock.calls[0][1].body)._token).toBe('token-2');
        vi.unstubAllGlobals();
    });

    it('updates the ring, the done count and the continue filter from the patched library progress', () => {
        const root = component();
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
});
