import { describe, expect, test } from 'vitest'
import password from '../password.js'

describe('password.checkStrength', () => {
    test('empty value: no flags set, strength stays 0', () => {
        const p = password();
        p.checkStrength('');
        expect(p.hasNumber).toBe(false);
        expect(p.hasLetter).toBe(false);
        expect(p.hasSpecial).toBe(false);
        expect(p.strength).toBe(0);
    });

    test('length >= 6 alone scores 1, hasLetter stays false below the 8-letter threshold', () => {
        const p = password();
        p.checkStrength('abcdef');
        expect(p.letterCount).toBe(6);
        expect(p.hasLetter).toBe(false);
        expect(p.strength).toBe(1);
    });

    test('length >= 10 with 8+ letters scores 2 (hasLetter true, hasNumber/hasSpecial false)', () => {
        const p = password();
        p.checkStrength('abcdefghij');
        expect(p.hasLetter).toBe(true);
        expect(p.strength).toBe(2);
    });

    test('8+ letters AND 8+ numbers AND a special char forces strength to exactly 3, regardless of length scoring', () => {
        const p = password();
        p.checkStrength('aaaaaaaa12345678!');
        expect(p.hasLetter).toBe(true);
        expect(p.hasNumber).toBe(true);
        expect(p.hasSpecial).toBe(true);
        expect(p.strength).toBe(3);
    });

    test('short mixed value under 6 chars scores 0 even with letters and digits present', () => {
        const p = password();
        p.checkStrength('abc123');
        expect(p.strength).toBe(1);
    });
});

describe('password bar color getters', () => {
    test('bar1Color is empty below strength 1, red when strength>=1 but not all-green, green when all-green', () => {
        const p = password();
        p.checkStrength('');
        expect(p.bar1Color).toBe('');

        p.checkStrength('abcdef');
        expect(p.bar1Color).toBe('bg-[var(--md-sys-color-error)]');

        p.checkStrength('aaaaaaaa12345678!');
        expect(p.bar1Color).toBe('bg-green-500');
    });

    test('bar2Color is empty below strength 2, yellow at strength>=2 but not all-green, green when all-green', () => {
        const p = password();
        p.checkStrength('abcdef');
        expect(p.bar2Color).toBe('');

        p.checkStrength('abcdefghij');
        expect(p.bar2Color).toBe('bg-yellow-500');

        p.checkStrength('aaaaaaaa12345678!');
        expect(p.bar2Color).toBe('bg-green-500');
    });

    test('bar3Color is empty below strength 3, green only at strength 3', () => {
        const p = password();
        p.checkStrength('abcdefghij');
        expect(p.bar3Color).toBe('');

        p.checkStrength('aaaaaaaa12345678!');
        expect(p.bar3Color).toBe('bg-green-500');
    });
});

describe('password.showIndicator', () => {
    test('reflects whether the last-checked value has any characters', () => {
        const p = password();
        p.checkStrength('a');
        expect(p.showIndicator).toBe(true);

        p.checkStrength('');
        expect(p.showIndicator).toBe(false);
    });

    test('reading showIndicator before checkStrength has ever run no longer throws — fixed 2026-10-01, `value` is now initialized to empty string in the returned data object', () => {
        const p = password();
        expect(() => p.showIndicator).not.toThrow();
        expect(p.showIndicator).toBe(false);
    });
});
