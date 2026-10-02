import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import countdown from '../countdown.js'

function withMatchMedia(matches = false) {
    window.matchMedia = vi.fn(() => ({ matches }))
}

beforeEach(() => {
    withMatchMedia(false)
})

afterEach(() => {
    vi.useRealTimers()
    vi.restoreAllMocks()
})

describe('countdown.fa — Persian digit rendering', () => {
    test('pads single digits to length 2 and maps 0-9 to Persian glyphs', () => {
        const c = countdown({ dateIso: new Date(Date.now() + 100000).toISOString(), confetti: false })
        expect(c.fa(0)).toBe('۰۰')
        expect(c.fa(5)).toBe('۰۵')
    })

    test('does not truncate values with more than 2 digits', () => {
        const c = countdown({ dateIso: new Date(Date.now() + 100000).toISOString(), confetti: false })
        expect(c.fa(100)).toBe('۱۰۰')
    })

    test('treats a falsy/undefined value as 0', () => {
        const c = countdown({ dateIso: new Date(Date.now() + 100000).toISOString(), confetti: false })
        expect(c.fa(undefined)).toBe('۰۰')
        expect(c.fa(0)).toBe('۰۰')
    })
})

describe('countdown.computeRemaining', () => {
    test('returns the whole-second difference to a future date', () => {
        vi.useFakeTimers()
        vi.setSystemTime(new Date('2026-01-01T00:00:00Z'))
        const c = countdown({ dateIso: '2026-01-02T01:01:01Z', confetti: false })
        expect(c.computeRemaining()).toBe(90061)
    })

    test('clamps to 0 for a date already in the past', () => {
        vi.useFakeTimers()
        vi.setSystemTime(new Date('2026-01-02T00:00:00Z'))
        const c = countdown({ dateIso: '2026-01-01T00:00:00Z', confetti: false })
        expect(c.computeRemaining()).toBe(0)
    })

    test('an unparsable dateIso yields 0 rather than NaN', () => {
        const c = countdown({ dateIso: 'not-a-date', confetti: false })
        expect(c.computeRemaining()).toBe(0)
    })
})

describe('countdown.update — day/hour/minute/second breakdown', () => {
    test('breaks down a remaining-seconds count into d/h/m/s Persian-digit strings', () => {
        const c = countdown({ dateIso: new Date(Date.now() + 100000).toISOString(), confetti: false })
        c.remaining = 90061
        c.update()
        expect(c.days).toBe('۰۱')
        expect(c.hours).toBe('۰۱')
        expect(c.minutes).toBe('۰۱')
        expect(c.seconds).toBe('۰۱')
    })

    test('zero remaining renders all-zero fields', () => {
        const c = countdown({ dateIso: new Date(Date.now() + 100000).toISOString(), confetti: false })
        c.remaining = 0
        c.update()
        expect([c.days, c.hours, c.minutes, c.seconds]).toEqual(['۰۰', '۰۰', '۰۰', '۰۰'])
    })
})

describe('countdown.mood / confetti constructor defaults', () => {
    test('mood defaults to happy unless explicitly "mourning"', () => {
        expect(countdown({ dateIso: new Date().toISOString(), confetti: false }).mood).toBe('happy')
        expect(countdown({ dateIso: new Date().toISOString(), mood: 'mourning', confetti: false }).mood).toBe('mourning')
        expect(countdown({ dateIso: new Date().toISOString(), mood: 'other', confetti: false }).mood).toBe('happy')
    })

    test('confetti defaults to true unless explicitly false', () => {
        expect(countdown({ dateIso: new Date().toISOString() }).confetti).toBe(true)
        expect(countdown({ dateIso: new Date().toISOString(), confetti: false }).confetti).toBe(false)
    })

    test('messages defaults to an empty array when omitted', () => {
        expect(countdown({ dateIso: new Date().toISOString(), confetti: false }).messages).toEqual([])
    })
})

describe('countdown.confettiColors', () => {
    function withTokens(map) {
        window.getComputedStyle = vi.fn(() => ({ getPropertyValue: (name) => map[name] || '' }))
    }

    test('mourning mood returns the fixed greyscale palette regardless of CSS tokens', () => {
        withTokens({})
        const c = countdown({ dateIso: new Date().toISOString(), mood: 'mourning', confetti: false })
        expect(c.confettiColors()).toEqual(['#0a0a0a', '#1f1f1f', '#3a3a3a', '#555555']);
    })

    test('happy mood resolves from CSS custom properties, falling back to hardcoded hex when unset', () => {
        withTokens({ '--tool-gold-500': '#111111', '--md-sys-color-primary': '#222222' })
        const c = countdown({ dateIso: new Date().toISOString(), confetti: false })
        const colors = c.confettiColors()
        expect(colors).toContain('#111111')
        expect(colors).toContain('#222222')
    })

    test('when every token resolves empty, falls back to the 3-color default palette', () => {
        withTokens({})
        const c = countdown({ dateIso: new Date().toISOString(), confetti: false })
        expect(c.confettiColors()).toEqual(['#FFD54F', '#6750A4', '#E6D2E8'])
    })
})

describe('countdown.fireZeroCelebration', () => {
    test('is a no-op under reduced motion, and never fires twice', () => {
        const c = countdown({ dateIso: new Date().toISOString(), confetti: false })
        c.reducedMotion = true
        c.celebrateZero = vi.fn()
        c.fireZeroCelebration()
        expect(c.celebrateZero).not.toHaveBeenCalled()
        expect(c.confettiFired).toBe(false)
    })

    test('fires once and flips confettiFired, guarding a second call', () => {
        const c = countdown({ dateIso: new Date().toISOString(), confetti: false })
        c.celebrateZero = vi.fn()
        c.fireZeroCelebration()
        expect(c.celebrateZero).toHaveBeenCalledTimes(1)
        expect(c.confettiFired).toBe(true)

        c.fireZeroCelebration()
        expect(c.celebrateZero).toHaveBeenCalledTimes(1)
    })
})

describe('countdown.tick', () => {
    test('counts down and, upon hitting zero, stops the interval and fires the zero celebration exactly once', () => {
        vi.useFakeTimers()
        vi.setSystemTime(new Date('2026-01-01T00:00:00Z'))
        const c = countdown({ dateIso: '2026-01-01T00:00:02Z', confetti: false })
        c.celebrateZero = vi.fn()
        c.intervalId = 123

        vi.setSystemTime(new Date('2026-01-01T00:00:01Z'))
        c.tick()
        expect(c.remaining).toBe(1)
        expect(c.reachedZero).toBe(false)

        vi.setSystemTime(new Date('2026-01-01T00:00:02Z'))
        c.tick()
        expect(c.remaining).toBe(0)
        expect(c.reachedZero).toBe(true)
        expect(c.celebrateZero).toHaveBeenCalledTimes(1)
    })
})

describe('countdown.nextTick — message rotation', () => {
    test('is a no-op with 0 or 1 messages', () => {
        const c = countdown({ dateIso: new Date().toISOString(), messages: ['only'], confetti: false })
        c.nextTick()
        expect(c.tickIndex).toBe(0)
    })

    test('advances tickIndex while there are further messages to show', () => {
        const c = countdown({ dateIso: new Date().toISOString(), messages: ['a', 'b', 'c'], confetti: false })
        c.nextTick()
        expect(c.tickIndex).toBe(1)
        c.nextTick()
        expect(c.tickIndex).toBe(2)
    })

    test('after the last message, schedules a no-transition wrap back to the first message', () => {
        vi.useFakeTimers()
        global.requestAnimationFrame = (cb) => setTimeout(cb, 0)

        const c = countdown({ dateIso: new Date().toISOString(), messages: ['a', 'b'], confetti: false })
        c.tickIndex = 1

        c.nextTick()
        expect(c.tickIndex).toBe(2)

        vi.advanceTimersByTime(600)
        expect(c.tickIndex).toBe(0)
        expect(c.noTransition).toBe(true)

        vi.runAllTimers()
        expect(c.noTransition).toBe(false)
    })
})

describe('countdown.destroy', () => {
    test('clears timers and does not throw when confettiInstance.reset throws', () => {
        vi.useFakeTimers()
        const c = countdown({ dateIso: new Date().toISOString(), confetti: false })
        c.intervalId = setInterval(() => {}, 1000)
        c.tickerId = setInterval(() => {}, 1000)
        c.transitionTimeout = setTimeout(() => {}, 1000)
        c.zeroTimeouts = [setTimeout(() => {}, 1000)]
        c.confettiInstance = { reset: () => { throw new Error('boom') } }

        expect(() => c.destroy()).not.toThrow()
    })

    test('is safe to call with no timers/confetti instance at all', () => {
        const c = countdown({ dateIso: new Date().toISOString(), confetti: false })
        expect(() => c.destroy()).not.toThrow()
    })
})

describe('countdown.createConfetti', () => {
    test('returns null when confetti is disabled, unavailable, or the canvas ref is missing', () => {
        const c = countdown({ dateIso: new Date().toISOString(), confetti: false })
        c.$refs = { confettiCanvas: document.createElement('canvas') }
        expect(c.createConfetti()).toBeNull()
    })

    test('returns null if window.confetti.create throws', () => {
        const c = countdown({ dateIso: new Date().toISOString(), confetti: true })
        c.$refs = { confettiCanvas: document.createElement('canvas') }
        window.confetti = { create: () => { throw new Error('fail') } }
        expect(c.createConfetti()).toBeNull()
        delete window.confetti
    })

    test('delegates to window.confetti.create with the canvas ref when everything is available', () => {
        const c = countdown({ dateIso: new Date().toISOString(), confetti: true })
        const canvas = document.createElement('canvas')
        c.$refs = { confettiCanvas: canvas }
        const instance = () => {}
        window.confetti = { create: vi.fn(() => instance) }
        expect(c.createConfetti()).toBe(instance)
        expect(window.confetti.create).toHaveBeenCalledWith(canvas, { resize: true, useWorker: false })
        delete window.confetti
    })
})
