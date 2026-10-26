import { describe, expect, test, beforeEach } from 'vitest'
import chatScaleMixin from '../chatScale.js'

const scale = () => document.documentElement.style.getPropertyValue('--chat-scale')

beforeEach(() => {
    localStorage.clear()
    document.documentElement.style.removeProperty('--chat-scale')
})

describe('chatScaleMixin', () => {
    test('defaults to scale 1 when nothing is stored', () => {
        const c = chatScaleMixin()
        c.initChatScale()
        expect(scale()).toBe('1')
        expect(c.canShrinkScale()).toBe(true)
        expect(c.canGrowScale()).toBe(true)
    })

    test('restores a stored step and ignores out-of-range or garbage values', () => {
        localStorage.setItem('chat-ui-scale', '0')
        const a = chatScaleMixin()
        a.initChatScale()
        expect(scale()).toBe('0.8')
        expect(a.canShrinkScale()).toBe(false)

        localStorage.setItem('chat-ui-scale', '99')
        const b = chatScaleMixin()
        b.initChatScale()
        expect(scale()).toBe('1')

        localStorage.setItem('chat-ui-scale', 'abc')
        const c = chatScaleMixin()
        c.initChatScale()
        expect(scale()).toBe('1')
    })

    test('stepScale moves one notch, persists it, and clamps at both ends without writing', () => {
        const c = chatScaleMixin()
        c.initChatScale()

        c.stepScale(1)
        expect(scale()).toBe('1.1')
        expect(localStorage.getItem('chat-ui-scale')).toBe('3')

        c.stepScale(1)
        expect(scale()).toBe('1.2')
        expect(c.canGrowScale()).toBe(false)

        localStorage.setItem('chat-ui-scale', 'sentinel')
        c.stepScale(1)
        expect(scale()).toBe('1.2')
        expect(localStorage.getItem('chat-ui-scale')).toBe('sentinel')

        c.stepScale(-4)
        expect(scale()).toBe('0.8')
        expect(c.canShrinkScale()).toBe(false)
    })

    test('resetScale jumps back to the default step from either side, persists it, and is a no-op at default', () => {
        const c = chatScaleMixin()
        c.initChatScale()
        expect(c.isDefaultScale()).toBe(true)

        c.stepScale(-2)
        expect(scale()).toBe('0.8')
        expect(c.isDefaultScale()).toBe(false)
        expect(c.scaleLabel().endsWith('٪')).toBe(true)

        c.resetScale()
        expect(scale()).toBe('1')
        expect(localStorage.getItem('chat-ui-scale')).toBe('2')
        expect(c.isDefaultScale()).toBe(true)

        c.stepScale(2)
        c.resetScale()
        expect(scale()).toBe('1')

        localStorage.setItem('chat-ui-scale', 'sentinel')
        c.resetScale()
        expect(localStorage.getItem('chat-ui-scale')).toBe('sentinel')
    })

    test('a second instance (another module) picks up the persisted step', () => {
        const contact = chatScaleMixin()
        contact.stepScale(-1)

        const project = chatScaleMixin()
        project.initChatScale()
        expect(scale()).toBe('0.9')
    })
})
