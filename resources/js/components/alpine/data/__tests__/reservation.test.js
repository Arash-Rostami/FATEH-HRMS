import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import reservation from '../reservation.js'

beforeEach(() => {
    vi.useFakeTimers()
})

afterEach(() => {
    vi.useRealTimers()
})

function withWireDate(date) {
    const r = reservation()
    r.$wire = { get: vi.fn((k) => (k === 'date' ? date : null)) }
    return r
}

describe('reservation.slotState', () => {
    test('a day other than today is always ok, regardless of the time of day', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 10, 0, 0))
        const r = withWireDate('2026-01-02')
        expect(r.slotState('09:00', 0, 'ok')).toBe('ok')
    })

    test('today: a slot already behind the current time is past', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 10, 0, 0))
        const r = withWireDate('2026-01-01')
        expect(r.slotState('09:00', 0, 'ok')).toBe('past')
    })

    test('today: a slot ahead of now but inside the notice window is soon', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 10, 0, 0))
        const r = withWireDate('2026-01-01')
        expect(r.slotState('10:30', 1, 'ok')).toBe('soon')
    })

    test('today: a slot beyond the notice window is ok', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 10, 0, 0))
        const r = withWireDate('2026-01-01')
        expect(r.slotState('12:00', 1, 'ok')).toBe('ok')
    })

    test('a stricter server state (past) overrides a laxer local state (ok)', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 10, 0, 0))
        const r = withWireDate('2026-01-01')
        expect(r.slotState('12:00', 0, 'past')).toBe('past')
    })

    test('a laxer server state (ok) never downgrades a stricter local state (past)', () => {
        vi.setSystemTime(new Date(2026, 0, 1, 10, 0, 0))
        const r = withWireDate('2026-01-01')
        expect(r.slotState('09:00', 0, 'ok')).toBe('past')
    })
})

describe('reservation.slotTitle / slotButtonClass / slotButtonStyle', () => {
    test('slotTitle has Persian copy for past/soon and is empty for ok', () => {
        const r = reservation()
        expect(r.slotTitle('past')).toContain('گذشته')
        expect(r.slotTitle('soon')).toContain('مهلت')
        expect(r.slotTitle('ok')).toBe('')
    })

    test('slotButtonClass picks the disabled class for any non-ok state regardless of selection', () => {
        const r = reservation()
        expect(r.slotButtonClass('past', true)).toContain('cursor-not-allowed')
        expect(r.slotButtonClass('soon', false)).toContain('cursor-not-allowed')
    })

    test('slotButtonClass distinguishes selected vs default only when the state is ok', () => {
        const r = reservation()
        expect(r.slotButtonClass('ok', true)).toContain('scale-102')
        expect(r.slotButtonClass('ok', false)).not.toContain('scale-102')
    })

    test('slotButtonStyle only supplies the inline border style for an ok, unselected slot', () => {
        const r = reservation()
        expect(r.slotButtonStyle('ok', false)).not.toBe('')
        expect(r.slotButtonStyle('ok', true)).toBe('')
        expect(r.slotButtonStyle('past', false)).toBe('')
    })
})

describe('reservation.fa / humanize', () => {
    test('fa converts ASCII digits to Persian digits', () => {
        const r = reservation()
        expect(r.fa(123)).toBe('۱۲۳')
    })

    test('humanize renders minutes-only, exact-hours, and hours+minutes', () => {
        const r = reservation()
        expect(r.humanize(45)).toBe('۴۵ دقیقه')
        expect(r.humanize(60)).toBe('۱ ساعت')
        expect(r.humanize(90)).toBe('۱ ساعت و ۳۰ دقیقه')
    })
})

describe('reservation.durationPreview', () => {
    test('a valid, in-range end-after-start range is valid with a humanized duration', () => {
        const r = reservation()
        r.start = '09:00'
        r.end = '10:30'
        expect(r.durationPreview(null, null)).toEqual({ valid: true, text: 'مدت انتخابی: ۱ ساعت و ۳۰ دقیقه' })
    })

    test('missing start or end is invalid with a fixed message', () => {
        const r = reservation()
        r.start = null
        r.end = null
        expect(r.durationPreview(null, null)).toEqual({ valid: false, text: 'زمان پایان باید بعد از شروع باشد' })
    })

    test('an end at or before start is invalid with the same fixed message', () => {
        const r = reservation()
        r.start = '10:00'
        r.end = '09:00'
        expect(r.durationPreview(null, null).valid).toBe(false)
        expect(r.durationPreview(null, null).text).toBe('زمان پایان باید بعد از شروع باشد')
    })

    test('a duration under the minimum is invalid but still shows the humanized text', () => {
        const r = reservation()
        r.start = '09:00'
        r.end = '09:10'
        const res = r.durationPreview(15, 60)
        expect(res.valid).toBe(false)
        expect(res.text).toBe('مدت انتخابی: ۱۰ دقیقه')
    })

    test('a duration over the maximum is invalid', () => {
        const r = reservation()
        r.start = '09:00'
        r.end = '11:00'
        expect(r.durationPreview(15, 60).valid).toBe(false)
    })
})

describe('reservation.syncTimePreview / pickStart / pickEnd', () => {
    test('syncTimePreview reads start/end from $wire', () => {
        const r = reservation()
        r.$wire = { get: vi.fn((k) => ({ startTime: '09:00', endTime: '10:00' }[k])) }
        r.syncTimePreview()
        expect(r.start).toBe('09:00')
        expect(r.end).toBe('10:00')
    })

    test('pickStart/pickEnd set the local preview only', () => {
        const r = reservation()
        r.pickStart('08:00')
        r.pickEnd('09:00')
        expect(r.start).toBe('08:00')
        expect(r.end).toBe('09:00')
    })
})

describe('reservation.deckReconcileOpen', () => {
    test('does nothing when $wire.open is 0/falsy', () => {
        const r = reservation()
        r.$wire = { get: vi.fn(() => 0) }
        r.deckOrder = [1, 2, 3]
        r.deckReconcileOpen()
        expect(r.deckOrder).toEqual([1, 2, 3])
    })

    test('does nothing when the open id is already at the front', () => {
        const r = reservation()
        r.$wire = { get: vi.fn(() => 1) }
        r.deckOrder = [1, 2, 3]
        const ref = r.deckOrder
        r.deckReconcileOpen()
        expect(r.deckOrder).toBe(ref)
    })

    test('brings the open id to the front when it exists deeper in the deck', () => {
        const r = reservation()
        r.$wire = { get: vi.fn(() => 3) }
        r.deckOrder = [1, 2, 3]
        r.deckReconcileOpen()
        expect(r.deckOrder).toEqual([3, 1, 2])
    })

    test('does nothing when the open id is not present in the deck at all', () => {
        const r = reservation()
        r.$wire = { get: vi.fn(() => 99) }
        r.deckOrder = [1, 2, 3]
        r.deckReconcileOpen()
        expect(r.deckOrder).toEqual([1, 2, 3])
    })
})

describe('reservation.destroy', () => {
    test('clears the morph hook and the clock interval, and is safe to call twice', () => {
        const r = reservation()
        const unhook = vi.fn()
        r._morphHook = unhook
        r._clockInterval = setInterval(() => {}, 1000)

        r.destroy()

        expect(unhook).toHaveBeenCalledTimes(1)
        expect(r._morphHook).toBeNull()
        expect(r._clockInterval).toBeNull()
        expect(() => r.destroy()).not.toThrow()
    })
})

describe('reservation.toggleMaximize', () => {
    test('opens with layout hidden, then closes through maxLeaving before max drops', () => {
        document.body.innerHTML = '<div id="header"></div><div id="content-shell"></div>'
        const r = reservation()
        r.$nextTick = (cb) => cb()

        r.toggleMaximize()
        expect(r.max).toBe(true)
        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(true)

        r.toggleMaximize()
        expect(r.maxLeaving).toBe(true)
        expect(r.max).toBe(true)
        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(false)

        vi.advanceTimersByTime(400)
        expect(r.max).toBe(false)
        expect(r.maxLeaving).toBe(false)
    })
})

describe('reservation.scrollNext / scrollPrev', () => {
    test('scrollNext scrolls the PREVIOUS sibling container leftward (negative)', () => {
        const r = reservation()
        const container = { scrollBy: vi.fn() }
        const btn = { previousElementSibling: container, nextElementSibling: null }

        r.scrollNext(btn)

        expect(container.scrollBy).toHaveBeenCalledWith({ left: -250, behavior: 'smooth' })
    })

    test('scrollPrev scrolls the NEXT sibling container rightward (positive)', () => {
        const r = reservation()
        const container = { scrollBy: vi.fn() }
        const btn = { previousElementSibling: null, nextElementSibling: container }

        r.scrollPrev(btn)

        expect(container.scrollBy).toHaveBeenCalledWith({ left: 250, behavior: 'smooth' })
    })

    test('both are no-ops when the expected sibling is absent', () => {
        const r = reservation()
        expect(() => r.scrollNext({ previousElementSibling: null })).not.toThrow()
        expect(() => r.scrollPrev({ nextElementSibling: null })).not.toThrow()
    })
})
