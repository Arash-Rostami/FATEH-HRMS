import { describe, expect, test, vi, beforeEach } from 'vitest'
import menu from '../menu.js'

function item(id, overrides = {}) {
    return { id, title: id, sub: '', group: overrides.group ?? 'g', hasNotification: false, _searchKey: id.toLowerCase(), ...overrides }
}

function withPins(ids = []) {
    return { sets: { menu: new Set(ids) }, isPinned: vi.fn((id) => ids.includes(id)), togglePin: vi.fn() }
}

describe('menu.sortedItems', () => {
    test('returns items unchanged (same reference) when not sorting alpha and no pins exist', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('b'), item('a')]
        expect(m.sortedItems).toBe(m.items)
    })

    test('pinned items float to the front, preserving original order among the rest', () => {
        const m = menu()
        m.$store = { pinned: withPins(['c']) }
        m.items = [item('a'), item('b'), item('c')]
        expect(m.sortedItems.map((i) => i.id)).toEqual(['c', 'a', 'b'])
    })

    test('sortAlpha sorts non-pinned items by Persian locale title, pins still win first', () => {
        const m = menu()
        m.$store = { pinned: withPins(['z']) }
        m.sortAlpha = true
        m.items = [item('b', { title: 'ب' }), item('a', { title: 'آ' }), item('z', { title: 'ز' })]
        expect(m.sortedItems.map((i) => i.id)).toEqual(['z', 'a', 'b'])
    })

    test('the sorted result is cached until sortAlpha or the pins version changes', () => {
        const m = menu()
        m.$store = { pinned: withPins(['a']) }
        m.items = [item('a'), item('b')]
        const first = m.sortedItems
        expect(m.sortedItems).toBe(first)

        m._pinsVersion++
        expect(m.sortedItems).not.toBe(first)
    })
})

describe('menu.groupedItems', () => {
    test('groups visible items by their group label, defaulting ungrouped items to "سایر"', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a', { group: 'G1' }), item('b', { group: 'G1' }), item('c', { group: null })]

        const groups = m.groupedItems

        expect(groups.map((g) => g.label)).toEqual(['G1', 'سایر'])
        expect(groups[0].items.map((i) => i.id)).toEqual(['a', 'b'])
        expect(groups[1].items.map((i) => i.id)).toEqual(['c'])
    })

    test('is cached by source-array reference and recomputes only when allVisibleItems changes', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a')]
        const first = m.groupedItems
        expect(m.groupedItems).toBe(first)

        m.items = [item('a')]
        expect(m.groupedItems).not.toBe(first)
    })
})

describe('menu.paginatedData', () => {
    test('chunks sortedItems into pages of perPage size', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b'), item('c')]
        m.perPage = 2
        expect(m.paginatedData.map((p) => p.map((i) => i.id))).toEqual([['a', 'b'], ['c']])
    })

    test('is cached until the source items or perPage changes', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b')]
        m.perPage = 1
        const first = m.paginatedData
        expect(m.paginatedData).toBe(first)

        m.perPage = 2
        expect(m.paginatedData).not.toBe(first)
    })
})

describe('menu._filteredItems / allVisibleItems / activePages', () => {
    test('recent-only mode maps recentIds through the item map, silently skipping unknown ids', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b')]
        m._itemMap = new Map(m.items.map((i) => [i.id, i]))
        m.showRecentOnly = true
        m.recentIds = ['b', 'missing', 'a']

        expect(m.allVisibleItems.map((i) => i.id)).toEqual(['b', 'a'])
        expect(m.activePages).toEqual([m.allVisibleItems])
    })

    test('a non-empty search filters sortedItems by the precomputed _searchKey', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('alpha'), item('beta')]
        m.search = 'AL'
        expect(m.allVisibleItems.map((i) => i.id)).toEqual(['alpha'])
    })

    test('no search and not recent-only falls back to the full sortedItems/paginatedData', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b')]
        expect(m.allVisibleItems).toBe(m.sortedItems)
        expect(m.activePages).toBe(m.paginatedData)
    })
})

describe('menu.activeIndex / currentFocusableItems', () => {
    test('activeIndex is forced to 0 while searching or showing recents, otherwise it is the current page', () => {
        const m = menu()
        m.current = 3
        expect(m.activeIndex).toBe(3)
        m.search = 'x'
        expect(m.activeIndex).toBe(0)
        m.search = ''
        m.showRecentOnly = true
        expect(m.activeIndex).toBe(0)
    })

    test('grid mode reads the active page; list/grouped modes use the full visible list', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b'), item('c')]
        m.perPage = 2
        m.viewMode = 'grid'
        expect(m.currentFocusableItems.map((i) => i.id)).toEqual(['a', 'b'])

        m.viewMode = 'list'
        expect(m.currentFocusableItems.map((i) => i.id)).toEqual(['a', 'b', 'c'])
    })
})

describe('menu._pageForItem / listCols / pageHasNotification / notifiedCount', () => {
    test('_pageForItem finds the page containing a given id, defaulting to 0 for null/unknown', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b'), item('c')]
        m.perPage = 2
        expect(m._pageForItem('c')).toBe(1)
        expect(m._pageForItem('a')).toBe(0)
        expect(m._pageForItem(null)).toBe(0)
        expect(m._pageForItem('unknown')).toBe(0)
    })

    test('listCols steps 1/2/3 at the 4 and 12 item thresholds', () => {
        const m = menu()
        expect(m.listCols(3)).toBe(1)
        expect(m.listCols(4)).toBe(1)
        expect(m.listCols(5)).toBe(2)
        expect(m.listCols(12)).toBe(2)
        expect(m.listCols(13)).toBe(3)
    })

    test('pageHasNotification/notifiedCount reflect each item\'s hasNotification flag', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a', { hasNotification: true }), item('b')]
        m.perPage = 2
        expect(m.pageHasNotification(0)).toBe(true)
        expect(m.pageHasNotification(5)).toBe(false)
        expect(m.notifiedCount).toBe(1)
    })
})

describe('menu.recordRecent', () => {
    test('moves a re-clicked id to the front without duplicating it', () => {
        const m = menu()
        m._saveState = vi.fn()
        m.recentIds = ['a', 'b', 'c']
        m.recordRecent(item('b'))
        expect(m.recentIds).toEqual(['b', 'a', 'c'])
    })

    test('caps the list at RECENT_MAX (8) entries', () => {
        const m = menu()
        m._saveState = vi.fn()
        m.recentIds = ['1', '2', '3', '4', '5', '6', '7', '8']
        m.recordRecent(item('9'))
        expect(m.recentIds).toHaveLength(8)
        expect(m.recentIds[0]).toBe('9')
        expect(m.recentIds).not.toContain('8')
    })

    test('ignores an item without an id', () => {
        const m = menu()
        m._saveState = vi.fn()
        m.recentIds = ['a']
        m.recordRecent({})
        expect(m.recentIds).toEqual(['a'])
    })
})

describe('menu.togglePin', () => {
    test('delegates to the pinned store and bumps _pinsVersion', () => {
        const m = menu()
        const pinned = withPins([])
        m.$store = { pinned }
        const before = m._pinsVersion

        m.togglePin(item('a'))

        expect(pinned.togglePin).toHaveBeenCalledWith('a', 'menu')
        expect(m._pinsVersion).toBe(before + 1)
    })

    test('resets to the first page when newly pinning while not searching/recent-only', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.current = 2
        m.togglePin(item('a'))
        expect(m.current).toBe(0)
    })

    test('does not reset the page when unpinning, or while a search/recent filter is active', () => {
        const m = menu()
        m.$store = { pinned: withPins(['a']) }
        m.current = 2
        m.togglePin(item('a'))
        expect(m.current).toBe(2)

        m.search = 'x'
        m.$store = { pinned: withPins([]) }
        m.togglePin(item('b'))
        expect(m.current).toBe(2)
    })

    test('prevents the default/bubbling click behavior when an event is given, and is a no-op without an id', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        const evt = { preventDefault: vi.fn(), stopPropagation: vi.fn() }
        m.togglePin(item('a'), evt)
        expect(evt.preventDefault).toHaveBeenCalled()
        expect(evt.stopPropagation).toHaveBeenCalled()
        expect(() => m.togglePin(null)).not.toThrow()
    })
})

describe('menu.handleItemClick', () => {
    test('a disabled item prevents default and records nothing', () => {
        const m = menu()
        m._saveState = vi.fn()
        m.closeMenu = vi.fn()
        const evt = { preventDefault: vi.fn() }
        m.handleItemClick(item('a', { disabled: true }), evt)
        expect(evt.preventDefault).toHaveBeenCalled()
        expect(m.closeMenu).not.toHaveBeenCalled()
    })

    test('an action item (href "-") prevents navigation and dispatches its action', () => {
        const m = menu()
        m._saveState = vi.fn()
        m.closeMenu = vi.fn()
        m.$dispatch = vi.fn()
        const evt = { preventDefault: vi.fn() }
        m.handleItemClick(item('a', { href: '-', action: 'stopwatch' }), evt)
        expect(evt.preventDefault).toHaveBeenCalled()
        expect(m.$dispatch).toHaveBeenCalledWith('stopwatch')
    })

    test('a ctrl/meta click records the item but leaves the menu open', () => {
        const m = menu()
        m._saveState = vi.fn()
        m.closeMenu = vi.fn()
        m.handleItemClick(item('a'), { preventDefault() {}, ctrlKey: true })
        expect(m.closeMenu).not.toHaveBeenCalled()
    })

    test('a plain click records the item and closes the menu', () => {
        const m = menu()
        m._saveState = vi.fn()
        m.closeMenu = vi.fn()
        m.handleItemClick(item('a'), { preventDefault() {}, ctrlKey: false, metaKey: false })
        expect(m.closeMenu).toHaveBeenCalledTimes(1)
    })
})

describe('menu.moveFocus', () => {
    beforeEach(() => {
        vi.stubGlobal('setTimeout', setTimeout)
    })

    test('with no current focus, a forward delta focuses the first item and a backward delta focuses the last', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b'), item('c')]
        m.viewMode = 'list'
        m.moveFocus(1)
        expect(m.focusIndex).toBe('a')

        m.focusIndex = null
        m.moveFocus(-1)
        expect(m.focusIndex).toBe('c')
    })

    test('moves focus within the current page bounds', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b'), item('c')]
        m.viewMode = 'list'
        m.focusIndex = 'a'
        m.moveFocus(1)
        expect(m.focusIndex).toBe('b')
    })

    test('is a no-op when there are no focusable items', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = []
        m.viewMode = 'list'
        m.focusIndex = 'ghost'
        m.moveFocus(1)
        expect(m.focusIndex).toBe('ghost')
    })

    test('clamps at the last item in grid mode when no further page exists', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b')]
        m.perPage = 2
        m.viewMode = 'grid'
        m.focusIndex = 'b'
        m.next = vi.fn()
        m.moveFocus(1)
        expect(m.next).not.toHaveBeenCalled()
        expect(m.focusIndex).toBe('b')
    })

    test('crosses to the next page in grid mode when the current page is exhausted', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b'), item('c')]
        m.perPage = 2
        m.viewMode = 'grid'
        m.current = 0
        m.focusIndex = 'b'
        m.$nextTick = (cb) => cb()

        m.moveFocus(1)

        expect(m.current).toBe(1)
        expect(m.focusIndex).toBe('c')
    })
})

describe('menu.openFocused / openTopResult', () => {
    test('openFocused clicks the item matching focusIndex', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b')]
        m.viewMode = 'list'
        m.focusIndex = 'b'
        m.handleItemClick = vi.fn()

        m.openFocused()

        expect(m.handleItemClick).toHaveBeenCalledWith(m.items[1], expect.any(Object))
    })

    test('openTopResult clicks the first focusable item regardless of focusIndex', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b')]
        m.viewMode = 'list'
        m.handleItemClick = vi.fn()

        m.openTopResult()

        expect(m.handleItemClick).toHaveBeenCalledWith(m.items[0], expect.any(Object))
    })
})

describe('menu.handleSwipe', () => {
    test('below the 50px threshold nothing happens', () => {
        const m = menu()
        m.next = vi.fn()
        m.prev = vi.fn()
        m.touchStartX = 100
        m.touchEndX = 60
        m.handleSwipe()
        expect(m.next).not.toHaveBeenCalled()
        expect(m.prev).not.toHaveBeenCalled()
    })

    test('a leftward swipe (end < start) advances to the next page', () => {
        const m = menu()
        m.next = vi.fn()
        m.prev = vi.fn()
        m.touchStartX = 200
        m.touchEndX = 100
        m.handleSwipe()
        expect(m.next).toHaveBeenCalled()
    })

    test('a rightward swipe (end > start) goes to the previous page', () => {
        const m = menu()
        m.next = vi.fn()
        m.prev = vi.fn()
        m.touchStartX = 100
        m.touchEndX = 200
        m.handleSwipe()
        expect(m.prev).toHaveBeenCalled()
    })
})

describe('menu.handleGlobalKeydown', () => {
    test('is a no-op while the menu is closed', () => {
        const m = menu()
        m.closeMenu = vi.fn()
        m.handleGlobalKeydown({ key: 'Escape' })
        expect(m.closeMenu).not.toHaveBeenCalled()
    })

    test('Escape closes the menu', () => {
        const m = menu()
        m.menuOpen = true
        m.$refs = {}
        m.closeMenu = vi.fn()
        m.handleGlobalKeydown({ key: 'Escape' })
        expect(m.closeMenu).toHaveBeenCalledTimes(1)
    })

    test('"/" focuses the search input when not already focused there', () => {
        const m = menu()
        m.menuOpen = true
        const input = { focus: vi.fn() }
        Object.defineProperty(m, 'searchEl', { get: () => input })
        m.handleGlobalKeydown({ key: '/', preventDefault: vi.fn() })
        expect(input.focus).toHaveBeenCalled()
    })

    test('Enter inside the search box opens the top result', () => {
        const m = menu()
        m.menuOpen = true
        m.openTopResult = vi.fn()
        m.openFocused = vi.fn()
        Object.defineProperty(m, 'searchEl', { get: () => document.activeElement })

        m.handleGlobalKeydown({ key: 'Enter', preventDefault: vi.fn() })

        expect(m.openTopResult).toHaveBeenCalledTimes(1)
        expect(m.openFocused).not.toHaveBeenCalled()
    })

    test('Enter outside the search box opens the focused item', () => {
        const m = menu()
        m.menuOpen = true
        m.openTopResult = vi.fn()
        m.openFocused = vi.fn()
        Object.defineProperty(m, 'searchEl', { get: () => document.createElement('input') })

        m.handleGlobalKeydown({ key: 'Enter', preventDefault: vi.fn() })

        expect(m.openFocused).toHaveBeenCalledTimes(1)
        expect(m.openTopResult).not.toHaveBeenCalled()
    })

    test('typing a single printable key outside the search box starts a new search and focuses it', () => {
        const m = menu()
        m.menuOpen = true
        m.search = ''
        Object.defineProperty(m, 'searchEl', { get: () => null })
        m.$nextTick = (cb) => cb()

        m.handleGlobalKeydown({ key: 'p', preventDefault: vi.fn(), ctrlKey: false, metaKey: false, altKey: false })

        expect(m.search).toBe('p')
    })

    test('ArrowLeft/ArrowRight call moveFocus with RTL-reversed directions, and stop the keystroke from also reaching other global window listeners (fixed 2026-10-05 — was leaking through to the bottom navbar\'s tab-switcher underneath)', () => {
        const m = menu()
        m.menuOpen = true
        m.search = ''
        Object.defineProperty(m, 'searchEl', { get: () => null })
        m.moveFocus = vi.fn()

        const left = { key: 'ArrowLeft', stopImmediatePropagation: vi.fn() }
        m.handleGlobalKeydown(left)
        expect(m.moveFocus).toHaveBeenCalledWith(1)
        expect(left.stopImmediatePropagation).toHaveBeenCalledOnce()

        const right = { key: 'ArrowRight', stopImmediatePropagation: vi.fn() }
        m.handleGlobalKeydown(right)
        expect(m.moveFocus).toHaveBeenCalledWith(-1)
        expect(right.stopImmediatePropagation).toHaveBeenCalledOnce()
    })
})

describe('menu.toggleRecentOnly / toggleMenu / prev / next / updatePerPage', () => {
    test('toggleRecentOnly flips the flag and resets search/page/focus', () => {
        const m = menu()
        m.search = 'x'
        m.current = 3
        m.focusIndex = 'a'
        m.toggleRecentOnly()
        expect(m.showRecentOnly).toBe(true)
        expect(m.search).toBe('')
        expect(m.current).toBe(0)
        expect(m.focusIndex).toBeNull()
    })

    test('prev decrements but never below 0', () => {
        const m = menu()
        m.current = 0
        m.prev()
        expect(m.current).toBe(0)
        m.current = 2
        m.prev()
        expect(m.current).toBe(1)
    })

    test('next advances but never past the last page', () => {
        const m = menu()
        m.$store = { pinned: withPins([]) }
        m.items = [item('a'), item('b'), item('c')]
        m.perPage = 2
        m.current = 1
        m.next()
        expect(m.current).toBe(1)
        m.current = 0
        m.next()
        expect(m.current).toBe(1)
    })

    test('updatePerPage switches perPage on the given breakpoint match and resets to page 0 only on an actual change', () => {
        const m = menu()
        m.perPage = 8
        m.current = 2
        m.updatePerPage({ matches: false })
        expect(m.perPage).toBe(8)
        expect(m.current).toBe(2)

        m.updatePerPage({ matches: true })
        expect(m.perPage).toBe(12)
        expect(m.current).toBe(0)
    })

    test('toggleMenu opening recalculates perPage from the cached media query and restores the last-viewed page', () => {
        const m = menu()
        m._mql = { matches: true }
        m._pageForItem = vi.fn(() => 2)
        m._loadState = vi.fn(() => 'last-id')
        m.toggleMenu()
        expect(m.menuOpen).toBe(true)
        expect(m.perPage).toBe(12)
        expect(m.current).toBe(2)
    })
})

describe('menu.isPinned', () => {
    test('delegates to the pinned store for a valid item, and is false for one without an id', () => {
        const m = menu()
        m.$store = { pinned: withPins(['a']) }
        expect(m.isPinned(item('a'))).toBe(true)
        expect(m.isPinned(item('b'))).toBe(false)
        expect(m.isPinned(null)).toBe(false)
        expect(m.isPinned({})).toBe(false)
    })
})

describe('menu.toggleDeclutter', () => {
    test('flips the declutter state and persists it via _saveState', () => {
        const m = menu()
        m._saveState = vi.fn()
        m.toggleDeclutter()
        expect(m.declutter).toBe(true)
        expect(m._saveState).toHaveBeenCalledWith('menu_declutter', true)
        m.toggleDeclutter()
        expect(m.declutter).toBe(false)
        expect(m._saveState).toHaveBeenCalledWith('menu_declutter', false)
    })

    test('toggles the declutter class on the document root', () => {
        const m = menu()
        m._saveState = vi.fn()
        document.documentElement.classList.remove('declutter')
        m.toggleDeclutter()
        expect(document.documentElement.classList.contains('declutter')).toBe(true)
        m.toggleDeclutter()
        expect(document.documentElement.classList.contains('declutter')).toBe(false)
    })

    test('dispatches a declutter window event with detail.on matching the new state', () => {
        const m = menu()
        m._saveState = vi.fn()
        const events = []
        const listener = (e) => events.push(e)
        window.addEventListener('declutter', listener)
        m.toggleDeclutter()
        m.toggleDeclutter()
        window.removeEventListener('declutter', listener)
        expect(events).toHaveLength(2)
        expect(events[0].detail.on).toBe(true)
        expect(events[1].detail.on).toBe(false)
    })
})
