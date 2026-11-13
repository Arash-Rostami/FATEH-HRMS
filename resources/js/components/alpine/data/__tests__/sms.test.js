import { describe, expect, test } from 'vitest'
import smsModal from '../sms.js'

const templates = { birthday: ['{name} عزیز، تولدت مبارک!', 'سلام {name}'] }

function make(scope = 'calendar') {
    const c = smsModal('', templates, scope)
    c.$wire = { smsUserId: null, smsType: '', smsKey: '', showSmsModal: false }
    return c
}

describe('smsModal', () => {
    test('opens for its own scope with an empty text box and the templates ready with the name filled in', () => {
        const c = make()
        c.open({ scope: 'calendar', id: 7, name: 'سارا', type: 'birthday', key: 'birthday-7-1405' })

        expect(c.$wire).toMatchObject({ smsUserId: 7, smsType: 'birthday', smsKey: 'birthday-7-1405', showSmsModal: true })
        expect(c.choices).toEqual(['سارا عزیز، تولدت مبارک!', 'سلام سارا'])
        expect(c.t).toBe('')
        expect(c.smart()).toBe(false)
    })

    test('preview prepends the sender and appends the footer, and stays empty while the editor is empty', () => {
        const c = smsModal('', templates, 'calendar', { sender: 'آرش', footer: 'این پیامک از سامانه X ارسال شده است' })
        c.t = '  سلام  '
        expect(c.preview()).toBe('آرش: سلام\nاین پیامک از سامانه X ارسال شده است')
        c.t = ''
        expect(c.preview()).toBe('')
        expect(c.segments()).toBe(0)
    })

    test('segments counts one part up to 70 characters, then 67 per part', () => {
        const c = smsModal('', templates, 'calendar', { sender: '', footer: '', fallback: '' })
        c.t = 'ا'.repeat(69)
        expect(c.segments()).toBe(1)
        c.t = 'ا'.repeat(100)
        expect(c.segments()).toBe(2)
    })

    test('ignores another scope and defaults a scope-less event to status', () => {
        const calendar = make('calendar')
        calendar.open({ id: 7, name: 'سارا' })
        expect(calendar.$wire.showSmsModal).toBe(false)

        const status = make('status')
        status.open({ id: 7, name: 'سارا' })
        expect(status.$wire.showSmsModal).toBe(true)
        expect(status.choices).toEqual([])
        expect(status.t).toBe('')
    })

    test('smart build starts at the first ready text, cycles and wraps; your-choice clears it', () => {
        const c = make()
        c.open({ scope: 'calendar', id: 7, name: 'سارا', type: 'birthday', key: 'k' })
        c.next()
        expect(c.t).toBe('سارا عزیز، تولدت مبارک!')
        expect(c.smart()).toBe(true)
        c.next()
        expect(c.t).toBe('سلام سارا')
        c.next()
        expect(c.t).toBe('سارا عزیز، تولدت مبارک!')
        c.custom()
        expect(c.t).toBe('')
        expect(c.smart()).toBe(false)
    })

    test('smart build does nothing when the modal has no templates', () => {
        const c = make('status')
        c.open({ id: 7, name: 'سارا' })
        c.next()
        expect(c.t).toBe('')
    })

    test('a template never exceeds the 140-character limit', () => {
        const c = smsModal('', { birthday: ['{name} ' + 'ا'.repeat(200)] }, 'calendar')
        c.$wire = { showSmsModal: false }
        c.open({ scope: 'calendar', id: 1, name: 'x', type: 'birthday' })
        expect(c.choices[0].length).toBe(140)
    })

    test('a committed Livewire request stops the fresh state so validation errors show', () => {
        const c = make()
        expect(c.fresh).toBe(true)
        c.committed()
        expect(c.fresh).toBe(false)
    })
})
