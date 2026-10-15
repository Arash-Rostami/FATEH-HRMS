import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import maximizeMixin from '../maximize.js'

function withLayoutDom() {
    document.body.innerHTML = `
        <div id="header"></div>
        <div id="navbar"></div>
        <div id="footer"></div>
        <div id="content-shell"></div>
    `;
}

function withUid(uid) {
    document.documentElement.dataset.uid = uid;
}

beforeEach(() => {
    document.body.innerHTML = '';
    delete document.documentElement.dataset.uid;
    document.documentElement.classList.remove('max-restore');
});

describe('maximizeMixin.applyMaximize', () => {
    test('activating adds layout-hidden to header/navbar/footer and maximized to content-shell', () => {
        withLayoutDom();
        const m = maximizeMixin();

        m.applyMaximize(true);

        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(true);
        expect(document.getElementById('navbar').classList.contains('layout-hidden')).toBe(true);
        expect(document.getElementById('footer').classList.contains('layout-hidden')).toBe(true);
        expect(document.getElementById('content-shell').classList.contains('maximized')).toBe(true);
    });

    test('deactivating removes those same classes', () => {
        withLayoutDom();
        const m = maximizeMixin();
        m.applyMaximize(true);

        m.applyMaximize(false);

        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(false);
        expect(document.getElementById('content-shell').classList.contains('maximized')).toBe(false);
    });

    test('no-ops without throwing when the layout elements are absent from the DOM', () => {
        const m = maximizeMixin();
        expect(() => m.applyMaximize(true)).not.toThrow();
    });

    test('does not throw and skips the resize dispatch when $nextTick is absent', () => {
        withLayoutDom();
        const m = maximizeMixin();
        expect(() => m.applyMaximize(true)).not.toThrow();
    });

    test('dispatches a window resize event through $nextTick when it is present', () => {
        withLayoutDom();
        const m = maximizeMixin();
        m.$nextTick = (cb) => cb();
        const handler = vi.fn();
        window.addEventListener('resize', handler, { once: true });

        m.applyMaximize(true);

        expect(handler).toHaveBeenCalledOnce();
    });
});

describe('maximizeMixin.toggleMaximize — open/close timing (graceful-close, MAX_WIDGET_CLOSE_MS=350)', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    test('opening sets max=true synchronously and hides chrome immediately, no leaving state involved', () => {
        withLayoutDom();
        const m = maximizeMixin();

        m.toggleMaximize();

        expect(m.max).toBe(true);
        expect(m.maxLeaving).toBe(false);
        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(true);
    });

    test('closing sets maxLeaving=true and un-hides chrome immediately, but keeps max=true until the close delay elapses', () => {
        withLayoutDom();
        const m = maximizeMixin();
        m.max = true;
        m.applyMaximize(true);

        m.toggleMaximize();

        expect(m.maxLeaving).toBe(true);
        expect(m.max).toBe(true);
        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(false);

        vi.advanceTimersByTime(349);
        expect(m.max).toBe(true);

        vi.advanceTimersByTime(1);
        expect(m.max).toBe(false);
        expect(m.maxLeaving).toBe(false);
    });
});

describe('maximizeMixin persistence — storage key scoping', () => {
    test('scope + uid present yields a non-null storage key', () => {
        withUid('42');
        const m = maximizeMixin('channel');
        expect(m._maxStorageKey).toBe('max:channel:42');
    });

    test('no scope argument yields a null storage key and localStorage is never touched', () => {
        withUid('42');
        const getSpy = vi.spyOn(Storage.prototype, 'getItem');
        const setSpy = vi.spyOn(Storage.prototype, 'setItem');
        const removeSpy = vi.spyOn(Storage.prototype, 'removeItem');

        const m = maximizeMixin();
        expect(m._maxStorageKey).toBeNull();

        m.initMaximize();
        m.toggleMaximize();

        expect(getSpy).not.toHaveBeenCalled();
        expect(setSpy).not.toHaveBeenCalled();
        expect(removeSpy).not.toHaveBeenCalled();
    });

    test('missing uid yields a null storage key and localStorage is never touched', () => {
        const getSpy = vi.spyOn(Storage.prototype, 'getItem');
        const setSpy = vi.spyOn(Storage.prototype, 'setItem');
        const removeSpy = vi.spyOn(Storage.prototype, 'removeItem');

        const m = maximizeMixin('channel');
        expect(m._maxStorageKey).toBeNull();

        m.initMaximize();
        m.toggleMaximize();

        expect(getSpy).not.toHaveBeenCalled();
        expect(setSpy).not.toHaveBeenCalled();
        expect(removeSpy).not.toHaveBeenCalled();
    });

    test('framed window (window.self !== window.top) yields a null storage key and localStorage is never touched', () => {
        withUid('42');
        const originalTop = window.top;
        Object.defineProperty(window, 'top', { value: {}, configurable: true });

        const getSpy = vi.spyOn(Storage.prototype, 'getItem');
        const setSpy = vi.spyOn(Storage.prototype, 'setItem');
        const removeSpy = vi.spyOn(Storage.prototype, 'removeItem');

        const m = maximizeMixin('channel');
        expect(m._maxStorageKey).toBeNull();

        m.initMaximize();
        m.toggleMaximize();

        expect(getSpy).not.toHaveBeenCalled();
        expect(setSpy).not.toHaveBeenCalled();
        expect(removeSpy).not.toHaveBeenCalled();

        Object.defineProperty(window, 'top', { value: originalTop, configurable: true });
    });
});

describe('maximizeMixin.initMaximize deferred restore', () => {
    test('with $nextTick the restore waits until the DOM has settled, so lazily inserted widgets are found and the stored key is not wiped', () => {
        withUid('7')
        localStorage.setItem('max:energy:7', 'score')
        const m = maximizeMixin('energy')
        const queue = []
        m.$nextTick = (cb) => queue.push(cb)
        m.restoreMaximize = vi.fn()

        m.initMaximize()

        expect(m.restoreMaximize).not.toHaveBeenCalled()
        expect(localStorage.getItem('max:energy:7')).toBe('score')

        queue.shift()()
        expect(m.restoreMaximize).toHaveBeenCalledWith('score')
        localStorage.removeItem('max:energy:7')
    })
})

describe('maximizeMixin.initMaximize', () => {
    test('with no stored value, max stays false and no layout classes are applied', () => {
        withLayoutDom();
        withUid('42');
        const m = maximizeMixin('channel');

        m.initMaximize();

        expect(m.max).toBe(false);
        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(false);
    });

    test('with a stored non-null value and no restoreMaximize override, sets max=true and applies layout-hidden classes', () => {
        withLayoutDom();
        withUid('42');
        try {
            localStorage.setItem('max:channel:42', '1');
        } catch {}
        const m = maximizeMixin('channel');

        m.initMaximize();

        expect(m.max).toBe(true);
        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(true);

        try {
            localStorage.removeItem('max:channel:42');
        } catch {}
    });

    test('always removes max-restore from the document root, with or without a stored value', () => {
        withLayoutDom();
        withUid('42');
        document.documentElement.classList.add('max-restore');
        const m = maximizeMixin('channel');

        m.initMaximize();

        expect(document.documentElement.classList.contains('max-restore')).toBe(false);
    });
});

describe('maximizeMixin.toggleMaximize — persistence writes', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => {
        vi.useRealTimers();
        try {
            localStorage.removeItem('max:channel:42');
        } catch {}
    });

    test('opening writes the storage key synchronously', () => {
        withLayoutDom();
        withUid('42');
        const setSpy = vi.spyOn(Storage.prototype, 'setItem');
        const m = maximizeMixin('channel');

        m.toggleMaximize();

        expect(setSpy).toHaveBeenCalledWith('max:channel:42', true);
    });

    test('closing removes the storage key synchronously, before the close timer fires', () => {
        withLayoutDom();
        withUid('42');
        const m = maximizeMixin('channel');
        m.max = true;
        m.applyMaximize(true);
        const removeSpy = vi.spyOn(Storage.prototype, 'removeItem');

        m.toggleMaximize();

        expect(removeSpy).toHaveBeenCalledWith('max:channel:42');

        vi.advanceTimersByTime(349);
        expect(m.max).toBe(true);
    });
});

describe('maximizeMixin — localStorage throwing degrades silently', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    test('initMaximize does not throw when localStorage.getItem throws', () => {
        withLayoutDom();
        withUid('42');
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('blocked'); });
        const m = maximizeMixin('channel');

        expect(() => m.initMaximize()).not.toThrow();
        expect(m.max).toBe(false);
    });

    test('toggleMaximize does not throw when localStorage.setItem/removeItem throw', () => {
        withLayoutDom();
        withUid('42');
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('blocked'); });
        vi.spyOn(Storage.prototype, 'removeItem').mockImplementation(() => { throw new Error('blocked'); });
        const m = maximizeMixin('channel');

        expect(() => m.toggleMaximize()).not.toThrow();
        expect(m.max).toBe(true);

        expect(() => m.toggleMaximize()).not.toThrow();
        vi.advanceTimersByTime(350);
        expect(m.max).toBe(false);
    });
});
