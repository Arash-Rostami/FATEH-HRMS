import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import focusStore from '../focus.js'

function freshStore() {
    let def = null
    focusStore({ store: (name, d) => { def = d } })
    return def
}

beforeEach(() => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-01-01T00:00:00.000Z'))
})

afterEach(() => {
    vi.useRealTimers()
})

describe('focus.schedule', () => {
    test('a falsy endsAt clears any pending timer and sets _timerId to null without dispatching', () => {
        const s = freshStore()
        const handler = vi.fn()
        window.addEventListener('focus-mode-expired', handler)

        s.schedule(Date.now() + 1000)
        s.schedule(null)

        expect(s._timerId).toBeNull()
        vi.advanceTimersByTime(5000)
        expect(handler).not.toHaveBeenCalled()
        window.removeEventListener('focus-mode-expired', handler)
    })

    test('a future endsAt dispatches focus-mode-expired after exactly the remaining delay', () => {
        const s = freshStore()
        const handler = vi.fn()
        window.addEventListener('focus-mode-expired', handler)

        s.schedule(Date.now() + 5000)

        vi.advanceTimersByTime(4999)
        expect(handler).not.toHaveBeenCalled()

        vi.advanceTimersByTime(1)
        expect(handler).toHaveBeenCalledTimes(1)
        expect(s._timerId).toBeNull()
        window.removeEventListener('focus-mode-expired', handler)
    })

    test('an endsAt already in the past fires immediately (delay clamped to 0)', () => {
        const s = freshStore()
        const handler = vi.fn()
        window.addEventListener('focus-mode-expired', handler)

        s.schedule(Date.now() - 1000)

        vi.advanceTimersByTime(0)
        expect(handler).toHaveBeenCalledTimes(1)
        window.removeEventListener('focus-mode-expired', handler)
    })

    test('calling schedule again cancels the previously pending timer, preventing a double-fire', () => {
        const s = freshStore()
        const handler = vi.fn()
        window.addEventListener('focus-mode-expired', handler)

        s.schedule(Date.now() + 1000)
        s.schedule(Date.now() + 3000)

        vi.advanceTimersByTime(1000)
        expect(handler).not.toHaveBeenCalled()

        vi.advanceTimersByTime(2000)
        expect(handler).toHaveBeenCalledTimes(1)
        window.removeEventListener('focus-mode-expired', handler)
    })
})

describe('focus.clear', () => {
    test('cancels a pending schedule, no event ever fires', () => {
        const s = freshStore()
        const handler = vi.fn()
        window.addEventListener('focus-mode-expired', handler)

        s.schedule(Date.now() + 1000)
        s.clear()

        expect(s._timerId).toBeNull()
        vi.advanceTimersByTime(5000)
        expect(handler).not.toHaveBeenCalled()
        window.removeEventListener('focus-mode-expired', handler)
    })

    test('is a no-op when there is no pending timer', () => {
        const s = freshStore()
        expect(() => s.clear()).not.toThrow()
        expect(s._timerId).toBeNull()
    })
})
