import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import home from '../home.js'

function dispatch(name) {
    window.dispatchEvent(new Event(name));
}

let h;

beforeEach(() => {
    sessionStorage.clear();
    vi.useFakeTimers();
});

afterEach(() => {
    h?.destroy();
    vi.useRealTimers();
});

describe('home typewriter', () => {
    test('an empty greeting never starts typing', () => {
        h = home('');
        h.init();
        expect(h.displayed).toBe('');
    });

    test('a greeting already held in sessionStorage under the shared key shows instantly without retyping', () => {
        sessionStorage.setItem('header_greeting', 'سلام آرش');
        h = home('سلام آرش');
        h.init();
        expect(h.displayed).toBe('سلام آرش');
    });

    test('types the first char synchronously and stores the greeting under the shared key', () => {
        h = home('خدا قوت');
        h.init();
        expect(h.displayed).toBe('خ');
        expect(sessionStorage.getItem('header_greeting')).toBe('خدا قوت');
    });

    test('a changed greeting (different key value) types again instead of showing instantly', () => {
        sessionStorage.setItem('header_greeting', 'پیام قدیمی');
        h = home('پیام جدید');
        h.init();
        expect(h.displayed).toBe('پ');
    });

    test('a tab that is hidden when typing would start shows the whole greeting at once', () => {
        Object.defineProperty(document, 'hidden', { configurable: true, value: true });
        h = home('خدا قوت 🚀');
        h.init();
        expect(h.displayed).toBe('خدا قوت 🚀');
        Object.defineProperty(document, 'hidden', { configurable: true, value: false });
    });

    test('hiding the tab mid-typing completes the greeting instead of leaving it half written', () => {
        h = home('سلام آرش، روزت قشنگ شروع بشه');
        h.init();
        vi.advanceTimersByTime(100);
        expect(h.displayed).not.toBe(h.full);

        Object.defineProperty(document, 'hidden', { configurable: true, value: true });
        document.dispatchEvent(new Event('visibilitychange'));
        expect(h.displayed).toBe(h.full);
        Object.defineProperty(document, 'hidden', { configurable: true, value: false });
    });

    test('reduced-motion users get the whole greeting at once', () => {
        const original = window.matchMedia;
        window.matchMedia = () => ({ matches: true });
        h = home('شب بخیر');
        h.init();
        expect(h.displayed).toBe('شب بخیر');
        window.matchMedia = original;
    });

    test('typing never shows half of an emoji (no lone surrogate, no bare base before its variation selector)', () => {
        h = home('عصر بخیر 🌤️✨');
        h.init();
        const seen = [h.displayed];
        for (let i = 0; i < 40; i++) {
            vi.advanceTimersByTime(30);
            seen.push(h.displayed);
        }
        expect(seen.every((s) => !/[\ud800-\udbff](?![\udc00-\udfff])/.test(s))).toBe(true);
        expect(seen.some((s) => s.endsWith('🌤'))).toBe(false);
        expect(h.displayed).toBe('عصر بخیر 🌤️✨');
    });
});

describe('home connection state', () => {
    test('defaults to the navigator online state', () => {
        h = home('x');
        h.init();
        expect(h.online).toBe(navigator.onLine);
        expect(h.lan).toBe(false);
    });

    test('connection getter covers the three states: online, lan-only, offline', () => {
        h = home('');
        h.online = true; h.lan = false;
        expect(h.connection.label).toBe('متصل');

        h.lan = true;
        expect(h.connection.label).toBe('فقط شبکه داخلی');
        expect(h.connection.icon).toBe('lan');

        h.online = false;
        expect(h.connection.label).toBe('آفلاین');
        expect(h.connection.icon).toBe('wifi_off');
    });

    test('init wires the online/offline/internet-unreachable listeners', () => {
        h = home('');
        h.init();

        dispatch('offline');
        expect(h.online).toBe(false);

        dispatch('online');
        expect(h.online).toBe(true);
        expect(h.lan).toBe(false);

        dispatch('internet-unreachable');
        expect(h.lan).toBe(true);
        expect(h.connection.label).toBe('فقط شبکه داخلی');
    });

    test('destroy removes all three listeners so later events no longer mutate state', () => {
        h = home('');
        h.init();
        h.destroy();

        dispatch('offline');
        expect(h.online).toBe(true);

        dispatch('internet-unreachable');
        expect(h.lan).toBe(false);
    });

    test('destroy clears the pending typing timeout', () => {
        h = home('abc');
        h.init();
        expect(h.displayed).toBe('a');

        h.destroy();
        vi.advanceTimersByTime(5000);
        expect(h.displayed).toBe('a');
    });
});