import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import thsModal from '../thsModal.js'
import { createWireMock } from '../../mixins/__tests__/_wireMock.js'

const make = () => {
    const c = thsModal()
    c.$wire = createWireMock()
    c.$nextTick = (cb) => cb()
    c.$root = document.getElementById('modal')
    return c
}

beforeEach(() => {
    vi.useFakeTimers()
    localStorage.clear()
    document.body.innerHTML = '<div id="header"></div><div id="content-shell"></div><div id="modal"><button data-ths-close></button></div>'
})

afterEach(() => {
    vi.useRealTimers()
})

describe('thsModal factory', () => {
    test('spreads the app-wide maximiser', () => {
        const c = make()
        expect(c.max).toBe(false)
        expect(typeof c.toggleMaximize).toBe('function')
        expect(typeof c.initMaximize).toBe('function')
    })

    test('init makes the page inert and focuses the close button; close releases it after the delay', () => {
        const c = make()
        c.init()
        expect(document.getElementById('content-shell').hasAttribute('inert')).toBe(true)
        expect(document.activeElement).toBe(document.querySelector('[data-ths-close]'))

        c.$wire.selectedTicket = { id: 1 }
        c.closeModal()
        expect(c.isClosing).toBe(true)
        expect(document.getElementById('content-shell').hasAttribute('inert')).toBe(true)
        vi.advanceTimersByTime(300)
        expect(document.getElementById('content-shell').hasAttribute('inert')).toBe(false)
        expect(c.$wire.set).toHaveBeenCalledWith('selectedTicket', null)
    })

    test('a pending close is a no-op when another ticket was opened meanwhile', () => {
        const c = make()
        c.init()
        c.$wire.selectedTicket = { id: 1 }
        c.closeModal()
        c.$wire.selectedTicket = { id: 2 }
        vi.advanceTimersByTime(300)
        expect(c.$wire.set).not.toHaveBeenCalled()
        expect(document.getElementById('content-shell').hasAttribute('inert')).toBe(true)
        expect(c.isClosing).toBe(false)
    })

    test('persisted maximise is restored on desktop but not on phones', () => {
        const key = 'max:ths-modal:7'
        document.documentElement.dataset.uid = '7'
        const phone = (matches) => { window.matchMedia = vi.fn(() => ({ matches })) }

        localStorage.setItem(key, 'true')
        phone(false)
        const a = thsModal()
        a.$nextTick = (cb) => cb()
        a.initMaximize()
        expect(a.max).toBe(false)
        expect(localStorage.getItem(key)).toBe('true')

        phone(true)
        const b = thsModal()
        b.$nextTick = (cb) => cb()
        b.initMaximize()
        expect(b.max).toBe(true)
        delete document.documentElement.dataset.uid
    })

    test('destroy releases inert and a maximised chrome', () => {
        const c = make()
        c.init()
        c.toggleMaximize()
        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(true)
        c.destroy()
        expect(document.getElementById('content-shell').hasAttribute('inert')).toBe(false)
        expect(document.getElementById('header').classList.contains('layout-hidden')).toBe(false)
    })

    test('escape order: details panel first, then restore maximise, then close', () => {
        const c = make()
        c.init()
        c.openInfo()
        c.toggleMaximize()

        c.onEscape()
        expect(c.info).toBe(false)
        expect(c.max).toBe(true)

        c.onEscape()
        vi.advanceTimersByTime(350)
        expect(c.max).toBe(false)
        expect(c.isClosing).toBe(false)

        c.onEscape()
        expect(c.isClosing).toBe(true)
    })

    test('closing the panel resets its tab only when not reopened within the leave animation', () => {
        const c = make()
        c.openInfo()
        c.infoTab = 'files'
        c.closeInfo()
        c.openInfo()
        vi.advanceTimersByTime(380)
        expect(c.infoTab).toBe('files')

        c.closeInfo()
        vi.advanceTimersByTime(380)
        expect(c.infoTab).toBe('info')
    })

    test('M toggles maximise only outside inputs; swipe-down closes from 80px', () => {
        const c = make()
        c.onKey({ key: 'm', target: document.createElement('div') })
        expect(c.max).toBe(true)
        c.onKey({ key: 'm', target: document.createElement('textarea') })
        expect(c.max).toBe(true)

        c.startDrag({ clientY: 10, target: document.createElement('div'), pointerId: 1 })
        c.moveDrag({ clientY: 60 })
        c.endDrag()
        expect(c.isClosing).toBe(false)

        c.startDrag({ clientY: 10, target: document.createElement('div'), pointerId: 1 })
        c.moveDrag({ clientY: 95 })
        c.endDrag()
        expect(c.isClosing).toBe(true)
    })
})
