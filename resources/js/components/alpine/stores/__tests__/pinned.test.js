import { describe, expect, test, beforeEach } from 'vitest'
import pinned from '../pinned.js'

function freshStore() {
    let def = null
    pinned({ store: (name, d) => { def = d }, raw: (x) => x })
    return def
}

beforeEach(() => {
    localStorage.clear()
})

describe('pinned.init / _load', () => {
    test('restores a saved numeric list for a non-menu scope, coercing to Number and dropping non-finite entries', () => {
        localStorage.setItem('pinned-contacts', JSON.stringify([1, '2', 'x', 3.5]))
        const s = freshStore()
        s.init()
        expect(s.sets.contact.has(1)).toBe(true)
        expect(s.sets.contact.has(2)).toBe(true)
        expect(s.sets.contact.has(3.5)).toBe(true)
        expect(s.lists.contact.sort((a, b) => a - b)).toEqual([1, 2, 3.5])
    })

    test('a literal null entry is dropped, not coerced to id 0 — fixed 2026-10-01', () => {
        localStorage.setItem('pinned-contacts', JSON.stringify([null]))
        const s = freshStore()
        s.init()
        expect(s.sets.contact.has(0)).toBe(false)
        expect(s.sets.contact.size).toBe(0)
    })

    test('the menu scope keeps entries as strings instead of coercing to Number', () => {
        localStorage.setItem('pinned-menu-items', JSON.stringify(['users', 42, '']))
        const s = freshStore()
        s.init()
        expect(s.sets.menu.has('users')).toBe(true)
        expect(s.sets.menu.has('42')).toBe(true)
        expect(s.lists.menu).toHaveLength(2)
    })

    test('corrupt JSON for a scope is caught, yielding an empty set for that scope without throwing', () => {
        localStorage.setItem('pinned-channels', '{not json')
        const s = freshStore()
        expect(() => s.init()).not.toThrow()
        expect(s.sets.channel.size).toBe(0)
        expect(s.lists.channel).toEqual([])
    })

    test('a saved value that is not an array yields an empty set', () => {
        localStorage.setItem('pinned-projects', JSON.stringify({ a: 1 }))
        const s = freshStore()
        s.init()
        expect(s.lists.project).toEqual([])
    })
})

describe('pinned.isPinned / togglePin', () => {
    test('toggling a non-menu id on then off round-trips isPinned and lists', () => {
        const s = freshStore()
        s.init()

        s.togglePin(5, 'contact')
        expect(s.isPinned(5, 'contact')).toBe(true)
        expect(s.lists.contact).toContain(5)

        s.togglePin(5, 'contact')
        expect(s.isPinned(5, 'contact')).toBe(false)
        expect(s.lists.contact).not.toContain(5)
    })

    test('isPinned coerces the given id to Number for a non-menu scope', () => {
        const s = freshStore()
        s.init()
        s.togglePin(7, 'channel')
        expect(s.isPinned('7', 'channel')).toBe(true)
    })

    test('the menu scope toggles and checks by String(id) instead of Number', () => {
        const s = freshStore()
        s.init()
        s.togglePin('users', 'menu')
        expect(s.isPinned('users', 'menu')).toBe(true)
        expect(s.lists.menu).toEqual(['users'])
    })

    test('an unknown scope is a safe no-op for isPinned (returns false) and togglePin (does not throw)', () => {
        const s = freshStore()
        expect(s.isPinned(1, 'bogus')).toBe(false)
        expect(() => s.togglePin(1, 'bogus')).not.toThrow()
    })

    test('togglePin persists the updated list to localStorage (microtask-deferred)', async () => {
        const s = freshStore()
        s.init()
        s.togglePin(9, 'reservation')
        await Promise.resolve()
        expect(JSON.parse(localStorage.getItem('pinned-reservations'))).toEqual([9])
    })

    test('the ai-report scope restores, toggles and persists under its own key', async () => {
        localStorage.setItem('pinned-ai-reports', JSON.stringify([3]))
        const s = freshStore()
        s.init()
        expect(s.isPinned(3, 'ai-report')).toBe(true)
        s.togglePin(5, 'ai-report')
        await Promise.resolve()
        expect(s.getPinned('ai-report').sort()).toEqual([3, 5])
        expect(JSON.parse(localStorage.getItem('pinned-ai-reports')).sort()).toEqual([3, 5])
        expect(localStorage.getItem('pinned-reservations')).toBeNull()
    })

    test('the post, report and gallery bookmark scopes persist under their own saved_* keys', async () => {
        const s = freshStore()
        s.init()
        s.togglePin(1, 'post')
        s.togglePin(2, 'report')
        s.togglePin(3, 'gallery')
        await Promise.resolve()
        expect(JSON.parse(localStorage.getItem('saved_posts'))).toEqual([1])
        expect(JSON.parse(localStorage.getItem('saved_reports'))).toEqual([2])
        expect(JSON.parse(localStorage.getItem('saved_gallery'))).toEqual([3])
        expect(s.isPinned(1, 'report')).toBe(false)
    })

    test('isEmpty reports whether a scope has no bookmarks', () => {
        const s = freshStore()
        s.init()
        expect(s.isEmpty('post')).toBe(true)
        s.togglePin(1, 'post')
        expect(s.isEmpty('post')).toBe(false)
        expect(s.isEmpty('report')).toBe(true)
    })

    test('the faq scope persists FAQ bookmarks under the saved_faqs key', async () => {
        localStorage.setItem('saved_faqs', JSON.stringify([4]))
        const s = freshStore()
        s.init()
        expect(s.isPinned(4, 'faq')).toBe(true)
        s.togglePin(7, 'faq')
        await Promise.resolve()
        expect(JSON.parse(localStorage.getItem('saved_faqs')).sort()).toEqual([4, 7])
        s.togglePin(4, 'faq')
        await Promise.resolve()
        expect(JSON.parse(localStorage.getItem('saved_faqs'))).toEqual([7])
    })
})

describe('pinned.getPinned', () => {
    test('returns the scope list even before init() has ever run', () => {
        const s = freshStore()
        expect(s.getPinned('activity')).toEqual([])
    })

    test('returns [] for a scope with no list entry at all', () => {
        const s = freshStore()
        expect(s.getPinned('bogus')).toEqual([])
    })
})

describe('pinned correspondence scope', () => {
    test('restores and persists correspondence ids under its dedicated key', async () => {
        localStorage.setItem('pinned-correspondence', JSON.stringify([21]))
        const s = freshStore()
        s.init()
        expect(s.isPinned(21, 'correspondence')).toBe(true)

        s.togglePin(34, 'correspondence')
        await Promise.resolve()

        expect(s.getPinned('correspondence').sort((a, b) => a - b)).toEqual([21, 34])
        expect(JSON.parse(localStorage.getItem('pinned-correspondence')).sort((a, b) => a - b)).toEqual([21, 34])
        expect(localStorage.getItem('pinned-ai-reports')).toBeNull()
    })
})

describe('pinned two-tab safety', () => {
    test('a toggle builds on what another tab already saved instead of overwriting it', async () => {
        const s = freshStore()
        s.init()
        s.togglePin(1, 'faq')
        await Promise.resolve()
        localStorage.setItem('saved_faqs', JSON.stringify([1, 2]))
        s.togglePin(3, 'faq')
        expect(s.lists.faq.sort()).toEqual([1, 2, 3])
    })

    test('with nothing stored yet the in-memory list is kept', () => {
        const s = freshStore()
        s.init()
        s.sets.faq = new Set([7])
        s.togglePin(8, 'faq')
        expect(s.lists.faq.sort()).toEqual([7, 8])
    })
})
