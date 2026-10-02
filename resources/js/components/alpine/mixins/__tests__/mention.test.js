import { describe, expect, test } from 'vitest'
import mentionMixin from '../mention.js'

const m = mentionMixin();

describe('mentionMixin.mentionFilter', () => {
    test('an empty query returns the full list unfiltered', () => {
        expect(m.mentionFilter(['Ali', 'Sara'], '')).toEqual(['Ali', 'Sara']);
    });

    test('filters case-insensitively by substring', () => {
        expect(m.mentionFilter(['Ali', 'Sara', 'Aliya'], 'ali')).toEqual(['Ali', 'Aliya']);
    });

    test('a null/undefined names list or query is treated as empty rather than throwing', () => {
        expect(m.mentionFilter(null, 'x')).toEqual([]);
        expect(m.mentionFilter(['Ali'], undefined)).toEqual(['Ali']);
    });

    test('a falsy entry inside the names list does not throw and is only excluded when it fails the substring test', () => {
        expect(m.mentionFilter(['Ali', null, 'Sara'], '')).toEqual(['Ali', null, 'Sara']);
        expect(m.mentionFilter(['Ali', null, 'Sara'], 'a')).toEqual(['Ali', 'Sara']);
    });
});

describe('mentionMixin.mentionAtTerm — active @term detection under the caret (the shared "channel-style" close rule)', () => {
    test('returns null when there is no @ before the cursor', () => {
        expect(m.mentionAtTerm('hello world', 5)).toBeNull();
    });

    test('detects an in-progress @term at the very start of the string', () => {
        expect(m.mentionAtTerm('@ali', 4)).toEqual({ at: 0, term: 'ali' });
    });

    test('detects an in-progress @term that opens right after whitespace', () => {
        expect(m.mentionAtTerm('hi @al', 6)).toEqual({ at: 3, term: 'al' });
    });

    test('closes (returns null) once whitespace appears inside the term', () => {
        expect(m.mentionAtTerm('hi @al x', 8)).toBeNull();
    });

    test('requires whitespace immediately before @ — a mid-word @ (e.g. an email address) never opens the picker', () => {
        expect(m.mentionAtTerm('email@domain', 12)).toBeNull();
    });

    test('a character directly before @ that is itself "@" still does not count as whitespace — stays closed', () => {
        expect(m.mentionAtTerm('x@@al', 5)).toBeNull();
    });

    test('resolves against the cursor position, ignoring any text typed after it', () => {
        expect(m.mentionAtTerm('@ali and more', 4)).toEqual({ at: 0, term: 'ali' });
    });
});

describe('mentionMixin.mentionBuild — splice "@name " into the composer value at the caret', () => {
    test('replaces the active @term with "@name " and places the caret right after the trailing space', () => {
        const r = m.mentionBuild('hi @al', 6, 'Alice');
        expect(r.value).toBe('hi @Alice ');
        expect(r.caret).toBe(10);
    });

    test('preserves text that comes after the cursor, inserted right after the trailing space', () => {
        const r = m.mentionBuild('hi @al, there', 6, 'Alice');
        expect(r.value).toBe('hi @Alice , there');
        expect(r.caret).toBe(10);
    });

    test('returns null when there is no @ before the cursor', () => {
        expect(m.mentionBuild('hello', 5, 'Alice')).toBeNull();
    });
});

describe('mentionMixin.mentionIsNotified — Unicode token-boundary @mention matching', () => {
    test('matches a whole-token @name surrounded by whitespace', () => {
        expect(m.mentionIsNotified('hi @Ali how are you', 'Ali')).toBe(true);
    });

    test('does NOT match a shorter name that is only a prefix of a longer mention (@Al must not match inside @Albert)', () => {
        expect(m.mentionIsNotified('hi @Albert', 'Al')).toBe(false);
    });

    test('matches at the very start of the body', () => {
        expect(m.mentionIsNotified('@Ali hi', 'Ali')).toBe(true);
    });

    test('matches a name immediately followed by punctuation, not just end-of-string/whitespace', () => {
        expect(m.mentionIsNotified('hi @Ali, welcome', 'Ali')).toBe(true);
    });

    test('a name containing regex metacharacters is escaped and matched literally, not as a pattern', () => {
        expect(m.mentionIsNotified('hi @a.b there', 'a.b')).toBe(true);
        expect(m.mentionIsNotified('hi @axb there', 'a.b')).toBe(false);
    });

    test('a null/undefined/empty body is treated as empty and never throws', () => {
        expect(m.mentionIsNotified(null, 'Ali')).toBe(false);
        expect(m.mentionIsNotified(undefined, 'Ali')).toBe(false);
        expect(m.mentionIsNotified('', 'Ali')).toBe(false);
    });

    test('matches Unicode (Persian) letters as token characters, same as ASCII', () => {
        expect(m.mentionIsNotified('سلام @علی خوبی', 'علی')).toBe(true);
        expect(m.mentionIsNotified('سلام @علی‌پور خوبی', 'علی‌پور')).toBe(true);
    });

    test('a shorter name does NOT match inside a ZWNJ-joined Persian compound name — fixed 2026-10-01, ZWNJ (U+200C) is now a token character, not a boundary', () => {
        expect(m.mentionIsNotified('سلام @علی‌رضا خوبی', 'علی')).toBe(false);
        expect(m.mentionIsNotified('سلام @علی‌رضا خوبی', 'علی‌رضا')).toBe(true);
    });

    test('is case-sensitive (unlike mentionFilter) — the regex carries no "i" flag', () => {
        expect(m.mentionIsNotified('hi @ali', 'Ali')).toBe(false);
    });
});
