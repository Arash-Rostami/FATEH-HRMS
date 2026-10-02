import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import gallery from '../gallery.js'
import { installWireProbe } from '../../mixins/__tests__/_wireMock.js'

function makeItem(id, right) {
    const el = document.createElement('div');
    el.dataset.photoId = id;
    el.getBoundingClientRect = () => ({ right });
    return el;
}

function makeVideoEl() {
    return { tagName: 'VIDEO', currentTime: 0, play: vi.fn(() => Promise.resolve()), pause: vi.fn() };
}

describe('gallery.updateActiveItem — closest-to-reference-point selection', () => {
    test('picks the item whose right edge is closest to the 10%-inset reference point', () => {
        const g = gallery();
        const container = document.createElement('div');
        const item1 = makeItem('1', 80);
        const item2 = makeItem('2', 92);
        const item3 = makeItem('3', 120);
        container.append(item1, item2, item3);
        const viewport = document.createElement('div');
        viewport.getBoundingClientRect = () => ({ right: 100, width: 100 });
        g.$refs = { galleryContainer: container, timeline: viewport };

        g.updateActiveItem();

        expect(g.activeId).toBe('2');
    });

    test('is a no-op when the galleryContainer or timeline ref is missing', () => {
        const g = gallery();
        g.$refs = {};
        expect(() => g.updateActiveItem()).not.toThrow();
        expect(g.activeId).toBeNull();
    });
});

describe('gallery.scrollNext / scrollPrev', () => {
    test('scrollNext scrolls left by the timeline\'s own width', () => {
        const g = gallery();
        const timeline = { offsetWidth: 300, scrollBy: vi.fn() };
        g.$refs = { timeline };
        g.scrollNext();
        expect(timeline.scrollBy).toHaveBeenCalledWith({ left: -300, behavior: 'smooth' });
    });

    test('scrollPrev scrolls right by the timeline\'s own width', () => {
        const g = gallery();
        const timeline = { offsetWidth: 300, scrollBy: vi.fn() };
        g.$refs = { timeline };
        g.scrollPrev();
        expect(timeline.scrollBy).toHaveBeenCalledWith({ left: 300, behavior: 'smooth' });
    });

    test('both are no-ops when the timeline ref is absent', () => {
        const g = gallery();
        g.$refs = {};
        expect(() => g.scrollNext()).not.toThrow();
        expect(() => g.scrollPrev()).not.toThrow();
    });
});

describe('gallery.formatDuration', () => {
    test('formats seconds as m:ss, zero-padding single-digit seconds', () => {
        const g = gallery();
        expect(g.formatDuration(65)).toBe('1:05');
        expect(g.formatDuration(59)).toBe('0:59');
        expect(g.formatDuration(125)).toBe('2:05');
    });

    test('returns an empty string for falsy or non-finite input', () => {
        const g = gallery();
        expect(g.formatDuration(0)).toBe('');
        expect(g.formatDuration(NaN)).toBe('');
        expect(g.formatDuration(Infinity)).toBe('');
    });
});

describe('gallery.galleryPreviewPlay / galleryPreviewStop', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    test('resets currentTime, plays, and auto-pauses after 1500ms', () => {
        const g = gallery();
        const el = makeVideoEl();
        g.galleryPreviewPlay(el);
        expect(el.currentTime).toBe(0);
        expect(el.play).toHaveBeenCalled();

        vi.advanceTimersByTime(1500);
        expect(el.pause).toHaveBeenCalled();
    });

    test('ignores a null element or a non-video element', () => {
        const g = gallery();
        expect(() => g.galleryPreviewPlay(null)).not.toThrow();
        g.galleryPreviewPlay({ tagName: 'DIV' });
        expect(g.previewTimer).toBeNull();
    });

    test('a second play call clears the previous element\'s pending auto-pause', () => {
        const g = gallery();
        const el1 = makeVideoEl();
        const el2 = makeVideoEl();
        g.galleryPreviewPlay(el1);
        g.galleryPreviewPlay(el2);

        vi.advanceTimersByTime(1500);

        expect(el1.pause).not.toHaveBeenCalled();
        expect(el2.pause).toHaveBeenCalled();
    });

    test('galleryPreviewStop cancels the pending auto-pause and pauses/rewinds immediately, exactly once', () => {
        const g = gallery();
        const el = makeVideoEl();
        g.galleryPreviewPlay(el);
        g.galleryPreviewStop(el);

        vi.advanceTimersByTime(1500);

        expect(el.pause).toHaveBeenCalledTimes(1);
        expect(el.currentTime).toBe(0.1);
    });

    test('galleryPreviewStop with no active timer/element does not throw', () => {
        const g = gallery();
        expect(() => g.galleryPreviewStop(null)).not.toThrow();
    });
});

describe('gallery.loadMore — $wire capture correctness + re-entrancy guard', () => {
    test('awaits $wire.loadMore() via a single pre-await read and resets loading afterward', async () => {
        const g = gallery();
        const wire = installWireProbe(g, { loadMore: vi.fn(() => Promise.resolve()) }, 1);

        const p = g.loadMore();
        expect(g.loading).toBe(true);
        await p;

        expect(wire.loadMore).toHaveBeenCalled();
        expect(g.loading).toBe(false);
    });

    test('does not touch $wire at all when already loading', async () => {
        const g = gallery();
        installWireProbe(g, { loadMore: vi.fn(() => Promise.resolve()) }, 0);
        g.loading = true;

        await g.loadMore();
    });

    test('resets loading to false even when the request rejects', async () => {
        const g = gallery();
        const errSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
        installWireProbe(g, { loadMore: vi.fn(() => Promise.reject(new Error('fail'))) }, 1);

        await g.loadMore();

        expect(g.loading).toBe(false);
        errSpy.mockRestore();
    });
});

describe('gallery.init — mobile filmstrip enforcement', () => {
    beforeEach(() => {
        global.Livewire = { hook: vi.fn(() => vi.fn()) };
    });

    function setup(viewFromServer, width) {
        const g = gallery();
        g.$wire = { get: vi.fn(() => viewFromServer), call: vi.fn(), __instance: { id: 1 } };
        g.$watch = vi.fn();
        g.$nextTick = vi.fn();
        g.$refs = {};
        Object.defineProperty(window, 'innerWidth', { value: width, configurable: true });
        return g;
    }

    test('forces filmstrip and notifies the server when the stored view is not filmstrip on a narrow viewport', () => {
        const g = setup('grid', 500);
        g.init();
        expect(g.view).toBe('filmstrip');
        expect(g.$wire.call).toHaveBeenCalledWith('toggleView', 'filmstrip');
        g.destroy();
    });

    test('leaves a non-filmstrip view untouched on a desktop-width viewport', () => {
        const g = setup('grid', 1200);
        g.init();
        expect(g.view).toBe('grid');
        expect(g.$wire.call).not.toHaveBeenCalled();
        g.destroy();
    });

    test('falls back to filmstrip when $wire has no stored view at all', () => {
        const g = setup(null, 1200);
        g.init();
        expect(g.view).toBe('filmstrip');
        g.destroy();
    });
});

describe('gallery.destroy', () => {
    test('tears down timers/listeners, unsubscribes the morph hook, and flips the destroyed flag', () => {
        const g = gallery();
        const morphHookSpy = vi.fn();
        g._morphHook = morphHookSpy;
        g._enforce = vi.fn();
        g._initTimeout = 1;
        g.previewTimer = 2;
        g._scrollRaf = 3;
        g._scrollListener = vi.fn();
        const timeline = { removeEventListener: vi.fn() };
        g.$refs = { timeline };

        const removeSpy = vi.spyOn(window, 'removeEventListener');
        const cafSpy = vi.spyOn(window, 'cancelAnimationFrame').mockImplementation(() => {});

        g.destroy();

        expect(g._isDestroyed).toBe(true);
        expect(morphHookSpy).toHaveBeenCalled();
        expect(g._morphHook).toBeNull();
        expect(g._enforce).toBeNull();
        expect(removeSpy).toHaveBeenCalledWith('resize', expect.any(Function));
        expect(cafSpy).toHaveBeenCalledWith(3);
        expect(timeline.removeEventListener).toHaveBeenCalledWith('scroll', expect.any(Function));
        expect(g._scrollListener).toBeNull();
        expect(g._scrollRaf).toBeNull();
        expect(g._initTimeout).toBeNull();
        expect(g.previewTimer).toBeNull();

        removeSpy.mockRestore();
        cafSpy.mockRestore();
    });
});
