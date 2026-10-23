import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import initRecordFocus from '../record-focus.js'

initRecordFocus()

let handler

function registerHandler() {
    const on = vi.fn((event, cb) => {
        if (event === 'record-focus') handler = cb
    })
    window.Livewire = { on }
    document.dispatchEvent(new Event('livewire:init'))
    return on
}

beforeEach(() => {
    vi.restoreAllMocks()
    document.body.innerHTML = ''
    handler = undefined
    Element.prototype.scrollIntoView = vi.fn()
    vi.useFakeTimers()
    registerHandler()
})

afterEach(() => {
    vi.clearAllTimers()
    vi.useRealTimers()
    delete window.Livewire
})

describe('record-focus — payload normalization', () => {
    test('unwraps an array-wrapped payload and normalizes type to string, id to number', () => {
        const spy = vi.fn()
        window.addEventListener('record-focus', spy)

        handler([{ id: '7', type: 'project' }])

        expect(spy).toHaveBeenCalledTimes(1)
        expect(spy.mock.calls[0][0].detail).toEqual({ type: 'project', id: 7 })
        window.removeEventListener('record-focus', spy)
    })

    test('accepts a plain (non-array) payload object directly', () => {
        const spy = vi.fn()
        window.addEventListener('record-focus', spy)

        handler({ id: 3, type: 'task' })

        expect(spy).toHaveBeenCalledTimes(1)
        expect(spy.mock.calls[0][0].detail).toEqual({ type: 'task', id: 3 })
        window.removeEventListener('record-focus', spy)
    })

    test('an id of 0 is treated as valid — the == null guard only excludes null/undefined', () => {
        const spy = vi.fn()
        window.addEventListener('record-focus', spy)

        handler({ id: 0, type: 'task' })

        expect(spy).toHaveBeenCalledTimes(1)
        expect(spy.mock.calls[0][0].detail).toEqual({ type: 'task', id: 0 })
        window.removeEventListener('record-focus', spy)
    })
})

describe('record-focus — invalid payload guard', () => {
    test.each([
        [null],
        [[]],
        [{ type: 'project' }],
        [{ id: 1 }],
        [{ id: null, type: 'project' }],
        [{ id: undefined, type: 'project' }],
    ])('ignores an invalid payload %j without dispatching or throwing', (payload) => {
        const spy = vi.fn()
        window.addEventListener('record-focus', spy)

        expect(() => handler(payload)).not.toThrow()

        expect(spy).not.toHaveBeenCalled()
        window.removeEventListener('record-focus', spy)
    })
})

describe('record-focus — scrollToRecord retry-with-backoff', () => {
    test('finds the element immediately without any retry when it already exists', () => {
        const target = document.createElement('div')
        target.setAttribute('data-rf', 'task-5')
        document.body.appendChild(target)
        const spy = vi.spyOn(document, 'querySelector')

        handler({ id: 5, type: 'task' })

        expect(spy).toHaveBeenCalledTimes(1)
        expect(target.classList.contains('record-focus-flash')).toBe(true)
        expect(target.style.animation).toBe('none')
        expect(target.scrollIntoView).toHaveBeenCalledWith({ behavior: 'smooth', block: 'center' })
    })

    test('dispatches a bubbling programmatic-scroll event on the element before scrollIntoView', () => {
        const target = document.createElement('div')
        target.setAttribute('data-rf', 'task-5')
        document.body.appendChild(target)
        const order = []
        const spy = vi.fn(() => order.push('event'))
        document.body.addEventListener('programmatic-scroll', spy)
        target.scrollIntoView = vi.fn(() => order.push('scroll'))

        handler({ id: 5, type: 'task' })

        expect(spy).toHaveBeenCalledTimes(1)
        expect(order).toEqual(['event', 'scroll'])
        document.body.removeEventListener('programmatic-scroll', spy)
    })

    test('clears a stale .record-focus-flash class from a previous target before flashing the new one', () => {
        const stale = document.createElement('div')
        stale.className = 'record-focus-flash'
        document.body.appendChild(stale)
        const target = document.createElement('div')
        target.setAttribute('data-rf', 'task-9')
        document.body.appendChild(target)

        handler({ id: 9, type: 'task' })

        expect(stale.classList.contains('record-focus-flash')).toBe(false)
        expect(target.classList.contains('record-focus-flash')).toBe(true)
    })

    test('retries at RETRY_MS (75ms) intervals until a late-appearing element is found', () => {
        const spy = vi.spyOn(document, 'querySelector')

        handler({ id: 9, type: 'task' })
        expect(spy).toHaveBeenCalledTimes(1)

        vi.advanceTimersByTime(75)
        expect(spy).toHaveBeenCalledTimes(2)

        const target = document.createElement('div')
        target.setAttribute('data-rf', 'task-9')
        document.body.appendChild(target)

        vi.advanceTimersByTime(75)
        expect(spy).toHaveBeenCalledTimes(3)
        expect(target.classList.contains('record-focus-flash')).toBe(true)
    })

    test('stops retrying after MAX_ATTEMPTS (40) when the element never appears', () => {
        const spy = vi.spyOn(document, 'querySelector')

        handler({ id: 1, type: 'ghost' })
        expect(spy).toHaveBeenCalledTimes(1)

        vi.advanceTimersByTime(75 * 40)
        expect(spy).toHaveBeenCalledTimes(41)

        vi.advanceTimersByTime(75 * 5)
        expect(spy).toHaveBeenCalledTimes(41)
    })

    test('escapes a double quote in the computed selector via the no-CSS.escape fallback (jsdom lacks window.CSS)', () => {
        const spy = vi.spyOn(document, 'querySelector')

        handler({ id: 1, type: 'a"b' })

        const expectedKey = 'a"b-1'.replace(/"/g, '\\"')
        expect(spy).toHaveBeenCalledWith(`[data-rf="${expectedKey}"]`)
    })
})
