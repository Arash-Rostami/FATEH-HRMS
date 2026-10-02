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

beforeEach(() => {
    document.body.innerHTML = '';
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
