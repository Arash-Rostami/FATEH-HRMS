import { describe, expect, test, vi, beforeEach } from 'vitest'
import report from '../report.js'
import { installWireProbe } from '../../mixins/__tests__/_wireMock.js'

const RECENT_KEY = 'fateh_reports_recent'

beforeEach(() => {
    localStorage.clear()
    document.body.innerHTML = ''
    global.Livewire = { hook: vi.fn(() => vi.fn()) }
})

describe('report.recordOpen', () => {
    test('ignores an item with no id', () => {
        const c = report()
        c.recordOpen(null)
        c.recordOpen({})
        expect(c.recent).toEqual([])
    })

    test('unshifts a new item to the front', () => {
        const c = report()
        c.recordOpen({ id: 1, title: 'a' })
        c.recordOpen({ id: 2, title: 'b' })
        expect(c.recent.map(r => r.id)).toEqual([2, 1])
    })

    test('re-opening an already-recent item moves it to the front instead of duplicating it', () => {
        const c = report()
        c.recordOpen({ id: 1 })
        c.recordOpen({ id: 2 })
        c.recordOpen({ id: 1 })
        expect(c.recent.map(r => r.id)).toEqual([1, 2])
    })

    test('caps the list at RECENT_MAX (6), dropping the oldest', () => {
        const c = report()
        for (let i = 1; i <= 7; i++) c.recordOpen({ id: i })
        expect(c.recent).toHaveLength(6)
        expect(c.recent.map(r => r.id)).toEqual([7, 6, 5, 4, 3, 2])
    })

    test('persists the updated recent list to localStorage (microtask-deferred)', async () => {
        const c = report()
        c.recordOpen({ id: 1 })
        await Promise.resolve()
        expect(JSON.parse(localStorage.getItem(RECENT_KEY))).toEqual([{ id: 1, payload: { id: 1 } }])
    })
})

describe('report.openRecent / clearRecent', () => {
    test('openRecent unwraps the stored payload and opens the modal', () => {
        const c = report()
        c.openRecent({ id: 1, payload: { id: 1, title: 'x' } })
        expect(c.activeReport).toEqual({ id: 1, title: 'x' })
        expect(c.showModal).toBe(true)
    })

    test('openRecent falls back to the raw item when there is no payload wrapper', () => {
        const c = report()
        c.openRecent({ id: 1, title: 'y' })
        expect(c.activeReport).toEqual({ id: 1, title: 'y' })
    })

    test('clearRecent empties the list and removes it from localStorage', async () => {
        localStorage.setItem(RECENT_KEY, JSON.stringify([{ id: 1 }]))
        const c = report()
        c.recent = [{ id: 1 }]

        c.clearRecent()
        expect(c.recent).toEqual([])

        await Promise.resolve()
        expect(localStorage.getItem(RECENT_KEY)).toBeNull()
    })
})

describe('report.init — recent restoration (fixed 2026-10-01: now checks the parsed array length, not the raw JSON string length)', () => {
    function freshReport() {
        const c = report()
        c.$wire = { get: vi.fn(() => null) }
        c.$nextTick = vi.fn((fn) => fn())
        c.$refs = {}
        c.$root = document.createElement('div')
        return c
    }

    test('restores a non-trivial saved JSON array', () => {
        localStorage.setItem(RECENT_KEY, JSON.stringify([{ id: 1 }]))
        const c = freshReport()
        c.init()
        expect(c.recent).toEqual([{ id: 1 }])
    })

    test('an empty saved array ("[]") restores to an empty list, correctly, via the parsed array length — not a coincidence of raw string length', () => {
        localStorage.setItem(RECENT_KEY, '[]')
        const c = freshReport()
        c.init()
        expect(c.recent).toEqual([])
    })

    test('corrupt JSON is caught and resets recent to []', () => {
        localStorage.setItem(RECENT_KEY, '{not json')
        const c = freshReport()
        expect(() => c.init()).not.toThrow()
        expect(c.recent).toEqual([])
    })
})

describe('report.init — mobile view enforcement', () => {
    function freshReport(view) {
        const c = report()
        c.$wire = { get: vi.fn(() => view), call: vi.fn() }
        c.$nextTick = vi.fn((fn) => fn())
        c.$refs = {}
        c.$root = document.createElement('div')
        return c
    }

    test('below the mobile breakpoint, forces view to "list" and tells the server', () => {
        Object.defineProperty(window, 'innerWidth', { configurable: true, value: 500 })
        const c = freshReport('grid')
        c.init()
        expect(c.view).toBe('list')
        expect(c.$wire.call).toHaveBeenCalledWith('toggleView', 'list')
    })

    test('at/above the breakpoint, the server view is left untouched', () => {
        Object.defineProperty(window, 'innerWidth', { configurable: true, value: 1024 })
        const c = freshReport('grid')
        c.init()
        expect(c.view).toBe('grid')
        expect(c.$wire.call).not.toHaveBeenCalled()
    })
})

describe('report.destroy', () => {
    test('tears down the resize listener, timers, observer, and morph hook without throwing', () => {
        const c = report()
        c.$wire = { get: vi.fn(() => 'list') }
        c.$nextTick = vi.fn((fn) => fn())
        c.$refs = {}
        c.$root = document.createElement('div')

        c.init()
        expect(() => c.destroy()).not.toThrow()
        expect(c._isDestroyed).toBe(true)
        expect(c._enforce).toBeNull()
        expect(c._observer).toBeNull()
    })
})

describe('report.loadMore', () => {
    test('is a re-entrancy guard: a second call while already loading is a no-op', async () => {
        const c = report()
        c.loading = true
        c.$wire = { loadMore: vi.fn(() => Promise.resolve()) }
        await c.loadMore()
        expect(c.$wire.loadMore).not.toHaveBeenCalled()
    })

    test('captures $wire before the await and does not re-read it afterwards', async () => {
        const c = report()
        const wire = installWireProbe(c, { loadMore: vi.fn(() => Promise.resolve()) }, 1)
        await c.loadMore()
        expect(wire.loadMore).toHaveBeenCalled()
        expect(c.loading).toBe(false)
    })

    test('a rejected $wire.loadMore() is swallowed and loading still resets to false', async () => {
        const c = report()
        c.$wire = { loadMore: vi.fn(() => Promise.reject(new Error('fail'))) }
        await c.loadMore()
        expect(c.loading).toBe(false)
    })
})

describe('report.updateActiveItem', () => {
    function rect(right) {
        return { top: 0, right, width: 0, height: 0, left: 0, bottom: 0 }
    }

    test('no-ops when either ref is missing', () => {
        const c = report()
        c.$refs = {}
        expect(() => c.updateActiveItem()).not.toThrow()
        expect(c.activeId).toBeNull()
    })

    test('picks the item whose right edge is closest to the 90% reference point of the viewport', () => {
        const c = report()
        const viewport = document.createElement('div')
        vi.spyOn(viewport, 'getBoundingClientRect').mockReturnValue({ top: 0, right: 1000, width: 1000, left: 0, bottom: 0, height: 0 })

        const container = document.createElement('div')
        const a = document.createElement('div')
        a.setAttribute('data-report-id', 'a')
        vi.spyOn(a, 'getBoundingClientRect').mockReturnValue(rect(500))
        const b = document.createElement('div')
        b.setAttribute('data-report-id', 'b')
        vi.spyOn(b, 'getBoundingClientRect').mockReturnValue(rect(895))
        container.append(a, b)

        c.$refs = { reportContainer: container, timeline: viewport }
        c.updateActiveItem()

        expect(c.activeId).toBe('b')
    })
})

describe('report.setupFilterCompact — scroll-driven compact toggling', () => {
    function flushRaf() {
        return new Promise((resolve) => setTimeout(resolve, 0))
    }

    function dom() {
        document.body.innerHTML = `
            <div class="overflow-y-auto" id="scroller">
                <div id="root"><div x-data="filters"></div></div>
            </div>`
        return { scroller: document.getElementById('scroller'), root: document.getElementById('root') }
    }

    beforeEach(() => {
        global.requestAnimationFrame = (cb) => setTimeout(cb, 0)
    })

    test('does nothing (no throw) when $root has no scrollable ancestor', () => {
        const c = report()
        c.$root = document.createElement('div')
        expect(() => c.setupFilterCompact()).not.toThrow()
    })

    test('does nothing (no throw) when no filters element is found inside $root', () => {
        document.body.innerHTML = `<div class="overflow-y-auto"><div id="root"></div></div>`
        const c = report()
        c.$root = document.getElementById('root')
        expect(() => c.setupFilterCompact()).not.toThrow()
    })

    test('scrolling past the top threshold with a downward delta collapses the filter bar', async () => {
        const { scroller, root } = dom()
        const data = { compact: false, showFilters: true }
        global.Alpine = { $data: vi.fn(() => data) }

        const c = report()
        c.$root = root
        c.setupFilterCompact()

        scroller.scrollTop = 50
        scroller.dispatchEvent(new Event('scroll'))
        await flushRaf()

        expect(data.compact).toBe(true)
        expect(data.showFilters).toBe(false)
    })

    test('scrolling back near the top re-expands the filter bar regardless of delta', async () => {
        const { scroller, root } = dom()
        const data = { compact: true, showFilters: false }
        global.Alpine = { $data: vi.fn(() => data) }

        const c = report()
        c.$root = root
        c.setupFilterCompact()

        scroller.scrollTop = 10
        scroller.dispatchEvent(new Event('scroll'))
        await flushRaf()

        expect(data.compact).toBe(false)
    })

    test('a small delta that crosses neither threshold leaves compact state untouched', async () => {
        const { scroller, root } = dom()
        const data = { compact: true, showFilters: false }
        global.Alpine = { $data: vi.fn(() => data) }

        const c = report()
        c.$root = root
        c.setupFilterCompact()

        scroller.scrollTop = 100
        scroller.dispatchEvent(new Event('scroll'))
        await flushRaf()

        scroller.scrollTop = 105
        scroller.dispatchEvent(new Event('scroll'))
        await flushRaf()

        expect(data.compact).toBe(true)
        expect(data.showFilters).toBe(false)
    })
})

describe('report.setupScrollListener', () => {
    test('wires a rAF-throttled scroll handler on $refs.timeline that calls updateActiveItem', async () => {
        global.requestAnimationFrame = (cb) => setTimeout(cb, 0)
        const c = report()
        const container = document.createElement('div')
        c.$refs = { timeline: container }
        c.updateActiveItem = vi.fn()

        c.setupScrollListener()
        container.dispatchEvent(new Event('scroll'))
        await new Promise((resolve) => setTimeout(resolve, 0))

        expect(c.updateActiveItem).toHaveBeenCalled()
    })

    test('is a no-op when $refs.timeline is missing', () => {
        const c = report()
        c.$refs = {}
        expect(() => c.setupScrollListener()).not.toThrow()
    })
})
