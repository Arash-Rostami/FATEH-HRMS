import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import greeting from '../greeting.js'

beforeEach(() => {
    sessionStorage.clear();
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
});

describe('greeting.init — typewriter scheduling', () => {
    test('an empty fullText does not start typing', () => {
        const g = greeting('');
        g.init();
        expect(g.displayed).toBe('');
    });

    test('when sessionStorage already holds the same greeting, it is shown instantly with no typing animation', () => {
        sessionStorage.setItem('header_greeting', 'سلام دوباره');
        const g = greeting('سلام دوباره');
        g.init();
        expect(g.displayed).toBe('سلام دوباره');

        vi.advanceTimersByTime(5000);
        expect(g.displayed).toBe('سلام دوباره');
    });

    test('types one character synchronously, then the rest on normal-char delays (26ms)', () => {
        const g = greeting('Hi');
        g.init();
        expect(g.displayed).toBe('H');

        vi.advanceTimersByTime(26);
        expect(g.displayed).toBe('Hi');
    });

    test('a sentence-mark char (".") schedules the NEXT tick at 1000ms instead of the base 26ms', () => {
        const g = greeting('Hi.');
        g.init();
        vi.advanceTimersByTime(26);
        vi.advanceTimersByTime(26);
        expect(g.displayed).toBe('Hi.');

        vi.advanceTimersByTime(999);
        expect(g.displayed).toBe('Hi.');
    });

    test('a pause-mark char (",") schedules the NEXT tick at 500ms', () => {
        const g = greeting('a,b');
        g.init();
        vi.advanceTimersByTime(26);
        expect(g.displayed).toBe('a,');

        vi.advanceTimersByTime(499);
        expect(g.displayed).toBe('a,');
        vi.advanceTimersByTime(1);
        expect(g.displayed).toBe('a,b');
    });

    test('writes the greeting into sessionStorage under the shared key so a re-render does not retype it', () => {
        const g = greeting('x');
        g.init();
        expect(sessionStorage.getItem('header_greeting')).toBe('x');
    });

    test('a sessionStorage access failure is swallowed and typing still proceeds', () => {
        const real = window.sessionStorage;
        Object.defineProperty(window, 'sessionStorage', {
            configurable: true,
            value: { getItem: () => { throw new Error('denied'); }, setItem: () => { throw new Error('denied'); } },
        });

        const g = greeting('Hi');
        expect(() => g.init()).not.toThrow();
        expect(g.displayed).toBe('H');

        Object.defineProperty(window, 'sessionStorage', { configurable: true, value: real });
    });
});

describe('greeting.destroy', () => {
    test('clears the pending typing timeout so further time advancement does not continue typing', () => {
        const g = greeting('abc');
        g.init();
        expect(g.displayed).toBe('a');

        g.destroy();
        vi.advanceTimersByTime(5000);
        expect(g.displayed).toBe('a');
    });

    test('is a no-op when there is no pending timeout', () => {
        const g = greeting('');
        g.init();
        expect(() => g.destroy()).not.toThrow();
    });
});
