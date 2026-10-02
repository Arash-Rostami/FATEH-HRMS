import { describe, expect, test, vi, beforeEach } from 'vitest'
import shortcut from '../shortcut.js'

const USAGE_KEY = 'qa.usage'
const PINNED_KEY = 'qa.pinned'

beforeEach(() => {
    localStorage.clear()
})

describe('shortcut.score — usage-recency weighting', () => {
    test('an untracked key scores 0', () => {
        const c = shortcut()
        expect(c.score('x', Date.now())).toBe(0)
    })

    test('weights count by 2 and adds a recency boost that decays linearly over 5 days', () => {
        const c = shortcut()
        const now = Date.now()
        c.usage = { a: { count: 3, last: now } }
        expect(c.score('a', now)).toBe(11)
    })

    test('the recency boost floors at 0 once more than 5 days old', () => {
        const c = shortcut()
        const now = Date.now()
        c.usage = { a: { count: 1, last: now - 10 * 86400000 } }
        expect(c.score('a', now)).toBe(2)
    })
})

describe('shortcut.track', () => {
    test('increments count and bumps last for a new key', () => {
        const c = shortcut()
        c.track('a')
        expect(c.usage.a.count).toBe(1)
        expect(c.usage.a.last).toBeGreaterThan(0)
    })

    test('is a no-op for a falsy key', () => {
        const c = shortcut()
        c.track(null)
        expect(c.usage).toEqual({})
    })

    test('debounces repeat tracking of the same key within DEBOUNCE_MS (800ms)', () => {
        vi.useFakeTimers()
        vi.setSystemTime(0)
        const c = shortcut()
        c.track('a')
        vi.setSystemTime(500)
        c.track('a')
        expect(c.usage.a.count).toBe(1)

        vi.setSystemTime(900)
        c.track('a')
        expect(c.usage.a.count).toBe(2)
        vi.useRealTimers()
    })

    test('persists usage to localStorage', () => {
        const c = shortcut()
        c.track('a')
        expect(JSON.parse(localStorage.getItem(USAGE_KEY)).a.count).toBe(1)
    })
})

describe('shortcut.shortcuts / manual / showEmpty', () => {
    const catalog = [
        { key: 'a', type: 'tab', target: 'a' },
        { key: 'b', type: 'tab', target: 'b' },
        { key: 'c', type: 'tab', target: 'c' },
    ]

    test('manual is true only when there are pinned entries', () => {
        const c = shortcut(catalog)
        expect(c.manual).toBe(false)
        c.pinned = ['a']
        expect(c.manual).toBe(true)
    })

    test('with pins present, shortcuts returns the pinned items in pinned order, ignoring usage scores', () => {
        const c = shortcut(catalog)
        c.pinned = ['c', 'a']
        expect(c.shortcuts.map(i => i.key)).toEqual(['c', 'a'])
    })

    test('an unresolvable pinned key (no longer in catalog) is silently dropped', () => {
        const c = shortcut(catalog)
        c.pinned = ['ghost', 'a']
        expect(c.shortcuts.map(i => i.key)).toEqual(['a'])
    })

    test('without pins, shortcuts ranks by score descending and excludes zero-score items', () => {
        const c = shortcut(catalog)
        const now = Date.now()
        c.usage = {
            a: { count: 1, last: now },
            b: { count: 5, last: now },
        }
        expect(c.shortcuts.map(i => i.key)).toEqual(['b', 'a'])
    })

    test('shortcuts is capped at MAX_SHORTCUTS (8) entries', () => {
        const big = Array.from({ length: 10 }, (_, i) => ({ key: `k${i}`, type: 'tab', target: `k${i}` }))
        const c = shortcut(big)
        const now = Date.now()
        c.usage = Object.fromEntries(big.map((i, idx) => [i.key, { count: idx + 1, last: now }]))
        expect(c.shortcuts).toHaveLength(8)
    })

    test('showEmpty is true only when unmanual and there are no scored shortcuts', () => {
        const c = shortcut(catalog)
        expect(c.showEmpty).toBe(true)
        c.usage = { a: { count: 1, last: Date.now() } }
        expect(c.showEmpty).toBe(false)
    })
})

describe('shortcut.isPinned / itemByKey', () => {
    test('isPinned reflects membership in pinned', () => {
        const c = shortcut([{ key: 'a' }])
        expect(c.isPinned('a')).toBe(false)
        c.pinned = ['a']
        expect(c.isPinned('a')).toBe(true)
    })

    test('itemByKey finds a catalog entry by key or returns undefined', () => {
        const c = shortcut([{ key: 'a', label: 'A' }])
        expect(c.itemByKey('a')).toEqual({ key: 'a', label: 'A' })
        expect(c.itemByKey('missing')).toBeUndefined()
    })
})

describe('shortcut.open', () => {
    test('a tab-type item dispatches a switch-tab event instead of navigating', () => {
        const c = shortcut()
        c.$dispatch = vi.fn()
        c.open({ type: 'tab', target: 'reports' })
        expect(c.$dispatch).toHaveBeenCalledWith('switch-tab', { tab: 'reports' })
    })

    test('a non-tab item tracks usage and navigates via Livewire.navigate when available', () => {
        const c = shortcut()
        window.Livewire = { navigate: vi.fn() }
        c.open({ type: 'link', key: 'docs', url: '/docs' })
        expect(c.usage.docs.count).toBe(1)
        expect(window.Livewire.navigate).toHaveBeenCalledWith('/docs')
        delete window.Livewire
    })

    test('is a no-op for a null item', () => {
        const c = shortcut()
        c.$dispatch = vi.fn()
        expect(() => c.open(null)).not.toThrow()
        expect(c.$dispatch).not.toHaveBeenCalled()
    })
})

describe('shortcut pin/unpin/move', () => {
    test('pin adds a key once (no duplicates) and persists', () => {
        const c = shortcut()
        c.pin('a')
        c.pin('a')
        expect(c.pinned).toEqual(['a'])
        expect(JSON.parse(localStorage.getItem(PINNED_KEY))).toEqual(['a'])
    })

    test('unpin removes a key and persists only when it was actually present', () => {
        const c = shortcut()
        c.pinned = ['a', 'b']
        c.unpin('a')
        expect(c.pinned).toEqual(['b'])

        const save = vi.spyOn(Storage.prototype, 'setItem')
        c.unpin('ghost')
        expect(save).not.toHaveBeenCalled()
        save.mockRestore()
    })

    test('move swaps a pinned key with its neighbor in the given direction', () => {
        const c = shortcut()
        c.pinned = ['a', 'b', 'c']
        c.move('a', 1)
        expect(c.pinned).toEqual(['b', 'a', 'c'])
    })

    test('move is a no-op at either boundary or for an unknown key', () => {
        const c = shortcut()
        c.pinned = ['a', 'b']
        c.move('a', -1)
        expect(c.pinned).toEqual(['a', 'b'])
        c.move('b', 1)
        expect(c.pinned).toEqual(['a', 'b'])
        c.move('ghost', 1)
        expect(c.pinned).toEqual(['a', 'b'])
    })

    test('resetToSmart clears pinned (and only persists if it was non-empty)', () => {
        const c = shortcut()
        c.pinned = ['a']
        c.resetToSmart()
        expect(c.pinned).toEqual([])
        expect(JSON.parse(localStorage.getItem(PINNED_KEY))).toEqual([])
    })
})

describe('shortcut.toggleEdit', () => {
    test('flips the editing flag', () => {
        const c = shortcut()
        c.toggleEdit()
        expect(c.editing).toBe(true)
        c.toggleEdit()
        expect(c.editing).toBe(false)
    })
})

describe('shortcut.init / destroy — switch-tab tracking', () => {
    test('listens for switch-tab window events and tracks the matching catalog entry', () => {
        const catalog = [{ key: 'reports', type: 'tab', target: 'reports' }]
        const c = shortcut(catalog)
        c.init()

        window.dispatchEvent(new CustomEvent('switch-tab', { detail: { tab: 'reports' } }))

        expect(c.usage.reports.count).toBe(1)
        c.destroy()
    })

    test('an event for an unmapped tab is ignored', () => {
        const c = shortcut([{ key: 'reports', type: 'tab', target: 'reports' }])
        c.init()
        window.dispatchEvent(new CustomEvent('switch-tab', { detail: { tab: 'unknown' } }))
        expect(c.usage).toEqual({})
        c.destroy()
    })

    test('destroy removes the listener so further events are not tracked', () => {
        const c = shortcut([{ key: 'reports', type: 'tab', target: 'reports' }])
        c.init()
        c.destroy()
        window.dispatchEvent(new CustomEvent('switch-tab', { detail: { tab: 'reports' } }))
        expect(c.usage).toEqual({})
    })
})
