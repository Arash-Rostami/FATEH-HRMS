import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import '../nav-dock.js'

function triggerInit() {
    delete window.__navDockBound
    document.dispatchEvent(new Event('alpine:initialized'))
}

beforeEach(() => {
    document.body.innerHTML = ''
    document.documentElement.classList.remove('nav-dock-bottom')
    delete window.__navDockBound
    window.Alpine = { store: vi.fn() }
    global.requestAnimationFrame = (cb) => setTimeout(cb, 0)
})

afterEach(() => {
    document.documentElement.classList.remove('nav-dock-bottom')
    delete window.Alpine
})

describe('nav-dock — applyNavDock gating on the nav-dock-bottom class', () => {
    test('does nothing when the root element lacks the nav-dock-bottom class', () => {
        triggerInit()
        expect(window.Alpine.store).not.toHaveBeenCalled()
    })

    test('closes the sidebar store and re-places tooltips on bound sidebar buttons when the class is present', async () => {
        document.documentElement.classList.add('nav-dock-bottom')
        const close = vi.fn()
        window.Alpine.store = vi.fn(() => ({ close }))

        const btn = document.createElement('button')
        btn.className = 'fi-sidebar-item-btn'
        const tippy = { setProps: vi.fn() }
        btn._tippy = tippy
        document.body.appendChild(btn)

        triggerInit()
        await new Promise((r) => setTimeout(r, 0))
        await new Promise((r) => setTimeout(r, 0))

        expect(window.Alpine.store).toHaveBeenCalledWith('sidebar')
        expect(close).toHaveBeenCalled()
        expect(tippy.setProps).toHaveBeenCalledWith({ placement: 'top' })
    })

    test('tolerates sidebar elements with no bound tippy instance', async () => {
        document.documentElement.classList.add('nav-dock-bottom')
        window.Alpine.store = vi.fn(() => ({ close: vi.fn() }))
        const btn = document.createElement('button')
        btn.className = 'fi-sidebar-item-btn'
        document.body.appendChild(btn)

        expect(() => triggerInit()).not.toThrow()
        await new Promise((r) => setTimeout(r, 0))
        await new Promise((r) => setTimeout(r, 0))
    })

    test('tolerates a missing sidebar store (optional chaining on store(...)?.close())', () => {
        document.documentElement.classList.add('nav-dock-bottom')
        window.Alpine.store = vi.fn(() => undefined)
        expect(() => triggerInit()).not.toThrow()
    })
})

describe('nav-dock — one-time bind guard', () => {
    test('a second alpine:initialized dispatch without resetting window.__navDockBound does not re-run applyNavDock', () => {
        triggerInit()
        window.Alpine.store = vi.fn()
        document.dispatchEvent(new Event('alpine:initialized'))
        expect(window.Alpine.store).not.toHaveBeenCalled()
    })
})

describe('nav-dock — livewire:navigated re-application', () => {
    test('re-runs applyNavDock after a Livewire navigation', () => {
        triggerInit()
        document.documentElement.classList.add('nav-dock-bottom')
        const close = vi.fn()
        window.Alpine.store = vi.fn(() => ({ close }))

        document.dispatchEvent(new Event('livewire:navigated'))

        expect(close).toHaveBeenCalled()
    })
})
