import { describe, expect, test, vi, afterEach } from 'vitest'
import reservationTimeRail from '../reservationTimeRail.js'

afterEach(() => {
    vi.unstubAllGlobals()
})

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

describe('reservationTimeRail drag never leaves startIdx >= endIdx', () => {
    function dragRail(start, end) {
        const r = make([], -1, start, end)
        r.$refs = { rail: { getBoundingClientRect: () => ({ left: 0, right: 600, width: 600 }) } }
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }
        vi.stubGlobal('requestAnimationFrame', (fn) => { fn(); return 1 })
        vi.stubGlobal('cancelAnimationFrame', vi.fn())
        return r
    }

    const clientXFor = (idx) => 600 - (idx / 6) * 600

    test('dragging start past the end handle stops one slot short', () => {
        const r = dragRail(1, 3)
        r.onDown('start')
        for (const idx of [2, 4, 6]) {
            r.onMove({ clientX: clientXFor(idx), cancelable: false })
            r._raf = null
            expect(r.startIdx).toBeLessThan(r.endIdx)
        }
        r.onUp()
        expect(r.startIdx).toBe(2)
        expect(r.$wire.setStartTime).toHaveBeenCalledWith(SLOTS[2])
    })

    test('dragging end past the start handle stops one slot short', () => {
        const r = dragRail(2, 5)
        r.onDown('end')
        for (const idx of [3, 1, 0]) {
            r.onMove({ clientX: clientXFor(idx), cancelable: false })
            r._raf = null
            expect(r.startIdx).toBeLessThan(r.endIdx)
        }
        r.onUp()
        expect(r.endIdx).toBe(3)
        expect(r.$wire.setEndTime).toHaveBeenCalledWith(SLOTS[3])
    })

    test('a track click beyond a collapsed pair never leaves start at or past end and never sends an equal pair', () => {
        const r = dragRail(2, 2)
        r.trackDown({ clientX: clientXFor(4) })
        r.onUp()

        expect(r.startIdx).toBeLessThan(r.endIdx)
        expect(r.startIdx).toBe(1)
        expect(r.$wire.setStartTime).toHaveBeenCalledWith(SLOTS[1])
        expect(r.$wire.setEndTime).not.toHaveBeenCalled()
    })
})

describe('reservationTimeRail.init server sync', () => {
    function synced(server, start = 0, end = 2) {
        const cbs = {}
        const r = make([], -1, start, end)
        r.$wire = { get: vi.fn((k) => server[k]), $watch: vi.fn((k, cb) => { cbs[k] = cb }) }
        r.init()
        return { r, cbs }
    }

    test('registers a watcher for both startTime and endTime', () => {
        const { cbs } = synced({})
        expect(Object.keys(cbs).sort()).toEqual(['endTime', 'startTime'])
    })

    test('a server change of both values updates both indexes', () => {
        const server = { startTime: '09:30', endTime: '11:00' }
        const { r, cbs } = synced(server)
        cbs.startTime()
        expect(r.startIdx).toBe(3)
        expect(r.endIdx).toBe(6)
    })

    test('nothing changes while a handle is being dragged', () => {
        const { r, cbs } = synced({ startTime: '09:30', endTime: '11:00' })
        r.dragging = 'start'
        cbs.endTime()
        expect(r.startIdx).toBe(0)
        expect(r.endIdx).toBe(2)
    })

    test('values that are not rail slots leave the indexes untouched', () => {
        const { r, cbs } = synced({ startTime: '19:00', endTime: '11:00' })
        cbs.startTime()
        expect(r.startIdx).toBe(0)
        expect(r.endIdx).toBe(2)
    })

    test('a server start at or after the end is ignored', () => {
        const { r, cbs } = synced({ startTime: '10:00', endTime: '10:00' })
        cbs.startTime()
        expect(r.startIdx).toBe(0)
        expect(r.endIdx).toBe(2)

        const server = { startTime: '11:00', endTime: '09:00' }
        const second = synced(server)
        second.cbs.endTime()
        expect(second.r.startIdx).toBe(0)
        expect(second.r.endIdx).toBe(2)
    })

    test('init is a no-op without a $watch-capable $wire', () => {
        const r = make()
        r.$wire = {}
        expect(() => r.init()).not.toThrow()
        expect(() => r.destroy()).not.toThrow()
    })

    test('destroy calls every stored unwatch function', () => {
        const unwatchers = []
        const r = make()
        r.$wire = { get: vi.fn(), $watch: vi.fn(() => { const u = vi.fn(); unwatchers.push(u); return u }) }
        r.init()
        r.destroy()
        expect(unwatchers).toHaveLength(2)
        unwatchers.forEach((u) => expect(u).toHaveBeenCalledTimes(1))
    })
})

describe('reservationTimeRail pending requests', () => {
    const flush = () => new Promise((res) => setTimeout(res, 0))

    function pendingRail(server) {
        const cbs = {}
        const resolvers = []
        const r = make([], -1, 0, 2)
        r.$wire = {
            get: vi.fn((k) => server[k]),
            $watch: vi.fn((k, cb) => { cbs[k] = cb; return vi.fn() }),
            setStartTime: vi.fn(() => new Promise((res) => resolvers.push(res))),
            setEndTime: vi.fn(() => new Promise((res) => resolvers.push(res))),
        }
        r.init()
        return { r, cbs, resolvers }
    }

    test('a rejected request does not throw, clears pending and still re-syncs', async () => {
        const server = { startTime: '08:00', endTime: '09:00' }
        const { r } = pendingRail(server)
        r.$wire.setStartTime = vi.fn(() => Promise.reject(new Error('boom')))
        r.nudge('start', 1)
        expect(r._pending).toBe(1)
        expect(r.startIdx).toBe(1)
        await flush()
        expect(r._pending).toBe(0)
        expect(r.startIdx).toBe(0)
        expect(r.$wire.get).toHaveBeenCalled()
    })

    test('a watch callback does not move the indexes while a request is pending', () => {
        const server = { startTime: '08:00', endTime: '10:00' }
        const { r, cbs } = pendingRail(server)
        r.nudge('end', 1)
        expect(r._pending).toBe(1)
        server.endTime = '08:30'
        cbs.endTime()
        expect(r.endIdx).toBe(3)
    })

    test('after the request settles the indexes follow the server values', async () => {
        const server = { startTime: '08:00', endTime: '09:00' }
        const { r, resolvers } = pendingRail(server)
        r.nudge('end', 1)
        server.endTime = '10:00'
        resolvers[0]()
        await flush()
        expect(r._pending).toBe(0)
        expect(r.endIdx).toBe(4)
    })

    test('a refused change is restored to the server value once it settles', async () => {
        const server = { startTime: '08:00', endTime: '09:00' }
        const { r, resolvers } = pendingRail(server)
        r.nudge('end', -1)
        expect(r.endIdx).toBe(1)
        resolvers[0]()
        await flush()
        expect(r.endIdx).toBe(2)
    })

    test('two quick nudges only sync once both have settled', async () => {
        const server = { startTime: '08:00', endTime: '09:00' }
        const { r, resolvers } = pendingRail(server)
        r.nudge('end', 1)
        r.nudge('end', 1)
        expect(r.endIdx).toBe(4)
        server.endTime = '09:30'
        resolvers[0]()
        await flush()
        expect(r._pending).toBe(1)
        expect(r.endIdx).toBe(4)
        resolvers[1]()
        await flush()
        expect(r.endIdx).toBe(3)
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
    test('nudging start clamps at 0 and stops one slot before the end handle', () => {
        const r = make([], -1, 0, 2)
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }

        r.nudge('start', -1)
        expect(r.startIdx).toBe(0)
        expect(r.$wire.setStartTime).not.toHaveBeenCalled()

        r.nudge('start', 5)
        expect(r.startIdx).toBe(1)
        expect(r.$wire.setStartTime).toHaveBeenCalledWith(SLOTS[1])
    })

    test('nudging end clamps at the rail length and stops one slot after the start handle', () => {
        const r = make([], -1, 4, 6)
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }

        r.nudge('end', 2)
        expect(r.endIdx).toBe(6)
        expect(r.$wire.setEndTime).not.toHaveBeenCalled()

        r.nudge('end', -5)
        expect(r.endIdx).toBe(5)
        expect(r.$wire.setEndTime).toHaveBeenCalledWith(SLOTS[5])
    })

    test('nudging start far right stops at endIdx - 1 and persists that slot', () => {
        const r = make([], -1, 1, 4)
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }

        r.nudge('start', 99)

        expect(r.startIdx).toBe(3)
        expect(r.$wire.setStartTime).toHaveBeenCalledWith(SLOTS[3])
    })

    test('nudging end far left stops at startIdx + 1 and persists that slot', () => {
        const r = make([], -1, 2, 5)
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }

        r.nudge('end', -99)

        expect(r.endIdx).toBe(3)
        expect(r.$wire.setEndTime).toHaveBeenCalledWith(SLOTS[3])
    })

    test('a 1-slot rail never calls $wire', () => {
        const r = reservationTimeRail(['08:00'], [], -1, 0, 0)
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }

        r.nudge('start', 1)
        r.nudge('end', 1)
        r.nudge('end', -1)
        r.dragging = 'end'
        r.onUp()

        expect(r.startIdx).toBe(0)
        expect(r.endIdx).toBe(0)
        expect(r.$wire.setStartTime).not.toHaveBeenCalled()
        expect(r.$wire.setEndTime).not.toHaveBeenCalled()
    })

    test('start at the last index: nudging end right does not call setEndTime', () => {
        const r = make([], -1, 6, 6)
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }

        r.nudge('end', 1)

        expect(r.endIdx).toBe(6)
        expect(r.$wire.setEndTime).not.toHaveBeenCalled()
    })

    test('end nudged left onto startIdx + 1 still persists, and start nudged right onto endIdx - 1 still persists', () => {
        const r = make([], -1, 2, 4)
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }

        r.nudge('end', -1)
        expect(r.endIdx).toBe(3)
        expect(r.$wire.setEndTime).toHaveBeenCalledTimes(1)
        expect(r.$wire.setEndTime).toHaveBeenCalledWith(SLOTS[3])

        r.nudge('end', -1)
        expect(r.endIdx).toBe(3)
        expect(r.$wire.setEndTime).toHaveBeenCalledTimes(1)

        const s = make([], -1, 1, 3)
        s.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }
        s.nudge('start', 1)
        expect(s.startIdx).toBe(2)
        expect(s.$wire.setStartTime).toHaveBeenCalledWith(SLOTS[2])
        s.nudge('start', 1)
        expect(s.startIdx).toBe(2)
        expect(s.$wire.setStartTime).toHaveBeenCalledTimes(1)
    })

    test('a no-op nudge (index unchanged) does not call $wire', () => {
        const r = make([], -1, 2, 3)
        r.$wire = { setStartTime: vi.fn(), setEndTime: vi.fn() }
        r.nudge('end', 0)
        expect(r.$wire.setEndTime).not.toHaveBeenCalled()
    })
})
