import { describe, expect, test, vi } from 'vitest'
import tagInput from '../tagInput.js'

const mount = (action = 'addTag') => {
    const input = tagInput()
    const call = vi.fn()
    input.$root = { dataset: { add: action } }
    input.$wire = { [action]: call }
    return { input, call }
}

describe('tagInput', () => {
    test('blank is true for empty and whitespace-only values and false once text exists', () => {
        const { input } = mount()
        expect(input.blank).toBe(true)
        input.value = '   '
        expect(input.blank).toBe(true)
        input.value = 'urgent'
        expect(input.blank).toBe(false)
    })

    test('add sends the trimmed tag to the wire method named by data-add and clears the field', () => {
        const { input, call } = mount('addTag')
        input.value = '  budget  '
        input.add()
        expect(call).toHaveBeenCalledOnce()
        expect(call).toHaveBeenCalledWith('budget')
        expect(input.value).toBe('')
    })

    test('add uses a custom action name from data-add', () => {
        const { input, call } = mount('addLabel')
        input.value = 'x'
        input.add()
        expect(call).toHaveBeenCalledWith('x')
    })

    test('add sends nothing for a blank value', () => {
        const { input, call } = mount()
        input.value = '   '
        input.add()
        expect(call).not.toHaveBeenCalled()
        expect(input.value).toBe('')
    })

    test('add does not throw when the wire proxy or the named method is missing', () => {
        const { input } = mount()
        input.value = 'tag'
        input.$wire = {}
        expect(() => input.add()).not.toThrow()
        input.value = 'tag'
        input.$wire = undefined
        expect(() => input.add()).not.toThrow()
        expect(input.value).toBe('')
    })
})
