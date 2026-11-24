import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import recentListMixin from '../recentList.js'

const KEY = 'recent:test'

const make = (options = {}) => {
    const c = recentListMixin({ key: KEY, ...options })
    return c
}

beforeEach(() => {
    localStorage.clear()
})

afterEach(() => {
    vi.restoreAllMocks()
})

describe('recentListMixin.loadRecent', () => {
    test('starts empty and stays empty with nothing stored', () => {
        const c = make()
        c.loadRecent()
        expect(c.recent).toEqual([])
    })

    test('loads a valid stored array', () => {
        localStorage.setItem(KEY, JSON.stringify([{ id: 1 }, { id: 2 }]))
        const c = make()
        c.loadRecent()
        expect(c.recent).toEqual([{ id: 1 }, { id: 2 }])
    })

    test('invalid JSON falls back to an empty list without throwing', () => {
        localStorage.setItem(KEY, '{not json')
        const c = make()
        expect(() => c.loadRecent()).not.toThrow()
        expect(c.recent).toEqual([])
    })

    test('a non-array value is rejected by the default accept', () => {
        localStorage.setItem(KEY, JSON.stringify({ id: 1 }))
        const c = make()
        c.loadRecent()
        expect(c.recent).toEqual([])
    })

    test('a custom accept can reject an otherwise valid array', () => {
        localStorage.setItem(KEY, '[]')
        const c = make({ accept: (v) => Array.isArray(v) && v.length > 0 })
        c.loadRecent()
        expect(c.recent).toEqual([])
    })
})

describe('recentListMixin.pushRecent', () => {
    test('puts the newest entry first and persists under the key', () => {
        const c = make()
        c.pushRecent({ id: 1 })
        c.pushRecent({ id: 2 })
        expect(c.recent.map((r) => r.id)).toEqual([2, 1])
        expect(JSON.parse(localStorage.getItem(KEY))).toEqual([{ id: 2 }, { id: 1 }])
    })

    test('re-pushing an existing id moves it to the front instead of duplicating', () => {
        const c = make()
        c.pushRecent({ id: 1 })
        c.pushRecent({ id: 2 })
        c.pushRecent({ id: 1, title: 'again' })
        expect(c.recent).toEqual([{ id: 1, title: 'again' }, { id: 2 }])
    })

    test('caps the list at max, dropping the oldest', () => {
        const c = make({ max: 3 })
        for (let i = 1; i <= 5; i++) c.pushRecent({ id: i })
        expect(c.recent.map((r) => r.id)).toEqual([5, 4, 3])
    })

    test('defaults the cap to 6', () => {
        const c = make()
        for (let i = 1; i <= 7; i++) c.pushRecent({ id: i })
        expect(c.recent).toHaveLength(6)
    })

    test('stores an entry built by the component as-is', () => {
        const c = make()
        const entry = { id: 9, payload: { id: 9, title: 'x' } }
        c.pushRecent(entry)
        expect(c.recent[0]).toEqual(entry)
    })

    test('a custom idOf decides what counts as the same item', () => {
        const c = make({ idOf: (item) => item.payload.id })
        c.pushRecent({ id: 'a', payload: { id: 1 } })
        c.pushRecent({ id: 'b', payload: { id: 1 } })
        expect(c.recent).toEqual([{ id: 'b', payload: { id: 1 } }])
    })
})

describe('recentListMixin.clearRecent', () => {
    test('empties the list and removes the key', () => {
        const c = make()
        c.pushRecent({ id: 1 })
        c.clearRecent()
        expect(c.recent).toEqual([])
        expect(localStorage.getItem(KEY)).toBeNull()
    })
})

describe('recentListMixin — blocked storage', () => {
    test('never throws when localStorage is unavailable', () => {
        const c = make()
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('blocked') })
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('blocked') })
        vi.spyOn(Storage.prototype, 'removeItem').mockImplementation(() => { throw new Error('blocked') })

        expect(() => c.loadRecent()).not.toThrow()
        expect(c.recent).toEqual([])
        expect(() => c.pushRecent({ id: 1 })).not.toThrow()
        expect(c.recent).toEqual([{ id: 1 }])
        expect(() => c.clearRecent()).not.toThrow()
        expect(c.recent).toEqual([])
    })
})
