import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import registerBackground from '../background.js'

function fresh(storeModule = { default: registerBackground }) {
    let captured
    storeModule.default({ store: (name, def) => { captured = def } })
    return captured
}

beforeEach(() => {
    localStorage.clear()
})

describe('background store — localStorage round-trips (resolved at call time, not import time)', () => {
    test('enabled/patternEnabled default to false and activePattern defaults to "shapes" when nothing is stored', () => {
        const s = fresh()
        expect(s.enabled).toBe(false)
        expect(s.patternEnabled).toBe(false)
        expect(s.activePattern).toBe('shapes')
    })

    test('restores enabled/patternEnabled/activePattern from localStorage', () => {
        localStorage.setItem('backgroundEnabled', 'true')
        localStorage.setItem('patternEnabled', 'true')
        localStorage.setItem('activePattern', 'rain')
        const s = fresh()
        expect(s.enabled).toBe(true)
        expect(s.patternEnabled).toBe(true)
        expect(s.activePattern).toBe('rain')
    })
})

describe('background.toggleBackground', () => {
    test('enabling turns the background on, forces patternEnabled off, and persists both flags', () => {
        const s = fresh()
        s.patternEnabled = true

        s.toggleBackground(true)

        expect(s.enabled).toBe(true)
        expect(s.patternEnabled).toBe(false)
        expect(localStorage.getItem('backgroundEnabled')).toBe('true')
        expect(localStorage.getItem('patternEnabled')).toBe('false')
    })

    test('disabling only persists the enabled flag, leaving patternEnabled untouched', () => {
        const s = fresh()
        s.patternEnabled = true

        s.toggleBackground(false)

        expect(s.enabled).toBe(false)
        expect(s.patternEnabled).toBe(true)
        expect(localStorage.getItem('backgroundEnabled')).toBe('false')
        expect(localStorage.getItem('patternEnabled')).toBeNull()
    })
})

describe('background.togglePattern', () => {
    test('enabling turns the pattern on, forces the background off, and persists the active pattern (falling back to the default if unset)', () => {
        const s = fresh()
        s.enabled = true
        s.activePattern = ''

        s.togglePattern(true)

        expect(s.patternEnabled).toBe(true)
        expect(s.enabled).toBe(false)
        expect(localStorage.getItem('backgroundEnabled')).toBe('false')
        expect(localStorage.getItem('activePattern')).toBe('shapes')
        expect(localStorage.getItem('patternEnabled')).toBe('true')
    })

    test('disabling only persists the patternEnabled flag', () => {
        const s = fresh()
        s.enabled = true

        s.togglePattern(false)

        expect(s.patternEnabled).toBe(false)
        expect(s.enabled).toBe(true)
        expect(localStorage.getItem('backgroundEnabled')).toBeNull()
        expect(localStorage.getItem('patternEnabled')).toBe('false')
    })
})

describe('background.setPattern', () => {
    test('sets activePattern and persists it', () => {
        const s = fresh()
        s.setPattern('cyber')
        expect(s.activePattern).toBe('cyber')
        expect(localStorage.getItem('activePattern')).toBe('cyber')
    })
})

describe('background store — tenant backdrop config (frozen at module import time from window.__tenantBackdrop)', () => {
    beforeEach(() => {
        vi.resetModules()
    })

    afterEach(() => {
        delete window.__tenantBackdrop
    })

    async function freshWithTenant(tenant) {
        if (tenant === undefined) {
            delete window.__tenantBackdrop
        } else {
            window.__tenantBackdrop = tenant
        }
        const mod = await import('../background.js')
        let captured
        mod.default({ store: (name, def) => { captured = def } })
        return captured
    }

    test('with no window.__tenantBackdrop at all, every backdrop field falls back to its hardcoded default', async () => {
        const s = await freshWithTenant(undefined)
        expect(s.images).toEqual([])
        expect(s.backdropMode).toBe('time')
        expect(s.opacity).toBe(0.85)
        expect(s.blur).toBe(0)
        expect(s.brightness).toBe(1)
        expect(s.contrast).toBe(1)
        expect(s.grayscale).toBe(0)
        expect(s.width).toBe('100%')
        expect(s.fit).toBe('cover')
    })

    test('opacity/blur/brightness/contrast/grayscale use ?? — an explicit 0 override is preserved, not treated as unset', async () => {
        const s = await freshWithTenant({ opacity: 0, blur: 0, brightness: 0, contrast: 0, grayscale: 1 })
        expect(s.opacity).toBe(0)
        expect(s.blur).toBe(0)
        expect(s.brightness).toBe(0)
        expect(s.contrast).toBe(0)
        expect(s.grayscale).toBe(1)
    })

    test('width/fit use || instead of ?? — an explicit empty string falls back to the default (asymmetric with the ?? fields above)', async () => {
        const s = await freshWithTenant({ width: '', fit: '' })
        expect(s.width).toBe('100%')
        expect(s.fit).toBe('cover')
    })

    test('custom images/mode/width/fit are carried through as given', async () => {
        const s = await freshWithTenant({ images: ['/a.jpg', '/b.jpg'], mode: 'tabs', width: '80%', fit: 'contain' })
        expect(s.images).toEqual(['/a.jpg', '/b.jpg'])
        expect(s.backdropMode).toBe('tabs')
        expect(s.width).toBe('80%')
        expect(s.fit).toBe('contain')
    })
})
