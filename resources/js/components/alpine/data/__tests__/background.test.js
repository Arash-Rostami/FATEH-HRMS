import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import background from '../background.js'

describe('background.init — time-of-day backdrop mode', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    function setup(images, hour) {
        vi.setSystemTime(new Date(2026, 0, 1, hour, 0, 0));
        const b = background();
        b.$store = { background: { backdropMode: 'time', images } };
        return b;
    }

    test('picks early at 5-7', () => {
        const b = setup(['/bg/early.jpg', '/bg/morning.jpg'], 6);
        b.init();
        expect(b.activeIndex).toBe(0);
    });

    test('picks the image whose filename contains the current time-of-day key (morning: 8-9)', () => {
        const b = setup(['/bg/night.jpg', '/bg/morning.jpg', '/bg/noon.jpg'], 8);
        b.init();
        expect(b.activeIndex).toBe(1);
    });

    test('picks noon at the 10-15 boundary', () => {
        const b = setup(['/bg/morning.jpg', '/bg/noon.jpg'], 10);
        b.init();
        expect(b.activeIndex).toBe(1);
    });

    test('picks evening at 16-19', () => {
        const b = setup(['/bg/evening.jpg', '/bg/night.jpg'], 16);
        b.init();
        expect(b.activeIndex).toBe(0);
    });

    test('falls back to night outside the morning/noon/evening ranges', () => {
        const b = setup(['/bg/morning.jpg', '/bg/night.jpg'], 2);
        b.init();
        expect(b.activeIndex).toBe(1);
    });

    test('falls back to index 0 when no image filename matches the current time key', () => {
        const b = setup(['/bg/alpha.jpg', '/bg/beta.jpg'], 8);
        b.init();
        expect(b.activeIndex).toBe(0);
    });
});

describe('background.init — non-time backdrop mode', () => {
    test('calls updateState(true) immediately and watches $wire.activeTab for later changes', () => {
        const b = background();
        b.$store = { background: { backdropMode: 'tabs', tabsOrder: ['a', 'b', 'c'] } };
        b.$wire = { activeTab: 'b' };
        let watchKey, watchCb;
        b.$watch = vi.fn((key, cb) => { watchKey = key; watchCb = cb; });

        b.init();

        expect(watchKey).toBe('$wire.activeTab');
        expect(b.activeIndex).toBe(0);
        expect(b.previousIndex).toBeNull();

        b.$wire.activeTab = 'c';
        watchCb();

        expect(b.activeIndex).toBe(1);
        expect(b.previousIndex).toBe(0);
    });

    test('does nothing further when there is no $wire bound', () => {
        const b = background();
        b.$store = { background: { backdropMode: 'tabs', tabsOrder: ['a'] } };
        b.$watch = vi.fn();

        b.init();

        expect(b.$watch).not.toHaveBeenCalled();
        expect(b.activeIndex).toBe(0);
    });
});

describe('background.updateState', () => {
    test('is a no-op when tabsOrder is missing or not an array', () => {
        const b = background();
        b.$store = { background: {} };
        b.$wire = { activeTab: 'a' };
        expect(() => b.updateState()).not.toThrow();
        expect(b.activeIndex).toBe(0);
    });

    test('is a no-op when the active tab is not found in tabsOrder', () => {
        const b = background();
        b.$store = { background: { tabsOrder: ['a', 'b'] } };
        b.$wire = { activeTab: 'zzz' };
        b.activeIndex = 5;
        b.updateState();
        expect(b.activeIndex).toBe(5);
    });
});

describe('background.getClasses', () => {
    test('returns the active/previous/hidden class sets based on the given index', () => {
        const b = background();
        b.activeIndex = 1;
        b.previousIndex = 0;

        expect(b.getClasses(1)).toBe('animate-backdrop-crossfade-in animate-delay-200');
        expect(b.getClasses(0)).toBe('animate-backdrop-crossfade-out z-0');
        expect(b.getClasses(2)).toBe('opacity-0 z-0');
    });
});

describe('background.renderIndices', () => {
    test('returns [] when there is no background store', () => {
        const b = background();
        b.$store = {};
        expect(b.renderIndices).toEqual([]);
    });

    test('in time mode, returns only the active index (or [] with no images)', () => {
        const b = background();
        b.activeIndex = 2;
        b.$store = { background: { backdropMode: 'time', images: ['a', 'b', 'c'] } };
        expect(b.renderIndices).toEqual([2]);

        b.$store.background.images = [];
        expect(b.renderIndices).toEqual([]);
    });

    test('outside time mode, returns every image index', () => {
        const b = background();
        b.$store = { background: { backdropMode: 'tabs', images: ['a', 'b', 'c'] } };
        expect(b.renderIndices).toEqual([0, 1, 2]);
    });
});

describe('background._baseFilter', () => {
    test('is empty when there is no background store', () => {
        const b = background();
        b.$store = {};
        expect(b._baseFilter).toBe('');
    });

    test('composes only the non-default filter terms, space-joined and trimmed', () => {
        const b = background();
        b.$store = { background: { blur: 5, brightness: 1, contrast: 2, grayscale: 0.5 } };
        expect(b._baseFilter).toBe('blur(5px) contrast(2) grayscale(0.5)');
    });

    test('omits every term when all values are falsy/default', () => {
        const b = background();
        b.$store = { background: { blur: 0, brightness: 1, contrast: 1, grayscale: 0 } };
        expect(b._baseFilter).toBe('');
    });

    test('includes brightness only when it differs from the default of 1', () => {
        const b = background();
        b.$store = { background: { brightness: 1.4 } };
        expect(b._baseFilter).toBe('brightness(1.4)');
    });
});

describe('background.imageStyle', () => {
    test('returns {} when there is no background store', () => {
        const b = background();
        b.$store = {};
        expect(b.imageStyle(0)).toEqual({});
    });

    test('builds backgroundImage and includes filter only when one is computed', () => {
        const b = background();
        b.activeIndex = 5;
        b.$store = { background: { images: ['/a.jpg'], blur: 3 } };
        expect(b.imageStyle(0)).toEqual({ backgroundImage: 'url(/a.jpg)', filter: 'blur(3px)' });
    });

    test('a missing image at the given index resolves to an empty url rather than "undefined"', () => {
        const b = background();
        b.$store = { background: { images: [] } };
        expect(b.imageStyle(0).backgroundImage).toBe('url()');
    });

    test('applies custom width/margin only to the currently active index, and only when width is set and not "100%"', () => {
        const b = background();
        b.activeIndex = 0;
        b.$store = { background: { images: ['/a.jpg'], width: '80%' } };

        expect(b.imageStyle(0)).toMatchObject({ width: '80%', marginInline: 'auto', marginBlock: '1.25rem' });
        expect(b.imageStyle(1).width).toBeUndefined();
    });

    test('a width of exactly "100%" is treated as the default and not applied as an inline style', () => {
        const b = background();
        b.activeIndex = 0;
        b.$store = { background: { images: ['/a.jpg'], width: '100%' } };

        expect(b.imageStyle(0).width).toBeUndefined();
    });
});
