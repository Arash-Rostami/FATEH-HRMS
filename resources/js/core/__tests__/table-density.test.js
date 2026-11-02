import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'

const root = document.documentElement

const makeAlpine = () => {
    const stores = {}

    return {
        store: vi.fn((name, value) => {
            if (value === undefined) return stores[name]
            stores[name] = value
        }),
        effect: vi.fn((fn) => fn()),
    }
}

const load = async () => {
    vi.resetModules()
    delete window.__tableDensityBound
    await import('../table-density.js')
    document.dispatchEvent(new Event('alpine:init'))
}

beforeEach(() => {
    localStorage.clear()
    root.classList.remove('table-density-compact')
    window.Alpine = makeAlpine()
})

afterEach(() => {
    delete window.setTableDensity
    delete window.Alpine
})

describe('table-density — compact rows toggle (2026-11-02)', () => {
    test('starts comfortable when nothing is stored', async () => {
        await load()

        expect(window.Alpine.store('tableDensityCompact')).toBe(false)
        expect(root.classList.contains('table-density-compact')).toBe(false)
    })

    test('restores a remembered compact choice onto the root element', async () => {
        localStorage.setItem('table_density', 'compact')

        await load()

        expect(window.Alpine.store('tableDensityCompact')).toBe(true)
        expect(root.classList.contains('table-density-compact')).toBe(true)
    })

    test('setTableDensity stores the choice and flips the store', async () => {
        await load()

        window.setTableDensity('compact')
        expect(localStorage.getItem('table_density')).toBe('compact')
        expect(window.Alpine.store('tableDensityCompact')).toBe(true)

        window.setTableDensity('comfortable')
        expect(localStorage.getItem('table_density')).toBe('comfortable')
        expect(window.Alpine.store('tableDensityCompact')).toBe(false)
    })

    test('any unknown mode counts as comfortable', async () => {
        await load()

        window.setTableDensity('whatever')

        expect(window.Alpine.store('tableDensityCompact')).toBe(false)
    })

    test('the class is re-applied after a Livewire navigation resets the root classes', async () => {
        localStorage.setItem('table_density', 'compact')
        await load()
        root.classList.remove('table-density-compact')

        document.dispatchEvent(new Event('livewire:navigated'))

        expect(root.classList.contains('table-density-compact')).toBe(true)
    })

    test('binds only once even if alpine:init fires twice', async () => {
        await load()
        document.dispatchEvent(new Event('alpine:init'))

        expect(window.Alpine.effect).toHaveBeenCalledTimes(1)
    })
})
