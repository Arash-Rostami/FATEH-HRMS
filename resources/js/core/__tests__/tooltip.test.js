import { describe, expect, test, vi, beforeEach } from 'vitest'
import '../tooltip.js'

function hover(el) {
    el.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }))
}

function tipEl() {
    return document.body.querySelector('[role="tooltip"]')
}

function rect(overrides = {}) {
    return { top: 0, left: 0, right: 0, bottom: 0, width: 0, height: 0, ...overrides }
}

async function flushReveal() {
    await new Promise((r) => setTimeout(r, 0))
    await new Promise((r) => setTimeout(r, 0))
}

beforeEach(() => {
    document.body.innerHTML = ''
    document.head.innerHTML = ''
    global.requestAnimationFrame = (cb) => setTimeout(cb, 0)
    global.cancelAnimationFrame = (id) => clearTimeout(id)
    Object.defineProperty(HTMLElement.prototype, 'offsetWidth', { configurable: true, value: 40 })
    Object.defineProperty(HTMLElement.prototype, 'offsetHeight', { configurable: true, value: 20 })
})

describe('tooltip — target resolution (resolve) and text extraction', () => {
    test('a [data-tip] child of the hovered element becomes the holder, positioned per data-tip-pos', async () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="Hello" data-tip-pos="left"></span></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect({ left: 100, right: 200, top: 50, bottom: 90, width: 100, height: 40 }))

        hover(wrap)
        await flushReveal()

        expect(tipEl().textContent).toBe('Hello')
    })

    test('a title attribute is converted to data-title, the title attribute removed, and aria-label backfilled', async () => {
        document.body.innerHTML = '<button id="btn" title="Click me"></button>'
        const btn = document.getElementById('btn')
        vi.spyOn(btn, 'getBoundingClientRect').mockReturnValue(rect())

        hover(btn)
        await flushReveal()

        expect(tipEl().textContent).toBe('Click me')
        expect(btn.hasAttribute('title')).toBe(false)
        expect(btn.dataset.title).toBe('Click me')
        expect(btn.getAttribute('aria-label')).toBe('Click me')
    })

    test('an existing aria-label on the title-holder element is left untouched', async () => {
        document.body.innerHTML = '<button id="btn" title="Click me" aria-label="Existing"></button>'
        const btn = document.getElementById('btn')
        vi.spyOn(btn, 'getBoundingClientRect').mockReturnValue(rect())

        hover(btn)
        await flushReveal()

        expect(btn.getAttribute('aria-label')).toBe('Existing')
    })

    test('re-hovering an already-converted element resolves via the data-title branch without a title attribute', async () => {
        document.body.innerHTML = '<button id="btn" title="Click me"></button><div id="blank"></div>'
        const btn = document.getElementById('btn')
        vi.spyOn(btn, 'getBoundingClientRect').mockReturnValue(rect())

        hover(btn)
        await flushReveal()
        hover(document.getElementById('blank'))
        hover(btn)
        await flushReveal()

        expect(tipEl().textContent).toBe('Click me')
        expect(btn.hasAttribute('title')).toBe(false)
    })

    test('a .group ancestor resolves its first [data-tip] descendant', async () => {
        document.body.innerHTML = '<div class="group" id="g"><button id="trigger"></button><span data-tip="Group tip"></span></div>'
        const group = document.getElementById('g')
        const trigger = document.getElementById('trigger')
        vi.spyOn(group, 'getBoundingClientRect').mockReturnValue(rect({ bottom: 40 }))

        hover(trigger)
        await flushReveal()

        expect(tipEl().textContent).toBe('Group tip')
    })

    test('hovering an <iframe> stops resolution immediately and hides any active tooltip', async () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="Hello"></span></div><iframe id="frame"></iframe>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect())
        hover(wrap)
        await flushReveal()
        expect(tipEl().style.opacity).toBe('1')

        hover(document.getElementById('frame'))

        expect(tipEl().style.opacity).toBe('0')
    })

    test('readText falls back to the holder\'s own textContent when data-tip is an empty string', async () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="">Fallback Text</span></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect())

        hover(wrap)
        await flushReveal()

        expect(tipEl().textContent).toBe('Fallback Text')
    })

    test('an empty resolved text (no data-tip value, no textContent) hides the tooltip instead of showing blank', async () => {
        document.body.innerHTML = '<div id="shown"><span data-tip="Visible"></span></div><div id="wrap"><span data-tip=""></span></div>'
        const shown = document.getElementById('shown')
        vi.spyOn(shown, 'getBoundingClientRect').mockReturnValue(rect())
        hover(shown)
        await flushReveal()
        expect(tipEl().style.opacity).toBe('1')

        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect())
        hover(wrap)
        await flushReveal()

        expect(tipEl().style.opacity).toBe('0')
    })

    test('hovering a target with no resolvable tooltip hides any currently active tooltip', async () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="Hello"></span></div><div id="plain"></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect())
        hover(wrap)
        await flushReveal()
        expect(tipEl().style.opacity).toBe('1')

        hover(document.getElementById('plain'))

        expect(tipEl().style.opacity).toBe('0')
    })

    test('re-hovering the already-active holder is a no-op (no position recomputation)', async () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="X"></span></div>'
        const wrap = document.getElementById('wrap')
        const getRect = vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect())

        hover(wrap)
        await flushReveal()
        expect(getRect).toHaveBeenCalledTimes(1)

        hover(wrap)

        expect(getRect).toHaveBeenCalledTimes(1)
    })
})

describe('tooltip — show() positioning math', () => {
    test('"bottom" (default) position centers horizontally under the anchor with a 12px gap', () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="X"></span></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect({ left: 100, right: 200, top: 50, bottom: 90, width: 100, height: 40 }))

        hover(wrap)

        expect(tipEl().style.left).toBe('130px')
        expect(tipEl().style.top).toBe('102px')
    })

    test('"right" position places the tooltip to the right of the anchor, vertically centered', () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="X" data-tip-pos="right"></span></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect({ left: 100, right: 200, top: 50, bottom: 90, width: 100, height: 40 }))

        hover(wrap)

        expect(tipEl().style.left).toBe('212px')
        expect(tipEl().style.top).toBe('60px')
    })

    test('"top" position places the tooltip above the anchor when there is enough room', () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="X" data-tip-pos="top"></span></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect({ left: 100, right: 200, top: 200, bottom: 240, width: 100, height: 40 }))

        hover(wrap)

        expect(tipEl().style.top).toBe('168px')
    })

    test('"top" flips to below the anchor when there is not enough room above it', () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="X" data-tip-pos="top"></span></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect({ left: 100, right: 200, top: 10, bottom: 50, width: 100, height: 40 }))

        hover(wrap)

        expect(tipEl().style.top).toBe('62px')
    })

    test('the horizontal position is clamped to the left viewport edge (PAD = 8px)', () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="X"></span></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect({ left: -100, right: -80, top: 0, bottom: 20, width: 20, height: 20 }))

        hover(wrap)

        expect(tipEl().style.left).toBe('8px')
    })

    test('the horizontal position is clamped to the right viewport edge', () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="X"></span></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect({ left: 2000, right: 2020, top: 0, bottom: 20, width: 20, height: 20 }))

        hover(wrap)

        expect(tipEl().style.left).toBe('976px')
    })
})

describe('tooltip — opacity reveal is debounced across two animation frames', () => {
    test('opacity stays 0 until both scheduled animation frames have run', async () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="X"></span></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect())

        hover(wrap)
        expect(tipEl().style.opacity).toBe('0')

        await new Promise((r) => setTimeout(r, 0))
        expect(tipEl().style.opacity).toBe('0')

        await new Promise((r) => setTimeout(r, 0))
        expect(tipEl().style.opacity).toBe('1')
    })
})

describe('tooltip — hide triggers', () => {
    test('scrolling the window hides an active tooltip', async () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="X"></span></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect())
        hover(wrap)
        await flushReveal()
        expect(tipEl().style.opacity).toBe('1')

        window.dispatchEvent(new Event('scroll'))

        expect(tipEl().style.opacity).toBe('0')
    })

    test('resizing the window hides an active tooltip', async () => {
        document.body.innerHTML = '<div id="wrap"><span data-tip="X"></span></div>'
        const wrap = document.getElementById('wrap')
        vi.spyOn(wrap, 'getBoundingClientRect').mockReturnValue(rect())
        hover(wrap)
        await flushReveal()

        window.dispatchEvent(new Event('resize'))

        expect(tipEl().style.opacity).toBe('0')
    })
})
