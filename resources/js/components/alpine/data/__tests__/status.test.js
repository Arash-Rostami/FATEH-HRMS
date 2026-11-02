import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import status from '../status.js'

function makeStatus() {
    const s = status('levels');
    s.$root = document.createElement('div');
    s.$refs = {
        depts: {
            scrollLeft: 0,
            scrollWidth: 300,
            clientWidth: 100,
            scrollBy: vi.fn(),
        },
    };
    s.vBox = { scrollTop: 0, scrollBy: vi.fn() };
    return s;
}

beforeEach(() => {
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
});

describe('status init', () => {
    test('picks the nearest scrollable ancestor as the vertical scroll box', () => {
        const scrollable = document.createElement('div');
        Object.defineProperty(scrollable, 'scrollHeight', { configurable: true, value: 300 });
        Object.defineProperty(scrollable, 'clientHeight', { configurable: true, value: 100 });
        const plain = document.createElement('div');

        const s = status('levels');
        s.$root = document.createElement('div');
        plain.appendChild(s.$root);
        scrollable.appendChild(plain);
        document.body.appendChild(scrollable);

        s.init();
        expect(s.vBox).toBe(scrollable);

        document.body.removeChild(scrollable);
    });

    test('falls back to the document scrolling element when no ancestor scrolls', () => {
        const s = status('levels');
        s.$root = document.createElement('div');
        document.body.appendChild(s.$root);

        s.init();
        expect(s.vBox).toBe(document.scrollingElement || document.documentElement);

        document.body.removeChild(s.$root);
    });
});

describe('status card/dept state', () => {
    test('toggleDept flips each code independently', () => {
        const s = makeStatus();
        s.toggleDept('HR');
        expect(s.collapsed['HR']).toBe(true);
        expect(s.collapsed['IT']).toBeUndefined();

        s.toggleDept('HR');
        expect(s.collapsed['HR']).toBe(false);
    });

    test('toggleCard opens a card and a second click closes it', () => {
        const s = makeStatus();
        s.toggleCard(7);
        expect(s.openId).toBe(7);

        s.toggleCard(7);
        expect(s.openId).toBeNull();
    });

    test('extraKeys drops the six core keys and falsy entries, keeping custom ones', () => {
        const s = makeStatus();
        s.aboutMe = {
            bio: 'رزومه',
            movies: '',
            custom_field: 'مقدار',
            another: 0,
            third: 'دیگری',
        };
        expect(s.extraKeys).toEqual(['custom_field', 'third']);
    });
});

describe('status tools popover', () => {
    test('openTools clamps the popover origin into the padded viewport', () => {
        const s = makeStatus();

        s.openTools({ clientX: 10, clientY: 7000 });
        expect(s.toolsX).toBe(56);
        expect(s.toolsY).toBe(window.innerHeight - 56);

        s.openTools({ clientX: 5000, clientY: 20 });
        expect(s.toolsX).toBe(window.innerWidth - 56);
        expect(s.toolsY).toBe(56);

        expect(s.toolsOpen).toBe(true);
    });
});

describe('status drag-to-pan', () => {
    test('a move beyond the 5px threshold marks the gesture as dragged', () => {
        const s = makeStatus();
        const prevent = vi.fn();

        s.onMouseDown({ clientX: 100, clientY: 100 });
        s.onMouseMove({ clientX: 103, clientY: 102, preventDefault: prevent });
        expect(s.dragged).toBe(false);

        s.onMouseMove({ clientX: 110, clientY: 104, preventDefault: prevent });
        expect(s.dragged).toBe(true);
    });

    test('onMouseMove pans the depts rail horizontally and the page vertically by the drag delta', () => {
        const s = makeStatus();
        const prevent = vi.fn();
        s.$refs.depts.scrollLeft = 0;
        s.vBox.scrollTop = 500;

        s.onMouseDown({ clientX: 200, clientY: 300 });
        s.onMouseMove({ clientX: 160, clientY: 280, preventDefault: prevent });

        expect(s.$refs.depts.scrollLeft).toBe(40);
        expect(s.vBox.scrollTop).toBe(520);
    });

    test('onClickCapture swallows the click only after a real drag, then clears the flag', () => {
        const s = makeStatus();
        const stop = vi.fn();
        const prevent = vi.fn();

        s.onClickCapture({ stopPropagation: stop });
        expect(stop).not.toHaveBeenCalled();

        s.onMouseDown({ clientX: 0, clientY: 0 });
        s.onMouseMove({ clientX: 30, clientY: 0, preventDefault: prevent });
        s.onClickCapture({ stopPropagation: stop });

        expect(stop).toHaveBeenCalledTimes(1);
        expect(s.dragged).toBe(false);
    });

    test('onMouseLeave resets the drag and hover state', () => {
        const s = makeStatus();
        s.onMouseDown({ clientX: 0, clientY: 0 });
        s.hovering = true;

        s.onMouseLeave();
        expect(s.down).toBe(false);
        expect(s.hovering).toBe(false);
    });

    test('pan delegates horizontal delta to the rail and vertical delta to the page', () => {
        const s = makeStatus();

        s.pan(50, 20);
        expect(s.$refs.depts.scrollBy).toHaveBeenCalledWith({ left: 50, behavior: 'smooth' });
        expect(s.vBox.scrollBy).toHaveBeenCalledWith({ top: 20, behavior: 'smooth' });

        s.pan(50, 0);
        expect(s.vBox.scrollBy).toHaveBeenCalledTimes(1);

        const noRail = makeStatus();
        delete noRail.$refs.depts;
        expect(() => noRail.pan(10, 0)).not.toThrow();
    });

    test('onWheel converts vertical wheel to rail scroll only when the rail overflows', () => {
        const s = makeStatus();
        const prevent = vi.fn();

        s.onWheel({ deltaY: 40, preventDefault: prevent });
        expect(prevent).toHaveBeenCalled();
        expect(s.$refs.depts.scrollLeft).toBe(40);

        s.$refs.depts.scrollWidth = 100;
        s.onWheel({ deltaY: 40, preventDefault: prevent });
        expect(prevent).toHaveBeenCalledTimes(1);
        expect(s.$refs.depts.scrollLeft).toBe(40);
    });
});

describe('status resetData', () => {
    test('clears the loaded user after the 300ms close animation', () => {
        const s = makeStatus();
        s.user = { id: 1 };

        s.resetData();
        expect(s.user).toEqual({ id: 1 });

        vi.advanceTimersByTime(300);
        expect(s.user).toBeNull();
    });
});