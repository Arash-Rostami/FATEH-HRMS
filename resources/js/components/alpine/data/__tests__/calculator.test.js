import { describe, expect, test, vi, beforeEach } from 'vitest'
import calculator from '../calculator.js'

const STORAGE_KEY = 'calculator_state'

beforeEach(() => {
    localStorage.clear()
})

describe('calculator.appendToDisplay', () => {
    test('appends plain digits/operators to the current display', () => {
        const c = calculator()
        c.appendToDisplay('2')
        c.appendToDisplay('+')
        c.appendToDisplay('3')
        expect(c.display).toBe('2+3')
    })

    test('after a finished calculation, typing a non-operator starts a fresh display', () => {
        const c = calculator()
        c.display = '5'
        c.calculationDone = true
        c.appendToDisplay('7')
        expect(c.display).toBe('7')
        expect(c.calculationDone).toBe(false)
    })

    test('after a finished calculation, typing an operator continues from the previous result', () => {
        const c = calculator()
        c.display = '5'
        c.calculationDone = true
        c.appendToDisplay('+')
        expect(c.display).toBe('5+')
        expect(c.calculationDone).toBe(false)
    })

    test('keeps formattedDisplay in sync and persists state', () => {
        const c = calculator()
        c.appendToDisplay('1')
        c.appendToDisplay('0')
        c.appendToDisplay('0')
        c.appendToDisplay('0')
        expect(c.formattedDisplay).toBe("1'000")
        expect(JSON.parse(localStorage.getItem(STORAGE_KEY)).display).toBe('1000')
    })

    test('a repeated operator replaces the trailing one instead of appending (5÷÷3 → 5÷3)', () => {
        const c = calculator()
        c.appendToDisplay('5')
        c.appendToDisplay('/')
        c.appendToDisplay('/')
        c.appendToDisplay('3')
        expect(c.display).toBe('5/3')
        c.calculate()
        expect(c.display).toBe((5 / 3).toString())
        expect(c.history[0].eq).toBe('5/3')
    })

    test('switching to a different operator also replaces the trailing one', () => {
        const c = calculator()
        c.appendToDisplay('5')
        c.appendToDisplay('*')
        c.appendToDisplay('+')
        expect(c.display).toBe('5+')
    })
})

describe('calculator.format — thousands separator', () => {
    test('groups a plain integer by thousands', () => {
        const c = calculator()
        c.display = '1234567'
        c.format()
        expect(c.formattedDisplay).toBe("1'234'567")
    })

    test('groups the integer part of a decimal but leaves the fractional part alone', () => {
        const c = calculator()
        c.display = '1234.567'
        c.format()
        expect(c.formattedDisplay).toBe("1'234.567")
    })

    test('groups each operand independently across an operator', () => {
        const c = calculator()
        c.display = '12+3456'
        c.format()
        expect(c.formattedDisplay).toBe("12+3'456")
    })

    test('a 3-digit or shorter number is left unseparated', () => {
        const c = calculator()
        c.display = '100'
        c.format()
        expect(c.formattedDisplay).toBe('100')
    })
})

describe('calculator.calculate', () => {
    test('evaluates a simple expression, formats the result, and records history', () => {
        const c = calculator()
        c.display = '2+3'
        c.calculate()
        expect(c.display).toBe('5')
        expect(c.formattedDisplay).toBe('5')
        expect(c.history[0]).toEqual({ eq: '2+3', res: '5' })
        expect(c.calculationDone).toBe(true)
    })

    test('translates the display-only ×, ÷, − symbols to JS operators before evaluating', () => {
        const c = calculator()
        c.display = '2×3÷1−1'
        c.calculate()
        expect(c.display).toBe('5')
    })

    test('a pure number with no operator does not trigger a calculation', () => {
        const c = calculator()
        c.display = '123'
        c.calculate()
        expect(c.display).toBe('123')
        expect(c.calculationDone).toBe(false)
        expect(c.history).toEqual([])
    })

    test('an empty display or a prior Error! state is a no-op', () => {
        const c = calculator()
        c.display = ''
        c.calculate()
        expect(c.history).toEqual([])

        c.display = 'Error!'
        c.calculate()
        expect(c.history).toEqual([])
    })

    test('an invalid expression sets the display to "Error!" instead of throwing', () => {
        const c = calculator()
        c.display = '2+'
        expect(() => c.calculate()).not.toThrow()
        expect(c.display).toBe('Error!')
        expect(c.formattedDisplay).toBe('Error!')
    })

    test('division by zero shows "Error!" and records no history entry', () => {
        const c = calculator()
        c.display = '5/0'
        c.calculate()
        expect(c.display).toBe('Error!')
        expect(c.formattedDisplay).toBe('Error!')
        expect(c.history).toEqual([])
    })

    test('a non-finite result anywhere in the expression (Infinity flowing onward) is also "Error!"', () => {
        const c = calculator()
        c.display = '5/0+1'
        c.calculate()
        expect(c.display).toBe('Error!')
        expect(c.history).toEqual([])
    })

    test('history is capped at MAX_HISTORY (50), dropping the oldest entry', () => {
        const c = calculator()
        c.history = Array.from({ length: 50 }, (_, i) => ({ eq: String(i), res: String(i) }))
        c.display = '1+1'
        c.calculate()
        expect(c.history).toHaveLength(50)
        expect(c.history[0]).toEqual({ eq: '1+1', res: '2' })
    })
})

describe('calculator.useHistory', () => {
    test('appends the (de-formatted) historical value to a fresh display after a finished calculation', () => {
        const c = calculator()
        c.display = '5'
        c.calculationDone = true
        c.useHistory("1'234")
        expect(c.display).toBe('1234')
        expect(c.calculationDone).toBe(false)
    })

    test('appends onto the current display when no calculation is pending', () => {
        const c = calculator()
        c.display = '5+'
        c.useHistory("1'000")
        expect(c.display).toBe('5+1000')
    })
})

describe('calculator.clearDisplay', () => {
    test('clears the display/format without touching history when the display is non-empty', () => {
        const c = calculator()
        c.display = '5'
        c.history = [{ eq: '1+1', res: '2' }]
        c.clearDisplay()
        expect(c.display).toBe('')
        expect(c.history).toEqual([{ eq: '1+1', res: '2' }])
    })

    test('clearing an already-empty display never touches history — fixed 2026-10-01, history is only ever cleared via its own dedicated button (history = [])', () => {
        const c = calculator()
        c.display = ''
        c.history = [{ eq: '1+1', res: '2' }]
        c.clearDisplay()
        expect(c.history).toEqual([{ eq: '1+1', res: '2' }])
    })
})

describe('calculator.backspace', () => {
    test('removes the last character of the display', () => {
        const c = calculator()
        c.display = '12+3'
        c.backspace()
        expect(c.display).toBe('12+')
    })

    test('clears to empty once the last character is removed', () => {
        const c = calculator()
        c.display = '5'
        c.backspace()
        expect(c.display).toBe('')
    })

    test('after a finished calculation, backspace clears the result instead of trimming it', () => {
        const c = calculator()
        c.display = '42'
        c.calculationDone = true
        c.backspace()
        expect(c.display).toBe('')
        expect(c.calculationDone).toBe(false)
    })
})

describe('calculator.copyLedger / copyToClipboard', () => {
    test('copyLedger is a no-op with empty history', () => {
        const c = calculator()
        c.copyText = vi.fn()
        c.copyLedger()
        expect(c.copyText).not.toHaveBeenCalled()
    })

    test('copyLedger joins history oldest-first as "eq = res" lines', () => {
        const c = calculator()
        c.copyText = vi.fn()
        c.history = [{ eq: '2+3', res: '5' }, { eq: '1+1', res: '2' }]
        c.copyLedger()
        expect(c.copyText).toHaveBeenCalledWith('1+1 = 2\n2+3 = 5', 'رسید محاسبات کپی شد.')
    })

    test('copyToClipboard is a no-op with an empty display, otherwise copies the raw display', () => {
        const c = calculator()
        c.copyText = vi.fn()
        c.display = ''
        c.copyToClipboard()
        expect(c.copyText).not.toHaveBeenCalled()

        c.display = '42'
        c.copyToClipboard()
        expect(c.copyText).toHaveBeenCalledWith('42', 'با موفقیت کپی شد.')
    })
})

describe('calculator.minimize / restore / mounted / destroy / closeModal', () => {
    function withModal() {
        const c = calculator()
        c.modal = document.createElement('div')
        c.modal.classList.add('hidden')
        return c
    }

    test('minimize hides the modal and flips state flags', () => {
        const c = withModal()
        c.modal.classList.add('flex')
        c.open = true
        c.minimize()
        expect(c.minimized).toBe(true)
        expect(c.open).toBe(false)
        expect(c.modal.classList.contains('hidden')).toBe(true)
        expect(c.modal.classList.contains('flex')).toBe(false)
    })

    test('restore shows the modal and flips state flags', () => {
        const c = withModal()
        c.minimized = true
        c.restore()
        expect(c.minimized).toBe(false)
        expect(c.open).toBe(true)
        expect(c.modal.classList.contains('flex')).toBe(true)
    })

    test('mounted opens the modal', () => {
        const c = withModal()
        c.mounted()
        expect(c.open).toBe(true)
        expect(c.modal.classList.contains('flex')).toBe(true)
    })

    test('destroy closes and resets state', () => {
        const c = withModal()
        c.modal.classList.add('flex')
        c.open = true
        c.minimized = true
        c.destroy()
        expect(c.open).toBe(false)
        expect(c.minimized).toBe(false)
        expect(c.modal.classList.contains('hidden')).toBe(true)
    })

    test('destroy removes the window listeners so a "calculate" event no longer reopens the tool', () => {
        const c = calculator()
        const modal = document.createElement('div')
        modal.classList.add('flex')
        c.$refs = { calculatorModal: modal }

        c.init()
        c.destroy()

        window.dispatchEvent(new Event('calculate'))
        expect(c.open).toBe(false)
        expect(modal.classList.contains('hidden')).toBe(true)
    })

    test('closeModal preserves the in-progress display and history for the next open', () => {
        const c = withModal()
        c.display = '5'
        c.history = [{ eq: '1+1', res: '2' }]
        c.minimized = true

        c.closeModal()

        expect(c.display).toBe('5')
        expect(c.open).toBe(false)
        expect(c.minimized).toBe(false)

        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY))
        expect(saved.display).toBe('5')
        expect(saved.minimized).toBe(false)
        expect(saved.history).toEqual([{ eq: '1+1', res: '2' }])

        const b = calculator()
        b.restoreState()
        expect(b.display).toBe('5')
        expect(b.history).toEqual([{ eq: '1+1', res: '2' }])
    })
})

describe('calculator state persistence round-trip', () => {
    test('restoreState with nothing saved leaves defaults untouched', () => {
        const c = calculator()
        c.restoreState()
        expect(c.display).toBe('')
        expect(c.history).toEqual([])
    })

    test('saveState then restoreState on a fresh instance round-trips the state, capping history at MAX_HISTORY', () => {
        const a = calculator()
        a.display = '5'
        a.formattedDisplay = '5'
        a.history = Array.from({ length: 55 }, (_, i) => ({ eq: String(i), res: String(i) }))
        a.minimized = true
        a.calculationDone = true
        a.saveState()

        const b = calculator()
        b.restoreState()

        expect(b.display).toBe('5')
        expect(b.history).toHaveLength(50)
        expect(b.minimized).toBe(true)
        expect(b.calculationDone).toBe(true)
    })
})

describe('calculator.init', () => {
    test('with no modal ref, still restores persisted state without registering listeners', () => {
        const c = calculator()
        c.$refs = {}
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ display: '9' }))
        c.init()
        expect(c.display).toBe('9')
        expect(c.modal).toBeUndefined()
    })

    test('with a modal ref, a "calculate" window event opens the calculator', () => {
        const c = calculator()
        const modal = document.createElement('div')
        modal.classList.add('hidden')
        c.$refs = { calculatorModal: modal }

        c.init()
        window.dispatchEvent(new Event('calculate'))

        expect(c.open).toBe(true)
        expect(modal.classList.contains('flex')).toBe(true)
    })

    test('pressing Escape while the modal is open (flex) minimizes it', () => {
        const c = calculator()
        const modal = document.createElement('div')
        modal.classList.add('flex')
        c.$refs = { calculatorModal: modal }

        c.init()
        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))

        expect(c.minimized).toBe(true)
        expect(modal.classList.contains('hidden')).toBe(true)
    })
})

describe('calculator keyboard input (opened tool only)', () => {
    function openCalculator() {
        const c = calculator()
        const modal = document.createElement('div')
        modal.classList.add('flex')
        c.$refs = { calculatorModal: modal }
        c.init()
        c.open = true
        return c
    }

    test('digit and operator keys type into the display', () => {
        const c = openCalculator()
        window.dispatchEvent(new KeyboardEvent('keydown', { key: '5' }))
        window.dispatchEvent(new KeyboardEvent('keydown', { key: '/' }))
        window.dispatchEvent(new KeyboardEvent('keydown', { key: '3' }))
        expect(c.display).toBe('5/3')
    })

    test('Persian digit keys type their ASCII equivalent into the display', () => {
        const c = openCalculator()
        window.dispatchEvent(new KeyboardEvent('keydown', { key: '۵' }))
        window.dispatchEvent(new KeyboardEvent('keydown', { key: '/' }))
        window.dispatchEvent(new KeyboardEvent('keydown', { key: '۳' }))
        expect(c.display).toBe('5/3')
    })

    test('Enter triggers calculate()', () => {
        const c = openCalculator()
        c.display = '2+2'
        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter' }))
        expect(c.display).toBe('4')
        expect(c.calculationDone).toBe(true)
    })

    test('Backspace removes the last character', () => {
        const c = openCalculator()
        c.display = '12'
        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Backspace' }))
        expect(c.display).toBe('1')
    })

    test('keys are ignored while the tool is closed', () => {
        const c = openCalculator()
        c.open = false
        window.dispatchEvent(new KeyboardEvent('keydown', { key: '7' }))
        expect(c.display).toBe('')
    })

    test('keys are ignored when typing in an unrelated input/textarea', () => {
        const c = openCalculator()
        const input = document.createElement('input')
        document.body.appendChild(input)
        input.dispatchEvent(new KeyboardEvent('keydown', { key: '7', bubbles: true }))
        expect(c.display).toBe('')
        document.body.removeChild(input)
    })

    test('keys still work when focus is on the calculator\'s own display input', () => {
        const c = openCalculator()
        const display = document.createElement('input')
        document.body.appendChild(display)
        c.$refs.display = display
        display.dispatchEvent(new KeyboardEvent('keydown', { key: '7', bubbles: true }))
        expect(c.display).toBe('7')
        document.body.removeChild(display)
    })
})
