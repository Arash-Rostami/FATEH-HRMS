import { describe, test, expect, vi, afterEach } from 'vitest'
import sidebarRailMixin from '../sidebarRail.js'

const bind = (scope) => ({ ...sidebarRailMixin(scope) })

describe('sidebarRailMixin', () => {
    afterEach(() => {
        vi.useRealTimers();
        ['contact', 'channel', 'project'].forEach(s => localStorage.removeItem(`sb-collapsed:${s}`));
    });

    test('flips the collapsed flag, persists it per scope, and drops the slide class after the animation window', () => {
        vi.useFakeTimers();
        const c = bind('contact');
        expect(c.sbCollapsed).toBe(false);

        c.toggleSidebar();

        expect(c.sbCollapsed).toBe(true);
        expect(c.sbSlide).toBe(true);
        expect(localStorage.getItem('sb-collapsed:contact')).toBe('1');
        expect(localStorage.getItem('sb-collapsed:channel')).toBeNull();

        vi.advanceTimersByTime(300);
        expect(c.sbSlide).toBe(false);

        c.toggleSidebar();
        expect(c.sbCollapsed).toBe(false);
        expect(localStorage.getItem('sb-collapsed:contact')).toBe('0');
    });

    test('a new instance restores its own scope\'s stored state', () => {
        localStorage.setItem('sb-collapsed:project', '1');

        expect(bind('project').sbCollapsed).toBe(true);
        expect(bind('channel').sbCollapsed).toBe(false);
    });

    test('rapid toggles keep a single slide window (the earlier timer is cancelled)', () => {
        vi.useFakeTimers();
        const c = bind('channel');

        c.toggleSidebar();
        vi.advanceTimersByTime(200);
        c.toggleSidebar();
        vi.advanceTimersByTime(200);
        expect(c.sbSlide).toBe(true);

        vi.advanceTimersByTime(100);
        expect(c.sbSlide).toBe(false);
    });

    test('works when localStorage throws (private mode): state still toggles', () => {
        vi.useFakeTimers();
        const get = vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('blocked'); });
        const set = vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('blocked'); });

        const c = bind('contact');
        expect(c.sbCollapsed).toBe(false);
        expect(() => c.toggleSidebar()).not.toThrow();
        expect(c.sbCollapsed).toBe(true);

        get.mockRestore();
        set.mockRestore();
    });
});
