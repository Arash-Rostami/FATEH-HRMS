import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import timer from '../timer.js'

beforeEach(() => {
    localStorage.clear();
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
});

describe('timer mode', () => {
    test('defaults to fa mode when nothing is stored', () => {
        const t = timer();
        expect(t.mode).toBe('fa');
    });

    test('restores the persisted mode', () => {
        localStorage.setItem('timer-mode', 'en');
        const t = timer();
        expect(t.mode).toBe('en');
    });

    test('toggleMode flips fa to en and back, persisting each flip', () => {
        const t = timer();
        t.toggleMode();
        expect(t.mode).toBe('en');
        expect(localStorage.getItem('timer-mode')).toBe('en');

        t.toggleMode();
        expect(t.mode).toBe('fa');
        expect(localStorage.getItem('timer-mode')).toBe('fa');
    });
});

describe('timer updateTime', () => {
    test('fills date and time strings in fa mode using Arabic-Indic numerals', () => {
        const t = timer();
        t.updateTime();

        expect(t.time).toMatch(/^[٠-٩:]+$/);
        expect(t.time.length).toBeGreaterThan(4);
        expect(t.date.length).toBeGreaterThan(4);
    });

    test('fa and en modes produce different renderings of the same moment', () => {
        const t = timer();
        t.mode = 'fa';
        t.updateTime();
        const faDate = t.date;

        t.mode = 'en';
        t.updateTime();
        const enDate = t.date;

        expect(enDate).not.toBe(faDate);
        expect(enDate).toMatch(/^[A-Za-z]/);
    });

    test('init renders once and then re-renders every second', () => {
        const t = timer();
        const spy = vi.spyOn(t, 'updateTime');

        t.init();
        expect(spy).toHaveBeenCalledTimes(1);

        vi.advanceTimersByTime(3000);
        expect(spy.mock.calls.length).toBe(4);
    });

    test('toggleMode re-renders immediately in the new mode', () => {
        const t = timer();
        t.updateTime();
        const before = t.date;

        t.toggleMode();
        expect(t.date).not.toBe(before);
    });
});