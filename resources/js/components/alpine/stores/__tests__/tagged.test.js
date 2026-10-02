import { describe, expect, test, beforeEach } from 'vitest'
import tagged from '../tagged.js'

function freshStore() {
    let def = null
    tagged({ store: (name, d) => { def = d }, raw: (x) => x })
    return def
}

beforeEach(() => {
    localStorage.clear()
})

describe('tagged.init / _load', () => {
    test('restores a saved map, keeping only integer indices within [0, 4) and coercing keys to Number', () => {
        localStorage.setItem('tagged-contacts', JSON.stringify({ '1': 0, '2': 3, '3': 4, '4': -1, '5': 1.5, '6': 'x' }))
        const s = freshStore()
        s.init()
        expect(s.maps.contact).toEqual({ 1: 0, 2: 3 })
    })

    test('corrupt JSON is caught, yielding an empty map without throwing', () => {
        localStorage.setItem('tagged-channels', '{not json')
        const s = freshStore()
        expect(() => s.init()).not.toThrow()
        expect(s.maps.channel).toEqual({})
    })

    test('a saved array (not a plain object) yields an empty map', () => {
        localStorage.setItem('tagged-projects', JSON.stringify([1, 2]))
        const s = freshStore()
        s.init()
        expect(s.maps.project).toEqual({})
    })
})

describe('tagged.getTag / isTagged', () => {
    test('returns null / false for an id with no tag, even after init', () => {
        const s = freshStore()
        s.init()
        expect(s.getTag(1, 'task')).toBeNull()
        expect(s.isTagged(1, 'task')).toBe(false)
    })

    test('an unknown scope is a safe no-op (getTag null, isTagged false)', () => {
        const s = freshStore()
        expect(s.getTag(1, 'bogus')).toBeNull()
        expect(s.isTagged(1, 'bogus')).toBe(false)
    })
})

describe('tagged.setTag / clearTag', () => {
    test('setTag with a valid index round-trips through getTag/isTagged', () => {
        const s = freshStore()
        s.init()
        s.setTag(10, 2, 'ths')
        expect(s.getTag(10, 'ths')).toBe(2)
        expect(s.isTagged(10, 'ths')).toBe(true)
    })

    test('setTag with an out-of-range or non-integer color is a silent no-op', () => {
        const s = freshStore()
        s.init()
        s.setTag(10, 4, 'ths')
        s.setTag(10, -1, 'ths')
        s.setTag(10, 1.5, 'ths')
        s.setTag(10, 'x', 'ths')
        expect(s.isTagged(10, 'ths')).toBe(false)
    })

    test('clearTag removes an existing tag', () => {
        const s = freshStore()
        s.init()
        s.setTag(10, 1, 'reservation')
        s.clearTag(10, 'reservation')
        expect(s.isTagged(10, 'reservation')).toBe(false)
    })

    test('clearTag on an id with no existing tag is a no-op that does not persist', async () => {
        const s = freshStore()
        s.init()
        s.clearTag(99, 'reservation')
        await Promise.resolve()
        expect(localStorage.getItem('tagged-reservations')).toBeNull()
    })

    test('setTag persists the updated map to localStorage (microtask-deferred)', async () => {
        const s = freshStore()
        s.init()
        s.setTag(3, 0, 'task')
        await Promise.resolve()
        expect(JSON.parse(localStorage.getItem('tagged-tasks'))).toEqual({ 3: 0 })
    })
})

describe('tagged.solid / tagBg', () => {
    test('solid(i) returns the ink color for a valid index and "transparent" out of range', () => {
        const s = freshStore()
        expect(s.solid(0)).toBe('var(--tool-amethyst-color)')
        expect(s.solid(99)).toBe('transparent')
    })

    test('tagBg returns the wash color for a tagged id, null otherwise', () => {
        const s = freshStore()
        s.init()
        expect(s.tagBg(1, 'contact')).toBeNull()

        s.setTag(1, 2, 'contact')
        expect(s.tagBg(1, 'contact')).toBe('var(--tool-sage-bg)')
    })

    test('tagBg on an unknown scope returns null', () => {
        const s = freshStore()
        expect(s.tagBg(1, 'bogus')).toBeNull()
    })
})
