import { describe, expect, test, vi, beforeEach } from 'vitest'
import lastOpenMixin from '../lastOpen.js'

function withUid(uid) {
    document.documentElement.dataset.uid = uid;
}

beforeEach(() => { delete document.documentElement.dataset.uid; });

describe('lastOpenMixin — storage key scoping', () => {
    test('scope + uid present yields a non-null storage key', () => {
        withUid('42');
        const m = lastOpenMixin('channel');
        expect(m._lastOpenStorageKey).toBe('last:channel:42');
    });

    test('no scope argument yields a null storage key', () => {
        withUid('42');
        const m = lastOpenMixin();
        expect(m._lastOpenStorageKey).toBeNull();
    });

    test('missing uid yields a null storage key', () => {
        const m = lastOpenMixin('channel');
        expect(m._lastOpenStorageKey).toBeNull();
    });

    test('framed window (window.self !== window.top) yields a null storage key', () => {
        withUid('42');
        const originalTop = window.top;
        Object.defineProperty(window, 'top', { value: {}, configurable: true });

        const m = lastOpenMixin('channel');
        expect(m._lastOpenStorageKey).toBeNull();

        Object.defineProperty(window, 'top', { value: originalTop, configurable: true });
    });
});

describe('lastOpenMixin._persistLastOpen / _readLastOpenId', () => {
    test('persists and reads back the id as a number', () => {
        withUid('42');
        const m = lastOpenMixin('channel');

        m._persistLastOpen(7);

        expect(localStorage.getItem('last:channel:42')).toBe('7');
        expect(m._readLastOpenId()).toBe(7);

        localStorage.removeItem('last:channel:42');
    });

    test('_persistLastOpen is a no-op for a falsy id or a null storage key', () => {
        const setSpy = vi.spyOn(Storage.prototype, 'setItem');
        const m = lastOpenMixin('channel');

        m._persistLastOpen(0);
        m._persistLastOpen(null);

        expect(setSpy).not.toHaveBeenCalled();

        withUid('42');
        const m2 = lastOpenMixin('channel');
        m2._persistLastOpen(0);
        expect(setSpy).not.toHaveBeenCalled();
    });

    test('_readLastOpenId returns null when nothing is stored or the storage key is null', () => {
        const m = lastOpenMixin('channel');
        expect(m._readLastOpenId()).toBeNull();

        withUid('42');
        const m2 = lastOpenMixin('channel');
        expect(m2._readLastOpenId()).toBeNull();
    });

    test('_readLastOpenId returns null for a non-numeric stored value', () => {
        withUid('42');
        localStorage.setItem('last:channel:42', 'not-a-number');
        const m = lastOpenMixin('channel');

        expect(m._readLastOpenId()).toBeNull();

        localStorage.removeItem('last:channel:42');
    });

    test('degrades silently when localStorage throws on write or read', () => {
        withUid('42');
        const m = lastOpenMixin('channel');

        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('blocked'); });
        expect(() => m._persistLastOpen(7)).not.toThrow();

        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('blocked'); });
        expect(() => m._readLastOpenId()).not.toThrow();
        expect(m._readLastOpenId()).toBeNull();
    });
});
