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

describe('nav-dock — collapse the bottom bar to a single corner button (2026-11-02)', () => {
    const root = document.documentElement

    const sidebarWithMinButton = () => {
        const sidebar = document.createElement('div')
        sidebar.className = 'fi-sidebar'
        const min = document.createElement('div')
        min.className = 'dock-min'
        const btn = document.createElement('button')
        min.appendChild(btn)
        sidebar.appendChild(min)
        document.body.appendChild(sidebar)
        return { sidebar, btn }
    }

    beforeEach(() => {
        localStorage.clear()
        root.classList.remove('nav-dock-min')
        window.Alpine.store = vi.fn(() => ({ close: vi.fn() }))
    })

    afterEach(() => {
        root.classList.remove('nav-dock-min')
        localStorage.clear()
    })

    test('clicking the dock-min button while docked collapses the bar and remembers it', () => {
        root.classList.add('nav-dock-bottom')
        triggerInit()
        const { btn } = sidebarWithMinButton()

        btn.click()

        expect(root.classList.contains('nav-dock-min')).toBe(true)
        expect(localStorage.getItem('nav-dock-min')).toBe('true')
    })

    test('clicking the collapsed corner button expands the bar again', () => {
        root.classList.add('nav-dock-bottom')
        triggerInit()
        const { sidebar, btn } = sidebarWithMinButton()
        btn.click()

        sidebar.click()

        expect(root.classList.contains('nav-dock-min')).toBe(false)
        expect(localStorage.getItem('nav-dock-min')).toBe('false')
    })

    test('clicks do nothing when the bar is not docked at the bottom', () => {
        triggerInit()
        const { btn } = sidebarWithMinButton()

        btn.click()

        expect(root.classList.contains('nav-dock-min')).toBe(false)
        expect(localStorage.getItem('nav-dock-min')).toBeNull()
    })

    test('a remembered collapsed state is restored on init and after a Livewire navigation resets the root classes', () => {
        localStorage.setItem('nav-dock-min', 'true')
        root.classList.add('nav-dock-bottom')
        triggerInit()
        expect(root.classList.contains('nav-dock-min')).toBe(true)

        root.classList.remove('nav-dock-min')
        document.dispatchEvent(new Event('livewire:navigated'))

        expect(root.classList.contains('nav-dock-min')).toBe(true)
    })
})

describe('nav-dock — wheel scrolls the docked bar sideways and the active item is centered (2026-11-02)', () => {
    const root = document.documentElement

    const overflowingNav = (direction = 'ltr') => {
        const nav = document.createElement('nav')
        nav.className = 'fi-sidebar-nav'
        nav.style.direction = direction
        Object.defineProperty(nav, 'scrollWidth', { value: 1000, configurable: true })
        Object.defineProperty(nav, 'clientWidth', { value: 400, configurable: true })
        document.body.appendChild(nav)
        return nav
    }

    const wheel = (target, init) => {
        const event = new WheelEvent('wheel', { bubbles: true, cancelable: true, ...init })
        target.dispatchEvent(event)
        return event
    }

    beforeEach(() => {
        localStorage.clear()
        root.classList.remove('nav-dock-min')
        window.Alpine.store = vi.fn(() => ({ close: vi.fn() }))
    })

    afterEach(() => {
        root.classList.remove('nav-dock-min')
    })

    test('a vertical wheel over an overflowing docked bar scrolls it sideways and cancels the page scroll', () => {
        root.classList.add('nav-dock-bottom')
        triggerInit()
        const nav = overflowingNav('ltr')

        const event = wheel(nav, { deltaY: 40 })

        expect(nav.scrollLeft).toBe(40)
        expect(event.defaultPrevented).toBe(true)
    })

    test('in an RTL bar the same wheel direction moves the other way', () => {
        root.classList.add('nav-dock-bottom')
        triggerInit()
        const nav = overflowingNav('rtl')

        wheel(nav, { deltaY: 40 })

        expect(nav.scrollLeft).toBe(-40)
    })

    test('the wheel is left alone when not docked, when collapsed, when nothing overflows, or when it is already horizontal', () => {
        triggerInit()
        const nav = overflowingNav()

        expect(wheel(nav, { deltaY: 40 }).defaultPrevented).toBe(false)

        root.classList.add('nav-dock-bottom', 'nav-dock-min')
        expect(wheel(nav, { deltaY: 40 }).defaultPrevented).toBe(false)

        root.classList.remove('nav-dock-min')
        expect(wheel(nav, { deltaX: 60, deltaY: 10 }).defaultPrevented).toBe(false)

        Object.defineProperty(nav, 'scrollWidth', { value: 300, configurable: true })
        expect(wheel(nav, { deltaY: 40 }).defaultPrevented).toBe(false)
    })

    test('the active item is scrolled to the center of the bar after init', async () => {
        root.classList.add('nav-dock-bottom')
        const active = document.createElement('li')
        active.className = 'fi-sidebar-item fi-active'
        active.scrollIntoView = vi.fn()
        document.body.appendChild(active)

        triggerInit()
        await new Promise((r) => setTimeout(r, 0))
        await new Promise((r) => setTimeout(r, 0))

        expect(active.scrollIntoView).toHaveBeenCalledWith({ inline: 'center', block: 'nearest' })
    })
})

describe('nav-dock — start/end arrow buttons for an overflowing bar (2026-11-02)', () => {
    const root = document.documentElement

    const dockWithArrows = ({ direction = 'ltr', scrollWidth = 1000, clientWidth = 400, scrollLeft = 0 } = {}) => {
        const sidebar = document.createElement('div')
        sidebar.className = 'fi-sidebar'
        const start = document.createElement('button')
        start.className = 'dock-arrow'
        start.dataset.edge = 'start'
        start.hidden = true
        const end = document.createElement('button')
        end.className = 'dock-arrow'
        end.dataset.edge = 'end'
        end.hidden = true
        const nav = document.createElement('nav')
        nav.className = 'fi-sidebar-nav'
        nav.style.direction = direction
        Object.defineProperty(nav, 'scrollWidth', { value: scrollWidth, configurable: true })
        Object.defineProperty(nav, 'clientWidth', { value: clientWidth, configurable: true })
        nav.scrollLeft = scrollLeft
        nav.scrollBy = vi.fn()
        sidebar.append(start, nav, end)
        document.body.appendChild(sidebar)

        return { nav, start, end }
    }

    beforeEach(() => {
        localStorage.clear()
        root.classList.remove('nav-dock-min')
        root.classList.add('nav-dock-bottom')
        window.Alpine.store = vi.fn(() => ({ close: vi.fn() }))
    })

    afterEach(() => {
        root.classList.remove('nav-dock-min')
    })

    test('only the end arrow shows at the start of an overflowing bar', () => {
        const { start, end } = dockWithArrows()

        triggerInit()

        expect(start.hidden).toBe(true)
        expect(end.hidden).toBe(false)
    })

    test('both arrows show mid-way, and only the start arrow shows at the far end', () => {
        const { nav, start, end } = dockWithArrows()
        triggerInit()

        nav.scrollLeft = 200
        nav.dispatchEvent(new Event('scroll'))
        expect(start.hidden).toBe(false)
        expect(end.hidden).toBe(false)

        nav.scrollLeft = 600
        nav.dispatchEvent(new Event('scroll'))
        expect(start.hidden).toBe(false)
        expect(end.hidden).toBe(true)
    })

    test('both arrows stay hidden when everything fits', () => {
        const { start, end } = dockWithArrows({ scrollWidth: 400 })

        triggerInit()

        expect(start.hidden).toBe(true)
        expect(end.hidden).toBe(true)
    })

    test('clicking an arrow scrolls the bar by 60% of its visible width in the reading direction', () => {
        const ltr = dockWithArrows()
        triggerInit()

        ltr.end.click()
        expect(ltr.nav.scrollBy).toHaveBeenCalledTimes(1)
        expect(ltr.nav.scrollBy).toHaveBeenCalledWith({ left: 240, behavior: 'smooth' })

        ltr.start.click()
        expect(ltr.nav.scrollBy).toHaveBeenLastCalledWith({ left: -240, behavior: 'smooth' })
    })

    test('in an RTL bar the end arrow scrolls the opposite way', () => {
        document.body.innerHTML = ''
        const rtl = dockWithArrows({ direction: 'rtl' })
        triggerInit()

        rtl.end.click()
        expect(rtl.nav.scrollBy).toHaveBeenCalledWith({ left: -240, behavior: 'smooth' })

        rtl.start.click()
        expect(rtl.nav.scrollBy).toHaveBeenLastCalledWith({ left: 240, behavior: 'smooth' })
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
