import { describe, expect, test, vi, beforeEach } from 'vitest'
import focusModeMixin from '../focusMode.js'

const SNAPSHOT_KEY = 'focus-snapshot';

function withStore(overrides = {}) {
    return {
        focus: { active: false, until: null, schedule: vi.fn(), clear: vi.fn() },
        background: { enabled: true, patternEnabled: true },
        sound: { globalHush: false },
        ...overrides,
    };
}

beforeEach(() => {
    localStorage.clear();
});

describe('focusModeMixin.initFocusFromSnapshot — 3-way branch (no-snapshot / expired-immediate-deactivate / valid-restore)', () => {
    test('no snapshot in storage: leaves the focus store untouched', () => {
        const c = focusModeMixin();
        c.$store = withStore();
        c.$wire = { call: vi.fn() };

        c.initFocusFromSnapshot();

        expect(c.$store.focus.active).toBe(false);
        expect(c.$store.focus.schedule).not.toHaveBeenCalled();
    });

    test('an expired snapshot (endsAt in the past) is immediately deactivated and the stored key is cleared', () => {
        localStorage.setItem(SNAPSHOT_KEY, JSON.stringify({ presence: 'onsite', backgroundEnabled: true, patternEnabled: false, endsAt: Date.now() - 1000 }));
        const c = focusModeMixin();
        c.$store = withStore();
        c.$wire = { call: vi.fn() };

        c.initFocusFromSnapshot();

        expect(c.$store.focus.active).toBe(false);
        expect(c.$store.focus.clear).toHaveBeenCalledOnce();
        expect(c.$wire.call).toHaveBeenCalledWith('deactivateFocusMode', 'onsite');
        expect(localStorage.getItem(SNAPSHOT_KEY)).toBeNull();
        expect(c.$store.background.enabled).toBe(true);
    });

    test('a valid (non-expired) snapshot restores active state and schedules the remaining time', () => {
        const endsAt = Date.now() + 60000;
        localStorage.setItem(SNAPSHOT_KEY, JSON.stringify({ presence: 'remote', backgroundEnabled: false, patternEnabled: false, endsAt }));
        const c = focusModeMixin();
        c.$store = withStore();

        c.initFocusFromSnapshot();

        expect(c.$store.focus.active).toBe(true);
        expect(c.$store.focus.until).toBe(endsAt);
        expect(c.$store.focus.schedule).toHaveBeenCalledWith(endsAt);
    });

    test('a snapshot with no expiry (endsAt null, indefinite focus) restores and schedules with null', () => {
        localStorage.setItem(SNAPSHOT_KEY, JSON.stringify({ presence: 'onsite', backgroundEnabled: false, patternEnabled: false, endsAt: null }));
        const c = focusModeMixin();
        c.$store = withStore();

        c.initFocusFromSnapshot();

        expect(c.$store.focus.active).toBe(true);
        expect(c.$store.focus.schedule).toHaveBeenCalledWith(null);
    });

    test('a snapshot is ignored entirely once the store already reports focus as active', () => {
        localStorage.setItem(SNAPSHOT_KEY, JSON.stringify({ presence: 'onsite', backgroundEnabled: false, patternEnabled: false, endsAt: Date.now() + 60000 }));
        const c = focusModeMixin();
        c.$store = withStore({ focus: { active: true, until: null, schedule: vi.fn(), clear: vi.fn() } });

        c.initFocusFromSnapshot();

        expect(c.$store.focus.schedule).not.toHaveBeenCalled();
    });

    test('corrupt JSON in storage is treated as no snapshot and never throws', () => {
        localStorage.setItem(SNAPSHOT_KEY, '{not json');
        const c = focusModeMixin();
        c.$store = withStore();

        expect(() => c.initFocusFromSnapshot()).not.toThrow();
        expect(c.$store.focus.active).toBe(false);
    });
});

describe('focusModeMixin window-event wiring', () => {
    test('dispatching focus-mode-expired calls deactivateFocus, and destroyFocusMode detaches the listener so it stops firing', () => {
        const c = focusModeMixin();
        c.$store = withStore();
        c.deactivateFocus = vi.fn();

        c.initFocusFromSnapshot();
        window.dispatchEvent(new Event('focus-mode-expired'));
        expect(c.deactivateFocus).toHaveBeenCalledTimes(1);

        c.destroyFocusMode();
        window.dispatchEvent(new Event('focus-mode-expired'));
        expect(c.deactivateFocus).toHaveBeenCalledTimes(1);
    });

    test('destroyFocusMode is a safe no-op when called twice (e.g. a double-unmount)', () => {
        const c = focusModeMixin();
        c.$store = withStore();
        c.initFocusFromSnapshot();
        c.destroyFocusMode();
        expect(() => c.destroyFocusMode()).not.toThrow();
    });
});

describe('focusModeMixin.activateFocus / deactivateFocus', () => {
    test('activateFocus snapshots presence + background/pattern state, mutes sound, and arms an absolute endsAt timestamp', () => {
        const c = focusModeMixin();
        c.$store = withStore();
        c.$wire = { call: vi.fn() };

        c.activateFocus(30, 'remote');

        const saved = JSON.parse(localStorage.getItem(SNAPSHOT_KEY));
        expect(saved.presence).toBe('remote');
        expect(saved.backgroundEnabled).toBe(true);
        expect(saved.endsAt).toBeGreaterThan(Date.now());
        expect(c.$store.focus.active).toBe(true);
        expect(c.$store.background.enabled).toBe(false);
        expect(c.$store.sound.globalHush).toBe(true);
        expect(c.$wire.call).toHaveBeenCalledWith('activateFocusMode', 30);
    });

    test('activateFocus(null) arms an indefinite session (endsAt stays null, not a bogus timestamp)', () => {
        const c = focusModeMixin();
        c.$store = withStore();

        c.activateFocus(null, 'onsite');

        expect(c.$store.focus.until).toBeNull();
        expect(JSON.parse(localStorage.getItem(SNAPSHOT_KEY)).endsAt).toBeNull();
    });

    test('deactivateFocus is a no-op when focus is not currently active', () => {
        const c = focusModeMixin();
        c.$store = withStore({ focus: { active: false, until: null, schedule: vi.fn(), clear: vi.fn() } });
        c.$wire = { call: vi.fn() };

        c.deactivateFocus();

        expect(c.$wire.call).not.toHaveBeenCalled();
        expect(c.$store.focus.clear).not.toHaveBeenCalled();
    });

    test('deactivateFocus restores the background only if the snapshot had recorded it as enabled', () => {
        localStorage.setItem(SNAPSHOT_KEY, JSON.stringify({ presence: 'onsite', backgroundEnabled: false, patternEnabled: false, endsAt: null }));
        const c = focusModeMixin();
        c.$store = withStore({ focus: { active: true, until: null, schedule: vi.fn(), clear: vi.fn() }, background: { enabled: false, patternEnabled: false } });

        c.deactivateFocus();

        expect(c.$store.background.enabled).toBe(false);
        expect(c.$store.focus.clear).toHaveBeenCalledOnce();
    });
});
