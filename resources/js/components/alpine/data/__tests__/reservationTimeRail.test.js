import { describe, expect, test, vi } from 'vitest'
import reservationTimeRail from '../reservationTimeRail.js'

const SLOTS = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00']

function make(busyIdx = [], nowIdx = -1, startIdx = 0, endIdx = 2) {
    return reservationTimeRail(SLOTS, busyIdx, nowIdx, startIdx, endIdx)
}

describe('reservationTimeRail._len / pct', () => {
    test('_len is slots.length - 1, and falls back to 1 for a single-slot rail', () => {
        const r = make()
        expect(r._len).toBe(6)
        const single = reservationTimeRail(['08:00'], [], -1, 0, 0)
        expect(single._len).toBe(1)
    })

    test('pct maps an index to a 0-100 percentage of the rail', () => {
        const r = make()
        expect(r.pct(0)).toBe(0)
        expect(r.pct(6)).toBe(100)
        expect(r.pct(3)).toBe(50)
    })
})

describe('reservationTimeRail.fa / mins', () => {
    test('fa converts digits to Persian', () => {
        expect(make().fa(12)).toBe('۱۲')
    })

    test('mins parses an HH:MM slot label into minutes-from-midnight, 0 for an empty label', () => {
        const r = make()
        expect(r.mins('09:30')).toBe(570)
        expect(r.mins('')).toBe(0)
    })
})

describe('reservationTimeRail.duration', () => {
    test('renders hours-only, minutes-only, hours+minutes, and zero', () => {
        const r = make([], -1, 0, 4)
        expect(r.duration()).toBe('۲ ساعت')

        r.startIdx = 0; r.endIdx = 2
        expect(r.duration()).toBe('۱ ساعت')

        r.startIdx = 0; r.endIdx = 1
        expect(r.duration()).toBe('۳۰ دقیقه')

        r.startIdx = 2; r.endIdx = 2
        expect(r.duration()).toBe('۰ دقیقه')
    })

    test('never reports a negative duration when end precedes start', () => {
        const r = make([], -1, 4, 1)
        expect(r.duration()).toBe('۰ دقیقه')
    })
})

describe('reservationTimeRail.conflict', () => {
    test('overlapping ranges conflict with a busy range', () => {
        const r = make([[2, 4]], -1, 1, 3)
        expect(r.conflict()).toBe(true)
    })

    test('a range that only touches a busy boundary does not conflict', () => {
        const touchLeft = make([[2, 4]], -1, 0, 2)
        expect(touchLeft.conflict()).toBe(false)

        const touchRight = make([[2, 4]], -1, 4, 6)
        expect(touchRight.conflict()).toBe(false)
    })

    test('a disjoint range does not conflict', () => {
        const r = make([[2, 4]], -1, 0, 1)
        expect(r.conflict()).toBe(false)
    })

    test('an empty busy list never conflicts', () => {
        expect(make([], -1, 0, 6).conflict()).toBe(false)
    })
})

describe('reservationTimeRail._getClientX / _getRatio', () => {
    test('_getClientX prefers a touch point clientX over the mouse clientX', () => {
        const r = make()
        expect(r._getClientX({ clientX: 10 })).toBe(10)
        expect(r._getClientX({ clientX: 10, touches: [{ clientX: 99 }] })).toBe(99)
        expect(r._getClientX({ clientX: 10, touches: [] })).toBe(10)
    })

    test('_getRatio is RTL: distance-from-right over width, clamped to [0,1], and caches the rail rect', () => {
        const r = make()
        const getBoundingClientRect = vi.fn(() => ({ left: 0, right: 100, width: 100 }))
        r.$refs = { rail: { getBoundingClientRect } }

        expect(r._getRatio(100)).toBe(0)
        expect(r._getRatio(0)).toBe(1)
        expect(r._getRatio(50)).toBeCloseTo(0.5)
        expect(r._getRatio(-50)).toBe(1)
        expect(r._getRatio(150)).toBe(0)
        expect(getBoundingClientRect).toHaveBeenCalledTimes(1)
    })

    test('_getRatio returns 0 for a zero-width rail instead of dividing by zero', () => {
        const r = make()
        r.$refs = { rail: { getBoundingClientRect: () => ({ left: 0, right: 0, width: 0 }) } }
        expect(r._getRatio(0)).toBe(0)
    })
})

describe('reservationTimeRail.trackDown — click-to-nearest-handle', () => {
    function withRail(r, right) {
        r.$refs = { rail: { getBoundingClientRect: () => ({ left: 0, right, width: right }) } }
    }

    test('a click closer to the start handle moves the start handle (clamped to not cross end)', () => {
        const r = make([], -1, 0, 4)
        withRail(r, 600)
        r.trackDown({ clientX: 600 - (1 / 6) * 600 })
        expect(r.dragging).toBe('start')
        expect(r.startIdx).toBe(1)
        expect(r.endIdx).toBe(4)
    })

    test('a click closer to the end handle moves the end handle (clamped to not cross start)', () => {
        const r = make([], -1, 0, 4)
        withRail(r, 600)
        r.trackDown({ clientX: 600 - (5 / 6) * 600 })
        expect(r.dragging).toBe('end')
        expect(r.endIdx).toBe(5)
        expect(r.startIdx).toBe(0)
    })
})

describe('reservationTimeRail.onUp', () => {
    test('persists the start time via $wire when the start handle was being dragged', () => {
        const r = make([], -1, 2, 4)
        r.dragging = 'start'
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }

        r.onUp()

        expect(r.$wire.setStartTime).toHaveBeenCalledWith(SLOTS[2])
        expect(r.$wire.setEndTime).not.toHaveBeenCalled()
        expect(r.dragging).toBeNull()
    })

    test('persists the end time via $wire when the end handle was being dragged', () => {
        const r = make([], -1, 2, 4)
        r.dragging = 'end'
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }

        r.onUp()

        expect(r.$wire.setEndTime).toHaveBeenCalledWith(SLOTS[4])
        expect(r.$wire.setStartTime).not.toHaveBeenCalled()
    })

    test('is a no-op when nothing is being dragged', () => {
        const r = make()
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }
        r.onUp()
        expect(r.$wire.setStartTime).not.toHaveBeenCalled()
        expect(r.$wire.setEndTime).not.toHaveBeenCalled()
    })
})

describe('reservationTimeRail.nudge', () => {
    test('nudging start clamps at 0 and cannot cross the end handle', () => {
        const r = make([], -1, 0, 2)
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }

        r.nudge('start', -1)
        expect(r.startIdx).toBe(0)
        expect(r.$wire.setStartTime).not.toHaveBeenCalled()

        r.nudge('start', 5)
        expect(r.startIdx).toBe(2)
        expect(r.$wire.setStartTime).toHaveBeenCalledWith(SLOTS[2])
    })

    test('nudging end clamps at the rail length and cannot cross the start handle', () => {
        const r = make([], -1, 4, 6)
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }

        r.nudge('end', 2)
        expect(r.endIdx).toBe(6)
        expect(r.$wire.setEndTime).not.toHaveBeenCalled()

        r.nudge('end', -5)
        expect(r.endIdx).toBe(4)
        expect(r.$wire.setEndTime).toHaveBeenCalledWith(SLOTS[4])
    })

    test('a no-op nudge (index unchanged) does not call $wire', () => {
        const r = make([], -1, 2, 2)
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }
        r.nudge('end', 0)
        expect(r.$wire.setEndTime).not.toHaveBeenCalled()
    })
})
