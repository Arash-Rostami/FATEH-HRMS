import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import { calendarNow, calendarView } from '../calendar.js'

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
