import { describe, expect, test, vi } from 'vitest'
import filters from '../filters.js'

global.requestAnimationFrame = (cb) => setTimeout(cb, 0);

function setScrollY(y) {
    Object.defineProperty(window, 'scrollY', { value: y, configurable: true });
}

describe('filters getters', () => {
    test('hasSearchQuery reflects whether the bound search model has a non-empty value', () => {
        const wireGet = vi.fn(() => 'abc');
        const f = filters(false, 'search', null, null);
        f.$wire = { get: wireGet };
        expect(f.hasSearchQuery).toBe(true);
        expect(wireGet).toHaveBeenCalledWith('search');

        wireGet.mockImplementation(() => '');
        expect(f.hasSearchQuery).toBe(false);
    });

    test('hasActiveFilters is true when activeConditionFn, called bound to the component, returns truthy', () => {
        const activeConditionFn = function () { return this.someFlag === true; };
        const f = filters(false, 'search', activeConditionFn, null);
        f.$wire = { get: () => '' };
        f.someFlag = true;
        expect(f.hasActiveFilters).toBe(true);
    });

    test('hasActiveFilters falls back to hasSearchQuery when activeConditionFn is absent', () => {
        const f = filters(false, 'search', null, null);
        f.$wire = { get: () => 'term' };
        expect(f.hasActiveFilters).toBe(true);
    });
});

describe('filters.clearFilters / clearSearchOnly', () => {
    test('clearFilters invokes clearActionFn bound to the component instance', () => {
        let capturedThis = null;
        const clearActionFn = function () { capturedThis = this; };
        const f = filters(false, 'search', null, clearActionFn);

        f.clearFilters();

        expect(capturedThis).toBe(f);
    });

    test('clearFilters is a no-op when no clearActionFn is given', () => {
        const f = filters(false, 'search', null, null);
        expect(() => f.clearFilters()).not.toThrow();
    });

    test('clearSearchOnly clears only the bound search model via $wire.set', () => {
        const f = filters(false, 'search', null, null);
        f.$wire = { set: vi.fn() };

        f.clearSearchOnly();

        expect(f.$wire.set).toHaveBeenCalledWith('search', '');
    });
});

describe('filters.init — scroll-driven compact/hide behavior', () => {
    test('scrolling down past HIDE_DELTA (while already below the top) hides the filters and marks compact', async () => {
        setScrollY(100);
        const f = filters(true, 'search', null, null);
        f.$watch = vi.fn();
        f.init();

        setScrollY(110);
        f._scrollHandler();
        await new Promise((r) => setTimeout(r, 0));

        expect(f.compact).toBe(true);
        expect(f.showFilters).toBe(false);
        f.destroy();
    });

    test('scrolling back up past SHOW_DELTA un-compacts, without forcing filters back open', async () => {
        setScrollY(100);
        const f = filters(false, 'search', null, null);
        f.$watch = vi.fn();
        f.init();
        f.compact = true;

        setScrollY(90);
        f._scrollHandler();
        await new Promise((r) => setTimeout(r, 0));

        expect(f.compact).toBe(false);
        f.destroy();
    });

    test('scrolling back below TOP_THRESHOLD forces compact off regardless of delta', async () => {
        setScrollY(100);
        const f = filters(false, 'search', null, null);
        f.$watch = vi.fn();
        f.init();
        f.compact = true;

        setScrollY(10);
        f._scrollHandler();
        await new Promise((r) => setTimeout(r, 0));

        expect(f.compact).toBe(false);
        f.destroy();
    });

    test('a scroll delta within the dead zone changes neither compact nor showFilters', async () => {
        setScrollY(100);
        const f = filters(true, 'search', null, null);
        f.$watch = vi.fn();
        f.init();

        setScrollY(101);
        f._scrollHandler();
        await new Promise((r) => setTimeout(r, 0));

        expect(f.compact).toBe(false);
        expect(f.showFilters).toBe(true);
        f.destroy();
    });

    test('the $watch(isDocked) effect syncs $store.chrome.forceHidden to the inverse of the docked state', () => {
        const f = filters(false, 'search', null, null);
        let watchCallback;
        f.$watch = vi.fn((key, cb) => { watchCallback = cb; });
        f.$store = { chrome: { forceHidden: false } };

        f.init();
        watchCallback(false);
        expect(f.$store.chrome.forceHidden).toBe(true);

        watchCallback(true);
        expect(f.$store.chrome.forceHidden).toBe(false);

        f.destroy();
    });
});

describe('filters.destroy', () => {
    test('cancels any pending rAF, removes the scroll listener, and resets forceHidden', () => {
        const f = filters(false, 'search', null, null);
        f.$watch = vi.fn();
        f.$store = { chrome: { forceHidden: true } };
        f.init();
        f._rafId = 999;

        const removeSpy = vi.spyOn(window, 'removeEventListener');
        const cafSpy = vi.spyOn(window, 'cancelAnimationFrame').mockImplementation(() => {});

        f.destroy();

        expect(cafSpy).toHaveBeenCalledWith(999);
        expect(removeSpy).toHaveBeenCalledWith('scroll', expect.any(Function));
        expect(f._rafId).toBeNull();
        expect(f._scrollHandler).toBeNull();
        expect(f.$store.chrome.forceHidden).toBe(false);

        removeSpy.mockRestore();
        cafSpy.mockRestore();
    });
});
