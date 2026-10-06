import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import mobile from '../mobile.js'

let instance = null

function makeMobile() {
    instance = mobile(0, ['alpha'])
    instance.$watch = vi.fn()
    return instance
}

beforeEach(() => {
    localStorage.clear()
    instance = null
})

afterEach(() => {
    instance?.destroy()
})

describe('mobile declutter listener', () => {
    test('a declutter-on event switches the preset to smart and stashes the previous preset', () => {
        const m = makeMobile()
        m.init()
        expect(m.labelPreset).toBe('all')
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }))
        expect(m.labelPreset).toBe('smart')
        expect(m._preDeclutterPreset).toBe('all')
        expect(m.barHidden).toBe(true)
        expect(localStorage.getItem('btm-labels')).toBe('smart')
    })

    test('a declutter-off event restores the stashed preset and clears the stash', () => {
        const m = makeMobile()
        m.init()
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }))
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: false } }))
        expect(m.labelPreset).toBe('all')
        expect(m._preDeclutterPreset).toBeUndefined()
        expect(localStorage.getItem('btm-labels')).toBe('all')
    })

    test('an off event with nothing stashed falls back to the plain all preset', () => {
        const m = makeMobile()
        m.init()
        m.setLabelPreset('smart')
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: false } }))
        expect(m.labelPreset).toBe('all')
        expect(m.barHidden).toBe(false)
        expect(m._preDeclutterPreset).toBeUndefined()
        expect(localStorage.getItem('btm-labels')).toBe('all')
    })

    test('a manual preset change during declutter is re-stashed by a fresh on event', () => {
        const m = makeMobile()
        m.init()
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }))
        m.setLabelPreset('icons')
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }))
        expect(m._preDeclutterPreset).toBe('icons')
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: false } }))
        expect(m.labelPreset).toBe('icons')
    })

    test('events without a detail.on flag are ignored without raising a listener error', () => {
        const m = makeMobile()
        m.init()
        const errors = []
        const onError = (e) => errors.push(e)
        window.addEventListener('error', onError)
        window.dispatchEvent(new CustomEvent('declutter', { detail: {} }))
        window.dispatchEvent(new CustomEvent('declutter'))
        window.removeEventListener('error', onError)
        expect(m.labelPreset).toBe('all')
        expect(m._preDeclutterPreset).toBeUndefined()
        expect(errors).toHaveLength(0)
    })

    test('destroy aborts the declutter listener so later events no longer reach the component', () => {
        const m = makeMobile()
        m.init()
        m.destroy()
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }))
        expect(m.labelPreset).toBe('all')
    })
})