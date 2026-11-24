import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import dms from '../dms.js'
import { createWireMock } from '../../mixins/__tests__/_wireMock.js'

const make = () => {
    window.matchMedia = vi.fn(() => ({ matches: false }))
    const c = dms()
    c.$wire = createWireMock()
    c.$nextTick = (cb) => cb()
    return c
}

beforeEach(() => {
    vi.useFakeTimers()
    localStorage.clear()
    document.body.innerHTML = '<div id="header"></div><div id="content-shell"></div>'
})

afterEach(() => {
    vi.useRealTimers()
})

describe('dms factory', () => {
    test('builds and initialises without throwing', () => {
        const c = make()
        expect(() => c.init()).not.toThrow()
        expect(c.max).toBe(false)
        expect(c.recent).toEqual([])
    })

    test('recordClick stores {id,title,url} first, dedupes, caps at 6 and clearRecent removes the key', () => {
        const c = make()
        c.init()
        c.recordClick({})
        expect(c.recent).toEqual([])
        for (let i = 1; i <= 7; i++) c.recordClick({ id: i, title: 't' + i })
        c.recordClick({ id: 5, url: '/u' })
        expect(c.recent.map((r) => r.id)).toEqual([5, 7, 6, 4, 3, 2])
        expect(c.recent[0]).toEqual({ id: 5, title: '', url: '/u' })
        expect(JSON.parse(localStorage.getItem('fateh_dms_recent'))).toEqual(c.recent)
        c.clearRecent()
        expect(c.recent).toEqual([])
        expect(localStorage.getItem('fateh_dms_recent')).toBeNull()
    })

    test('init restores stored recent and falls back to [] on invalid JSON', () => {
        localStorage.setItem('fateh_dms_recent', JSON.stringify([{ id: 1, title: 'a', url: '' }]))
        const c = make()
        c.init()
        expect(c.recent).toEqual([{ id: 1, title: 'a', url: '' }])
        localStorage.setItem('fateh_dms_recent', '{bad')
        const d = make()
        d.init()
        expect(d.recent).toEqual([])
    })

    test('toggleMaximize opens with layout hidden, then closes via maxLeaving after the delay', () => {
        const c = make()
        c.init()

        c.toggleMaximize()
        expect(c.max).toBe(true)
        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(true)

        c.toggleMaximize()
        expect(c.maxLeaving).toBe(true)
        expect(c.max).toBe(true)

        vi.advanceTimersByTime(400)
        expect(c.max).toBe(false)
        expect(c.maxLeaving).toBe(false)
        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(false)
    })

    test('initTableTools reads the table-tools:dms key', () => {
        localStorage.setItem('table-tools:dms', 'true')
        const c = make()
        c.init()
        expect(c.headerDeclutter).toBe(true)
    })

    test('destroy delegates to destroyTableTools and aborts the listener signal', () => {
        const c = make()
        c.init()
        const signal = c._tableToolsAc.signal
        const spy = vi.spyOn(c, 'destroyTableTools')

        c.destroy()

        expect(spy).toHaveBeenCalledTimes(1)
        expect(signal.aborted).toBe(true)
    })
})
