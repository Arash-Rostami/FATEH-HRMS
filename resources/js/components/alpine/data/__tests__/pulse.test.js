import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import pulse from '../pulse.js'

let hidden = false
let dispatch

class FakeChannel {
    constructor(name) {
        this.name = name
        this.posted = []
        FakeChannel.last = this
    }

    postMessage(message) {
        this.posted.push(message)
    }

    close() {
        this.closed = true
    }
}

function setHidden(value) {
    hidden = value
    document.dispatchEvent(new Event('visibilitychange'))
}

function mockLocks() {
    const grants = []
    Object.defineProperty(navigator, 'locks', {
        configurable: true,
        value: {
            request: vi.fn((name, callback) => {
                const granted = callback()
                grants.push(granted)
                return granted
            }),
        },
    })
    return grants
}

const made = []

function make(wireResult = { events: ['reminder-changed'], fingerprint: { reminders: '2' } }) {
    const p = pulse({ reminders: '1' })
    p.$wire = { pulse: vi.fn(async () => wireResult) }
    made.push(p)
    return p
}

beforeEach(() => {
    hidden = false
    Object.defineProperty(document, 'hidden', { configurable: true, get: () => hidden })
    dispatch = vi.fn()
    window.Livewire = { dispatch }
    global.BroadcastChannel = FakeChannel
    FakeChannel.last = null
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
})

afterEach(() => {
    made.splice(0).forEach((p) => p.destroy())
    vi.useRealTimers()
    delete window.Livewire
    delete global.BroadcastChannel
    delete navigator.locks
})

describe('pulse leader election', () => {
    test('a visible tab asks for the shared lock and, once leader, polls with its own jitter around 70s', async () => {
        mockLocks()
        const p = make()
        p.init()

        expect(navigator.locks.request).toHaveBeenCalledWith('pulse-leader', expect.any(Function))
        expect(p.$wire.pulse).not.toHaveBeenCalled()

        await vi.advanceTimersByTimeAsync(62000)
        expect(p.$wire.pulse).not.toHaveBeenCalled()

        await vi.advanceTimersByTimeAsync(16000)
        expect(p.$wire.pulse).toHaveBeenCalledTimes(1)
        expect(p.$wire.pulse).toHaveBeenCalledWith({ reminders: '1' })
    })

    test('a hidden tab never asks for the lock', () => {
        hidden = true
        mockLocks()
        make().init()

        expect(navigator.locks.request).not.toHaveBeenCalled()
    })

    test('going hidden releases the lock and stops polling; becoming visible again re-requests it', async () => {
        const grants = mockLocks()
        const p = make()
        p.init()

        setHidden(true)
        await expect(grants[0]).resolves.toBeUndefined()
        await vi.advanceTimersByTimeAsync(200000)
        expect(p.$wire.pulse).not.toHaveBeenCalled()

        setHidden(false)
        expect(navigator.locks.request).toHaveBeenCalledTimes(2)
    })

    test('a tab that becomes leader long after the last check polls immediately', async () => {
        mockLocks()
        const p = make()
        p.init()
        p.lastAt = Date.now() - 200000
        p._standDown()
        p._lead()

        await vi.advanceTimersByTimeAsync(1)
        expect(p.$wire.pulse).toHaveBeenCalledTimes(1)
    })

    test('without the Web Locks API a visible tab leads on its own', async () => {
        const p = make()
        p.init()

        await vi.advanceTimersByTimeAsync(78000)
        expect(p.$wire.pulse).toHaveBeenCalledTimes(1)
    })
})

describe('pulse lock grants that arrive late', () => {
    function mockDeferredLocks() {
        const grants = []
        Object.defineProperty(navigator, 'locks', {
            configurable: true,
            value: {
                request: vi.fn((name, callback) => new Promise((resolve) => {
                    grants.push(() => resolve(callback()))
                })),
            },
        })
        return grants
    }

    test('visibility flips while the request is pending never create a second request, and the late grant makes the tab lead', () => {
        const grants = mockDeferredLocks()
        const p = make()
        p.init()

        setHidden(true)
        setHidden(false)
        expect(navigator.locks.request).toHaveBeenCalledTimes(1)

        grants[0]()
        expect(p._leading).toBe(true)
    })

    test('a grant that arrives while the tab is hidden is released at once and the tab requests again when shown', () => {
        const grants = mockDeferredLocks()
        const p = make()
        p.init()

        setHidden(true)
        grants[0]()
        expect(p._leading).toBe(false)

        setHidden(false)
        expect(navigator.locks.request).toHaveBeenCalledTimes(2)
    })
})

describe('pulse tick and fan-out', () => {
    test('the leader adopts the result, dispatches the events locally, broadcasts to the other tabs and reschedules', async () => {
        mockLocks()
        const p = make()
        p.init()

        await vi.advanceTimersByTimeAsync(78000)

        expect(p.fp).toEqual({ reminders: '2' })
        expect(dispatch).toHaveBeenCalledWith('reminder-changed')
        expect(FakeChannel.last.posted).toHaveLength(1)
        expect(FakeChannel.last.posted[0]).toMatchObject({ events: ['reminder-changed'], fingerprint: { reminders: '2' } })

        await vi.advanceTimersByTimeAsync(78000)
        expect(p.$wire.pulse).toHaveBeenCalledTimes(2)
        expect(p.$wire.pulse).toHaveBeenLastCalledWith({ reminders: '2' })
    })

    test('a failing request is swallowed and the next tick is still scheduled', async () => {
        mockLocks()
        const p = make()
        p.$wire.pulse = vi.fn().mockRejectedValueOnce(new Error('offline')).mockResolvedValue({ events: [], fingerprint: { reminders: '3' } })
        p.init()

        await vi.advanceTimersByTimeAsync(78000)
        expect(dispatch).not.toHaveBeenCalled()
        expect(FakeChannel.last.posted).toHaveLength(0)

        await vi.advanceTimersByTimeAsync(78000)
        expect(p.fp).toEqual({ reminders: '3' })
    })

    test('a follower applies a broadcast at once when visible: adopts the fingerprint and dispatches the events', () => {
        mockLocks()
        const p = make()
        p.init()

        FakeChannel.last.onmessage({ data: { events: ['edges-changed'], fingerprint: { edges: '9' }, at: Date.now() } })

        expect(p.fp).toEqual({ edges: '9' })
        expect(dispatch).toHaveBeenCalledWith('edges-changed')
    })

    test('a hidden follower queues the events and flushes each exactly once when the tab is shown', () => {
        mockLocks()
        const p = make()
        p.init()
        setHidden(true)

        FakeChannel.last.onmessage({ data: { events: ['edges-changed'], fingerprint: { edges: '9' }, at: Date.now() } })
        FakeChannel.last.onmessage({ data: { events: ['edges-changed', 'reminder-changed'], fingerprint: { edges: '10' }, at: Date.now() } })
        expect(dispatch).not.toHaveBeenCalled()

        setHidden(false)

        expect(dispatch.mock.calls.map((c) => c[0]).sort()).toEqual(['edges-changed', 'reminder-changed'])
        expect(p.fp).toEqual({ edges: '10' })
    })

    test('destroy stops the timer, releases the lock, closes the channel and ignores later visibility changes', async () => {
        const grants = mockLocks()
        const p = make()
        p.init()

        p.destroy()
        await expect(grants[0]).resolves.toBeUndefined()
        expect(FakeChannel.last.closed).toBe(true)

        setHidden(true)
        setHidden(false)
        expect(navigator.locks.request).toHaveBeenCalledTimes(1)
        await vi.advanceTimersByTimeAsync(200000)
        expect(p.$wire.pulse).not.toHaveBeenCalled()
    })
})
