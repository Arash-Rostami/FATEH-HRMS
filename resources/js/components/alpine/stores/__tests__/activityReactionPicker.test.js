import { describe, expect, test } from 'vitest'
import registerActivityReactionPicker from '../activityReactionPicker.js'
import { feedReactions } from '../emoji.js'

function fresh() {
    let captured
    registerActivityReactionPicker({ store: (name, def) => { captured = def } })
    return captured
}

describe('activityReactionPicker.reactions', () => {
    test('is the same feedReactions array exported by emoji.js (shared reference, not a copy)', () => {
        const s = fresh()
        expect(s.reactions).toBe(feedReactions)
    })
})

describe('activityReactionPicker.open/close/is', () => {
    test('open(id, el) sets entryId and positions top/right from the element rect', () => {
        const s = fresh()
        const el = { getBoundingClientRect: () => ({ bottom: 100, right: 300 }) }
        Object.defineProperty(window, 'innerWidth', { configurable: true, value: 1000 })

        s.open(7, el)

        expect(s.entryId).toBe(7)
        expect(s.top).toBe(106)
        expect(s.right).toBe(700)
    })

    test('open(id) without an element sets entryId but leaves top/right untouched', () => {
        const s = fresh()
        s.top = 50
        s.right = 60

        s.open(3)

        expect(s.entryId).toBe(3)
        expect(s.top).toBe(50)
        expect(s.right).toBe(60)
    })

    test('close() clears entryId', () => {
        const s = fresh()
        s.open(1, { getBoundingClientRect: () => ({ bottom: 0, right: 0 }) })
        s.close()
        expect(s.entryId).toBeNull()
    })

    test('is(id) reflects whether the given id matches the currently open entry', () => {
        const s = fresh()
        s.open(5, { getBoundingClientRect: () => ({ bottom: 0, right: 0 }) })
        expect(s.is(5)).toBe(true)
        expect(s.is(6)).toBe(false)
    })

    test('is(null)/is(undefined) are always false, even while closed — fixed 2026-10-01, guards against a blank id matching the null "closed" sentinel', () => {
        const s = fresh()
        expect(s.is(null)).toBe(false)
        expect(s.is(undefined)).toBe(false)

        s.open(5, { getBoundingClientRect: () => ({ bottom: 0, right: 0 }) })
        expect(s.is(null)).toBe(false)
    })
})
