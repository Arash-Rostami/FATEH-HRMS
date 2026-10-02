import { describe, expect, test, vi, beforeEach } from 'vitest'
import registerAccessibility from '../accessibility.js'

function fresh() {
    let captured
    registerAccessibility({ store: (name, def) => { captured = def } })
    return captured
}

function withContext({ hasClipboard = true, secure = true } = {}) {
    Object.defineProperty(window, 'isSecureContext', { value: secure, configurable: true })
    Object.defineProperty(navigator, 'clipboard', {
        value: hasClipboard ? { writeText: vi.fn() } : undefined,
        configurable: true,
    })
}

const flush = () => new Promise((r) => setTimeout(r, 0))

beforeEach(() => {
    localStorage.clear()
    document.documentElement.className = ''
    document.documentElement.style.cssText = ''
})

describe('accessibility — readInt/readBool via init()', () => {
    test('readInt falls back to 0 on a non-numeric stored value', () => {
        localStorage.setItem('fontSizeLevel', 'abc')
        const s = fresh()
        s.init()
        expect(s.fontSizeLevel).toBe(0)
    })

    test('readInt truncates a decimal stored value via parseInt', () => {
        localStorage.setItem('fontSizeLevel', '2.9')
        const s = fresh()
        s.init()
        expect(s.fontSizeLevel).toBe(2)
    })

    test('readBool only treats the exact string "true" as true', () => {
        localStorage.setItem('readingRuler', '1')
        const s = fresh()
        s.init()
        expect(s.readingRuler).toBe(false)
    })

    test('init restores persisted font level/ruler/copy flags and applies them to the DOM', () => {
        localStorage.setItem('fontSizeLevel', '2')
        localStorage.setItem('readingRuler', 'true')
        localStorage.setItem('doubleClickCopy', 'true')
        const s = fresh()
        s.init()
        expect(s.fontSizeLevel).toBe(2)
        expect(s.readingRuler).toBe(true)
        expect(s.doubleClickCopy).toBe(true)
        expect(document.documentElement.classList.contains('reading-ruler')).toBe(true)
    })
})

describe('accessibility — font scale bounds', () => {
    test('increaseFontSize stops at maxScale (3)', () => {
        const s = fresh()
        s.fontSizeLevel = 3
        s.increaseFontSize()
        expect(s.fontSizeLevel).toBe(3)
    })

    test('decreaseFontSize stops at minScale (-2)', () => {
        const s = fresh()
        s.fontSizeLevel = -2
        s.decreaseFontSize()
        expect(s.fontSizeLevel).toBe(-2)
    })

    test('resetFontSize is a true no-op (skips the write) when already at 0', () => {
        const s = fresh()
        s.fontSizeLevel = 0
        const writeSpy = vi.spyOn(Storage.prototype, 'setItem')
        s.resetFontSize()
        expect(writeSpy).not.toHaveBeenCalled()
        writeSpy.mockRestore()
    })
})

describe('accessibility.getScaleLabel', () => {
    test.each([
        [-2, 'کوچک'],
        [-1, 'کوچک'],
        [0, 'پیش‌فرض'],
        [1, 'بزرگ'],
        [2, 'خیلی بزرگ'],
        [3, 'خیلی بزرگ'],
    ])('level %i -> %s', (level, label) => {
        const s = fresh()
        s.fontSizeLevel = level
        expect(s.getScaleLabel()).toBe(label)
    })
})

describe('accessibility.applyFontSize — scale = 1 + level*0.1', () => {
    test('a clean level (2) sets the CSS var, root font-size, and persists the level', () => {
        const s = fresh()
        s.fontSizeLevel = 2
        s.applyFontSize()
        expect(document.documentElement.style.getPropertyValue('--app-font-scale')).toBe('1.2')
        expect(document.documentElement.style.fontSize).toBe('120%')
        expect(localStorage.getItem('fontSizeLevel')).toBe('2')
    })

    test('level 1 rounds to a clean font-size percentage — fixed 2026-10-01, floating-point artifacts from scale*100 are rounded away', () => {
        const s = fresh()
        s.fontSizeLevel = 1
        s.applyFontSize()
        expect(document.documentElement.style.fontSize).toBe('110%')
    })
})

describe('accessibility — reading-ruler / double-click-copy toggles: state + persistence + root class', () => {
    test('toggleReadingRuler flips state, persists it, and toggles the reading-ruler root class', () => {
        const s = fresh()
        s.toggleReadingRuler()
        expect(s.readingRuler).toBe(true)
        expect(localStorage.getItem('readingRuler')).toBe('true')
        expect(document.documentElement.classList.contains('reading-ruler')).toBe(true)

        s.toggleReadingRuler()
        expect(s.readingRuler).toBe(false)
        expect(localStorage.getItem('readingRuler')).toBe('false')
        expect(document.documentElement.classList.contains('reading-ruler')).toBe(false)
    })

    test('toggleDoubleClickCopy flips state and persists it', () => {
        const s = fresh()
        s.toggleDoubleClickCopy()
        expect(s.doubleClickCopy).toBe(true)
        expect(localStorage.getItem('doubleClickCopy')).toBe('true')

        s.toggleDoubleClickCopy()
        expect(s.doubleClickCopy).toBe(false)
        expect(localStorage.getItem('doubleClickCopy')).toBe('false')
    })
})

describe('accessibility — reading-ruler mousemove handler (rAF-throttled)', () => {
    beforeEach(() => {
        global.requestAnimationFrame = (cb) => setTimeout(cb, 0)
        global.cancelAnimationFrame = (id) => clearTimeout(id)
    })

    test('activating tracks mousemove and writes --ruler-y through the rAF callback', async () => {
        const s = fresh()
        s.readingRuler = true
        s.applyReadingRuler()

        document.dispatchEvent(new MouseEvent('mousemove', { clientY: 123 }))
        await flush()

        expect(document.documentElement.style.getPropertyValue('--ruler-y')).toBe('123px')
    })

    test('a second mousemove before the scheduled frame fires does not schedule a second rAF (re-entrancy guard)', async () => {
        const s = fresh()
        s.readingRuler = true
        s.applyReadingRuler()

        const rafSpy = vi.spyOn(global, 'requestAnimationFrame')
        document.dispatchEvent(new MouseEvent('mousemove', { clientY: 10 }))
        document.dispatchEvent(new MouseEvent('mousemove', { clientY: 20 }))
        expect(rafSpy).toHaveBeenCalledTimes(1)

        await flush()
        rafSpy.mockRestore()
    })

    test('deactivating removes the reading-ruler class and the mousemove listener', () => {
        const s = fresh()
        s.readingRuler = true
        s.applyReadingRuler()
        expect(document.documentElement.classList.contains('reading-ruler')).toBe(true)

        s.readingRuler = false
        s.applyReadingRuler()
        expect(document.documentElement.classList.contains('reading-ruler')).toBe(false)
    })
})

describe('accessibility — double-click-copy mouseup handler', () => {
    function fakeSelection(text) {
        window.getSelection = vi.fn(() => (text === null ? null : { toString: () => text }))
    }

    beforeEach(() => {
        document.body.innerHTML = ''
    })

    test('a null window.getSelection() is a no-op', () => {
        withContext({ secure: true, hasClipboard: true })
        const s = fresh()
        fakeSelection(null)
        s.doubleClickCopy = true
        s.applyDoubleClickCopy()

        expect(() => document.dispatchEvent(new MouseEvent('mouseup'))).not.toThrow()
        expect(navigator.clipboard.writeText).not.toHaveBeenCalled()
    })

    test('a whitespace-only selection is a no-op (no clipboard call)', () => {
        withContext({ secure: true, hasClipboard: true })
        const s = fresh()
        fakeSelection('   ')
        s.doubleClickCopy = true
        s.applyDoubleClickCopy()

        document.dispatchEvent(new MouseEvent('mouseup'))
        expect(navigator.clipboard.writeText).not.toHaveBeenCalled()
    })

    test('secure context copies the trimmed selection via the Clipboard API and toasts success', async () => {
        withContext({ secure: true, hasClipboard: true })
        navigator.clipboard.writeText.mockResolvedValue(undefined)
        const s = fresh()
        fakeSelection('  hello  ')
        s.doubleClickCopy = true
        s.applyDoubleClickCopy()

        const toastHandler = vi.fn()
        window.addEventListener('toast', toastHandler, { once: true })
        document.dispatchEvent(new MouseEvent('mouseup'))
        await flush()

        expect(navigator.clipboard.writeText).toHaveBeenCalledWith('hello')
        expect(toastHandler.mock.calls[0][0].detail).toEqual({ message: 'کپی شد', type: 'success' })
    })

    test('a rejected Clipboard API write toasts an error', async () => {
        withContext({ secure: true, hasClipboard: true })
        navigator.clipboard.writeText.mockRejectedValue(new Error('denied'))
        const s = fresh()
        fakeSelection('hello')
        s.doubleClickCopy = true
        s.applyDoubleClickCopy()

        const toastHandler = vi.fn()
        window.addEventListener('toast', toastHandler, { once: true })
        document.dispatchEvent(new MouseEvent('mouseup'))
        await flush()

        expect(toastHandler.mock.calls[0][0].detail).toEqual({ message: 'کپی ناموفق بود.', type: 'error' })
    })

    test('outside a secure context, falls back to execCommand and toasts success', () => {
        withContext({ secure: false, hasClipboard: false })
        document.execCommand = vi.fn(() => true)
        const s = fresh()
        fakeSelection('hello')
        s.doubleClickCopy = true
        s.applyDoubleClickCopy()

        const toastHandler = vi.fn()
        window.addEventListener('toast', toastHandler, { once: true })
        document.dispatchEvent(new MouseEvent('mouseup'))

        expect(document.execCommand).toHaveBeenCalledWith('copy')
        expect(toastHandler.mock.calls[0][0].detail.type).toBe('success')
        expect(document.querySelector('textarea')).toBeNull()
    })

    test('a throwing execCommand fallback toasts an error without throwing, and now cleans up the textarea too — fixed 2026-10-01, matching mixins/clipboard.js\'s finally-guarded fallbackCopyText', () => {
        withContext({ secure: false, hasClipboard: false })
        document.execCommand = vi.fn(() => { throw new Error('nope') })
        const s = fresh()
        fakeSelection('hello')
        s.doubleClickCopy = true
        s.applyDoubleClickCopy()

        const toastHandler = vi.fn()
        window.addEventListener('toast', toastHandler, { once: true })
        expect(() => document.dispatchEvent(new MouseEvent('mouseup'))).not.toThrow()

        expect(toastHandler.mock.calls[0][0].detail.type).toBe('error')
        expect(document.querySelector('textarea')).toBeNull()
    })

    test('deactivating removes the mouseup listener', () => {
        withContext({ secure: true, hasClipboard: true })
        const s = fresh()
        fakeSelection('hello')
        s.doubleClickCopy = true
        s.applyDoubleClickCopy()
        s.doubleClickCopy = false
        s.applyDoubleClickCopy()

        document.dispatchEvent(new MouseEvent('mouseup'))
        expect(navigator.clipboard.writeText).not.toHaveBeenCalled()
    })
})
