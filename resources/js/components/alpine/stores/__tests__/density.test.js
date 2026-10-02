import { describe, expect, test, beforeEach } from 'vitest'
import registerDensity from '../density.js'

function fresh() {
    let captured
    registerDensity({ store: (name, def) => { captured = def } })
    return captured
}

beforeEach(() => {
    localStorage.clear()
    document.documentElement.className = ''
})

describe('density.init', () => {
    test('restores compact=true from localStorage and adds the compact root class', () => {
        localStorage.setItem('app-density', 'compact')
        const s = fresh()
        s.init()
        expect(s.compact).toBe(true)
        expect(document.documentElement.classList.contains('app-density-compact')).toBe(true)
    })

    test('defaults to compact=false (no root class) when nothing is stored', () => {
        const s = fresh()
        s.init()
        expect(s.compact).toBe(false)
        expect(document.documentElement.classList.contains('app-density-compact')).toBe(false)
    })

    test('any stored value other than the exact string "compact" resolves to comfortable', () => {
        localStorage.setItem('app-density', 'comfortable')
        const s = fresh()
        s.init()
        expect(s.compact).toBe(false)
    })

    test('removes a stale compact class left on the root before init runs', () => {
        document.documentElement.classList.add('app-density-compact')
        const s = fresh()
        s.init()
        expect(document.documentElement.classList.contains('app-density-compact')).toBe(false)
    })
})

describe('density.toggle', () => {
    test('flips compact true -> false -> true, persisting "compact"/"comfortable" and toggling the root class each time', () => {
        const s = fresh()

        s.toggle()
        expect(s.compact).toBe(true)
        expect(localStorage.getItem('app-density')).toBe('compact')
        expect(document.documentElement.classList.contains('app-density-compact')).toBe(true)

        s.toggle()
        expect(s.compact).toBe(false)
        expect(localStorage.getItem('app-density')).toBe('comfortable')
        expect(document.documentElement.classList.contains('app-density-compact')).toBe(false)
    })
})
