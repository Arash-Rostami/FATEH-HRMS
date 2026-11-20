import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import aiAssistant from '../aiAssistant.js'

const make = () => {
    const c = aiAssistant('https://ai.test/?user=1')
    c.$root = document.createElement('div')
    return c
}

beforeEach(() => vi.useFakeTimers())
afterEach(() => vi.useRealTimers())

describe('aiAssistant() handleEscape', () => {
    test('a press with the chat closed is left alone', () => {
        const c = make()
        c.open = false
        c.maximized = false
        const event = { preventDefault: vi.fn() }

        c.handleEscape(event)

        expect(event.preventDefault).not.toHaveBeenCalled()
    })

    test('a maximized chat is restored first and the press is consumed', () => {
        const c = make()
        c.open = true
        c.maximized = true
        c.close = vi.fn()
        const event = { preventDefault: vi.fn() }

        c.handleEscape(event)

        expect(event.preventDefault).toHaveBeenCalledOnce()
        expect(c.maximized).toBe(false)
        expect(c.close).not.toHaveBeenCalled()
    })

    test('an open chat closes and the press is consumed', () => {
        const c = make()
        c.open = true
        c.maximized = false
        c.close = vi.fn()
        const event = { preventDefault: vi.fn() }

        c.handleEscape(event)

        expect(event.preventDefault).toHaveBeenCalledOnce()
        expect(c.close).toHaveBeenCalledOnce()
    })
})

describe('aiAssistant() auto-hiding header', () => {
    test('opening the chat shows the header for a moment, then hides it', () => {
        const c = make()
        c.headerShown = false

        c.openChat()
        expect(c.open).toBe(true)
        expect(c.headerShown).toBe(true)

        vi.advanceTimersByTime(2499)
        expect(c.headerShown).toBe(true)
        vi.advanceTimersByTime(1)
        expect(c.headerShown).toBe(false)
    })

    test('hovering the strip shows the header and cancels a pending hide', () => {
        const c = make()
        c.hideHeaderSoon()
        vi.advanceTimersByTime(300)

        c.showHeader()
        vi.advanceTimersByTime(5000)

        expect(c.headerShown).toBe(true)
    })

    test('leaving the header hides it after the short delay', () => {
        const c = make()
        c.showHeader()

        c.hideHeaderSoon()
        vi.advanceTimersByTime(599)
        expect(c.headerShown).toBe(true)
        vi.advanceTimersByTime(1)
        expect(c.headerShown).toBe(false)
    })

    test('a tap on the strip toggles the header (touch has no hover)', () => {
        const c = make()
        c.headerShown = false

        c.toggleHeader()
        expect(c.headerShown).toBe(true)

        c.toggleHeader()
        vi.advanceTimersByTime(0)
        expect(c.headerShown).toBe(false)
    })

    test('moving focus or the pointer between header children does not hide it, leaving does', () => {
        const c = make()
        const header = document.createElement('div')
        const inside = document.createElement('button')
        const outside = document.createElement('button')
        header.appendChild(inside)

        c.showHeader()
        c.leaveHeader({ currentTarget: header, relatedTarget: inside })
        vi.advanceTimersByTime(5000)
        expect(c.headerShown).toBe(true)

        c.leaveHeader({ currentTarget: header, relatedTarget: outside })
        vi.advanceTimersByTime(600)
        expect(c.headerShown).toBe(false)

        c.showHeader()
        c.leaveHeader({ currentTarget: header, relatedTarget: null })
        vi.advanceTimersByTime(600)
        expect(c.headerShown).toBe(false)
    })

    test('closing the panel cancels the timer and resets the header for the next open', () => {
        const c = make()
        c.openChat()

        c.close()
        vi.advanceTimersByTime(5000)

        expect(c.open).toBe(false)
        expect(c.maximized).toBe(false)
        expect(c.headerShown).toBe(true)
    })
})
