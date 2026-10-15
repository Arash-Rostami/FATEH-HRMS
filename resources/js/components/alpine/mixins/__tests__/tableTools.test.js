import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import tableToolsMixin from '../tableTools.js'

const KEY = 'table-tools:dms'

const make = (mobile = false) => {
    window.matchMedia = vi.fn(() => ({ matches: mobile }))
    const c = tableToolsMixin('dms')
    c.initTableTools()
    return c
}

beforeEach(() => {
    vi.useFakeTimers()
    localStorage.clear()
})

afterEach(() => {
    vi.useRealTimers()
    localStorage.clear()
})

describe('tableTools defaults', () => {
    test('desktop with no saved key stays expanded and writes nothing', () => {
        const c = make(false)
        expect(c.headerDeclutter).toBe(false)
        expect(localStorage.getItem(KEY)).toBeNull()
    })

    test('mobile with no saved key also starts expanded so search stays reachable', () => {
        expect(make(true).headerDeclutter).toBe(false)
    })

    test('a persisted choice wins on both breakpoints', () => {
        localStorage.setItem(KEY, 'true')
        expect(make(true).headerDeclutter).toBe(true)
        expect(make(false).headerDeclutter).toBe(true)
        localStorage.setItem(KEY, 'false')
        expect(make(true).headerDeclutter).toBe(false)
    })
})

describe('tableTools local declutter hides the footer', () => {
    const root = () => document.documentElement.classList

    afterEach(() => root().remove('declutter-local'))

    test('a collapsed toolbar at init marks the page, and the watcher follows later changes', () => {
        localStorage.setItem(KEY, 'true')
        const c = tableToolsMixin('dms')
        let watch
        c.$watch = (_key, cb) => { watch = cb }
        c.initTableTools()
        expect(root().contains('declutter-local')).toBe(true)

        watch(false)
        expect(root().contains('declutter-local')).toBe(false)
        watch(true)
        expect(root().contains('declutter-local')).toBe(true)
    })

    test('destroying the instance and a Livewire navigation both clear the mark', () => {
        localStorage.setItem(KEY, 'true')
        const c = tableToolsMixin('dms')
        c.initTableTools()
        expect(root().contains('declutter-local')).toBe(true)

        c.destroyTableTools()
        expect(root().contains('declutter-local')).toBe(false)

        root().add('declutter-local')
        document.dispatchEvent(new Event('livewire:navigating'))
        expect(root().contains('declutter-local')).toBe(false)
    })
})

describe('tableTools manual toggling', () => {
    test('collapse animates then persists true after 400ms', () => {
        const c = make()
        c.toggleHeaderDeclutter()
        expect(c.headerDeclutterExit).toBe(true)
        expect(c.headerDeclutter).toBe(false)
        vi.advanceTimersByTime(400)
        expect(c.headerDeclutterExit).toBe(false)
        expect(c.headerDeclutter).toBe(true)
        expect(localStorage.getItem(KEY)).toBe('true')
    })

    test('a second click mid-exit cancels without writing', () => {
        const c = make()
        c.toggleHeaderDeclutter()
        c.toggleHeaderDeclutter()
        vi.advanceTimersByTime(1000)
        expect(c.headerDeclutterExit).toBe(false)
        expect(c.headerDeclutter).toBe(false)
        expect(localStorage.getItem(KEY)).toBeNull()
    })

    test('manual expand persists false immediately', () => {
        localStorage.setItem(KEY, 'true')
        const c = make()
        c.toggleHeaderDeclutter()
        expect(c.headerDeclutter).toBe(false)
        expect(localStorage.getItem(KEY)).toBe('false')
    })
})

describe('tableTools menu bridge', () => {
    test('declutter on collapses without persisting, off expands and persists false', () => {
        const c = make()
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }))
        expect(c.headerDeclutter).toBe(true)
        expect(c._menuForced).toBe(true)
        expect(localStorage.getItem(KEY)).toBeNull()
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: false } }))
        expect(c.headerDeclutter).toBe(false)
        expect(c._menuForced).toBe(false)
        expect(localStorage.getItem(KEY)).toBe('false')
    })

    test('toggle while forced expands and persists false; forced persist never writes true', () => {
        const c = make()
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }))
        c.toggleHeaderDeclutter()
        expect(c.headerDeclutter).toBe(false)
        expect(localStorage.getItem(KEY)).toBe('false')
        localStorage.clear()
        c.headerDeclutter = true
        c._menuForced = true
        c._persistChatSettings()
        expect(localStorage.getItem(KEY)).toBe('false')
    })

    test('destroyTableTools detaches the bridge and cancels a pending persist', () => {
        const c = make()
        c.destroyTableTools()
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }))
        expect(c.headerDeclutter).toBe(false)
        expect(localStorage.getItem(KEY)).toBeNull()
        c.toggleHeaderDeclutter()
        c.destroyTableTools()
        vi.advanceTimersByTime(400)
        expect(localStorage.getItem(KEY)).toBeNull()
    })

    test('boot sync with menu_declutter collapses and leaves the key untouched', () => {
        localStorage.setItem('menu_declutter', 'true')
        const c = make()
        expect(c.headerDeclutter).toBe(true)
        expect(localStorage.getItem(KEY)).toBeNull()
    })
})
