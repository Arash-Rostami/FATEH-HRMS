import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import { calendarNow, calendarView, calendarDrag, calendarResize } from '../calendar.js'

beforeEach(() => {
    vi.useFakeTimers()
    global.Livewire = { dispatch: vi.fn() }
})

afterEach(() => {
    vi.useRealTimers()
    delete global.Livewire
})

function makeNow(startIso, spanHours) {
    const c = calendarNow({ startIso, spanHours })
    c.$nextTick = vi.fn()
    return c
}

describe('calendarNow — nowTop percentage within the start/span window', () => {
    test('2h into a 4h span is exactly the 50% mark', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 10, 0, 0))
        const c = makeNow(new Date(2026, 0, 1, 8, 0, 0).toISOString(), 4)
        c.init()
        expect(c.nowTop).toBeCloseTo(50)
        c.destroy()
    })

    test('before the window start sets nowTop to -1', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 6, 0, 0))
        const c = makeNow(new Date(2026, 0, 1, 8, 0, 0).toISOString(), 4)
        c.init()
        expect(c.nowTop).toBe(-1)
        c.destroy()
    })

    test('strictly after the window end sets nowTop to -1', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 13, 0, 0))
        const c = makeNow(new Date(2026, 0, 1, 8, 0, 0).toISOString(), 4)
        c.init()
        expect(c.nowTop).toBe(-1)
        c.destroy()
    })

    test('exactly at the window end boundary is still inside it (nowTop = 100)', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 12, 0, 0))
        const c = makeNow(new Date(2026, 0, 1, 8, 0, 0).toISOString(), 4)
        c.init()
        expect(c.nowTop).toBe(100)
        c.destroy()
    })

    test('spanHours defaults to 18 when not provided', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 17, 0, 0))
        const c = makeNow(new Date(2026, 0, 1, 8, 0, 0).toISOString(), undefined)
        c.init()
        expect(c.nowTop).toBeCloseTo((9 / 18) * 100)
        c.destroy()
    })

    test('an unparseable startIso leaves nowTop at -1 without throwing', () => {
        const c = makeNow('not-a-date', 4)
        expect(() => c.init()).not.toThrow()
        expect(c.nowTop).toBe(-1)
        c.destroy()
    })
})

describe('calendarNow — recompute on the 60s tick and day-change dispatch', () => {
    test('recomputes nowTop as time advances on the NOW_TICK_MS interval', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 8, 0, 0))
        const c = makeNow(new Date(2026, 0, 1, 8, 0, 0).toISOString(), 4)
        c.init()
        expect(c.nowTop).toBeCloseTo(0)

        vi.setSystemTime(new Date(2026, 0, 1, 10, 0, 0))
        vi.advanceTimersByTime(60000)

        expect(c.nowTop).toBeCloseTo(50, 0)
        c.destroy()
    })

    test('does not dispatch refresh-calendar on the very first compute', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 10, 0, 0))
        const c = makeNow(new Date(2026, 0, 1, 8, 0, 0).toISOString(), 4)
        c.init()
        expect(global.Livewire.dispatch).not.toHaveBeenCalled()
        c.destroy()
    })

    test('dispatches refresh-calendar once the local calendar day rolls over', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 10, 0, 0))
        const c = makeNow(new Date(2026, 0, 1, 8, 0, 0).toISOString(), 48)
        c.init()

        vi.setSystemTime(new Date(2026, 0, 2, 11, 0, 0))
        vi.advanceTimersByTime(60000)

        expect(global.Livewire.dispatch).toHaveBeenCalledWith('refresh-calendar')
        c.destroy()
    })

    test('destroy() stops further recomputation', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 8, 0, 0))
        const c = makeNow(new Date(2026, 0, 1, 8, 0, 0).toISOString(), 4)
        c.init()
        c.destroy()

        vi.setSystemTime(new Date(2026, 0, 1, 10, 0, 0))
        vi.advanceTimersByTime(120000)

        expect(c.nowTop).toBeCloseTo(0)
    })
})

describe('calendarView — scroll-memory persistence', () => {
    test('saveScroll is a no-op without a bound $root', () => {
        const c = calendarView()
        expect(() => c.saveScroll('month')).not.toThrow()
        expect(c.scrollMemory).toEqual({})
    })

    test('saveScroll records the current scrollTop under the given mode and persists it', () => {
        const c = calendarView()
        c.$root = { scrollTop: 240 }

        c.saveScroll('month')

        expect(c.scrollMemory.month).toBe(240)
        expect(JSON.parse(localStorage.getItem('calendar-scroll-memory')).month).toBe(240)
    })

    test('restoreScroll applies a remembered scrollTop back onto $root', () => {
        const c = calendarView()
        c.$root = { scrollTop: 0 }
        c.scrollMemory = { week: 88 }

        c.restoreScroll('week')

        expect(c.$root.scrollTop).toBe(88)
    })

    test('restoreScroll is a no-op for a mode with no remembered value', () => {
        const c = calendarView()
        c.$root = { scrollTop: 10 }
        c.scrollMemory = {}

        c.restoreScroll('month')

        expect(c.$root.scrollTop).toBe(10)
    })
})

function fire(target, type, props = {}) {
    const e = new Event(type, { bubbles: true, cancelable: true })
    Object.assign(e, { button: 0, clientY: 0, ...props })
    target.dispatchEvent(e)
}

function drag(root, fromY, toY) {
    fire(root, 'pointerdown', { clientY: fromY })
    fire(window, 'pointermove', { clientY: toY })
    fire(window, 'pointerup', { clientY: toY })
}

function makeEventRoot({ offsetTop = 300, hourHeight = 60 } = {}) {
    const day = document.createElement('div')
    day.setAttribute('data-date', '1403-01-01')
    document.body.appendChild(day)

    const root = document.createElement('div')
    root.dataset.hourHeight = String(hourHeight)
    Object.defineProperty(root, 'offsetTop', { value: offsetTop, configurable: true })
    Object.defineProperty(root, 'offsetLeft', { value: 0, configurable: true })
    Object.defineProperty(root, 'offsetWidth', { value: 120, configurable: true })
    day.appendChild(root)
    return root
}

const flush = async () => { for (let i = 0; i < 6; i++) await Promise.resolve() }

describe('calendarDrag — commitMove race-condition guard (fixed 2026-10-05)', () => {
    test('a stale drag response landing after a newer drag on the same event does not snap the position back or re-toast', async () => {
        const root = makeEventRoot()
        const c = calendarDrag({ eventId: 1, locked: false, isOwner: true })
        c.$root = root
        c.$dispatch = vi.fn()

        let resolveStale
        const calls = []
        c.$wire = {
            moveEvent: vi.fn((...args) => {
                calls.push(args)
                if (calls.length === 1) return new Promise((r) => { resolveStale = r })
                return Promise.resolve({ ok: true })
            }),
        }

        c.init()

        drag(root, 0, 60) // gesture 1: fires moveEvent, stays pending
        const topAfterFirst = root.style.top
        expect(topAfterFirst).not.toBe('')

        drag(root, 60, 140) // gesture 2: supersedes it, resolves immediately
        await flush()
        const topAfterSecond = root.style.top
        expect(topAfterSecond).not.toBe(topAfterFirst)
        expect(c.$wire.moveEvent).toHaveBeenCalledTimes(2)

        resolveStale({ ok: false, reason: 'conflict' }) // the stale (gesture 1) response finally lands, as a FAILURE
        await flush()

        expect(root.style.top).toBe(topAfterSecond) // must NOT have snapped back to gesture 1's revert position
        expect(c.$dispatch).toHaveBeenCalledTimes(1) // only gesture 2's success toast — no stale error toast
        expect(c.$dispatch).toHaveBeenCalledWith('toast', expect.objectContaining({ type: 'success' }))

        c.destroy()
    })

    test('without a superseding drag, a failed response still reverts the position and toasts the error (regression guard — the fix must not suppress a genuine single-gesture failure)', async () => {
        const root = makeEventRoot()
        const c = calendarDrag({ eventId: 2, locked: false, isOwner: true })
        c.$root = root
        c.$dispatch = vi.fn()
        c.$wire = { moveEvent: vi.fn(() => Promise.resolve({ ok: false, reason: 'conflict' })) }

        c.init()
        const originalTop = root.offsetTop

        drag(root, 0, 60)
        await flush()

        expect(root.style.top).toBe(`${originalTop}px`)
        expect(c.$dispatch).toHaveBeenCalledWith('toast', expect.objectContaining({ type: 'error' }))

        c.destroy()
    })
})

describe('calendarResize — commitResize race-condition guard (fixed 2026-10-05)', () => {
    function makeHandle(block) {
        const handle = document.createElement('div')
        block.appendChild(handle)
        return handle
    }

    function makeBlock({ offsetHeight = 60, hourHeight = 60 } = {}) {
        const block = document.createElement('div')
        block.setAttribute('data-event-id', '1')
        Object.defineProperty(block, 'offsetHeight', { value: offsetHeight, configurable: true })
        document.body.appendChild(block)
        const handle = makeHandle(block)
        return { block, handle, hourHeight }
    }

    test('a stale resize response landing after a newer resize on the same event does not snap the height back or re-toast', async () => {
        const { block, handle, hourHeight } = makeBlock()
        const c = calendarResize({ eventId: 1, startMinutes: 480, durationMinutes: 60, isOwner: true, locked: false })
        c.$root = handle
        c.$dispatch = vi.fn()

        let resolveStale
        const calls = []
        c.$wire = {
            resizeEvent: vi.fn((...args) => {
                calls.push(args)
                if (calls.length === 1) return new Promise((r) => { resolveStale = r })
                return Promise.resolve({ ok: true })
            }),
        }

        c.init()

        drag(handle, 0, hourHeight) // gesture 1: extend by 1h worth of px, resizeEvent pending
        drag(handle, hourHeight, hourHeight * 3) // gesture 2: supersedes it, resolves immediately
        await flush()

        expect(c.$wire.resizeEvent).toHaveBeenCalledTimes(2)
        const heightBeforeStaleResolves = block.style.height

        resolveStale({ ok: false, reason: 'conflict' }) // the stale (gesture 1) response finally lands, as a FAILURE
        await flush()

        // the stale failure must not touch the DOM at all once superseded — height is unchanged, no toast fired
        expect(block.style.height).toBe(heightBeforeStaleResolves)
        expect(c.$dispatch).not.toHaveBeenCalled()

        c.destroy()
    })

    test('without a superseding resize, a failed response still reverts the height and toasts the error (regression guard)', async () => {
        const { block, handle } = makeBlock()
        const c = calendarResize({ eventId: 2, startMinutes: 480, durationMinutes: 60, isOwner: true, locked: false })
        c.$root = handle
        c.$dispatch = vi.fn()
        c.$wire = { resizeEvent: vi.fn(() => Promise.resolve({ ok: false, reason: 'conflict' })) }

        c.init()
        const originalHeight = block.offsetHeight

        drag(handle, 0, 60)
        await flush()

        expect(block.style.height).toBe(`${originalHeight}px`)
        expect(c.$dispatch).toHaveBeenCalledWith('toast', expect.objectContaining({ type: 'error' }))

        c.destroy()
    })
})
