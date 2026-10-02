import { describe, expect, test } from 'vitest'
import { emojis, feedEmojis, feedReactions } from '../emoji.js'

describe('emoji.js — static data module (no Alpine.store registration, no logic)', () => {
    test('emojis is a non-empty list of {cat, items} groups, each with a non-empty items array of strings', () => {
        expect(emojis.length).toBeGreaterThan(0)
        for (const group of emojis) {
            expect(typeof group.cat).toBe('string')
            expect(Array.isArray(group.items)).toBe(true)
            expect(group.items.length).toBeGreaterThan(0)
            for (const item of group.items) {
                expect(typeof item).toBe('string')
            }
        }
    })

    test('feedEmojis is a flat non-empty array of emoji strings', () => {
        expect(Array.isArray(feedEmojis)).toBe(true)
        expect(feedEmojis.length).toBeGreaterThan(0)
        expect(feedEmojis.every((e) => typeof e === 'string' && e.length > 0)).toBe(true)
    })

    test('feedReactions is a flat non-empty array of emoji strings', () => {
        expect(Array.isArray(feedReactions)).toBe(true)
        expect(feedReactions.length).toBeGreaterThan(0)
        expect(feedReactions.every((e) => typeof e === 'string' && e.length > 0)).toBe(true)
    })
})
