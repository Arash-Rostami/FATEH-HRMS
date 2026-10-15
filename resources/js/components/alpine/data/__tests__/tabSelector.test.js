import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import tabSelector from '../tabSelector.js'

global.ResizeObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
}

function buildBar({ widths, activeIndex = -1, rowClientWidth, barClientWidth = 999, siblingWidth = 0 }) {
    document.body.innerHTML = `
        <nav id="row" style="display:flex;column-gap:8px;padding-left:4px;padding-right:4px">
            <div id="bar"></div>
            <button id="sibling"></button>
        </nav>
    `
    const row = document.getElementById('row')
    const bar = document.getElementById('bar')
    const sibling = document.getElementById('sibling')

    Object.defineProperty(row, 'clientWidth', { value: rowClientWidth, configurable: true })
    Object.defineProperty(bar, 'clientWidth', { value: barClientWidth, configurable: true })
    Object.defineProperty(sibling, 'offsetWidth', { value: siblingWidth, configurable: true })
    Object.defineProperty(sibling, 'offsetParent', { value: row, configurable: true })

    widths.forEach((w, i) => {
        const el = document.createElement('button')
        el.dataset.tabPill = 'p' + (i + 1)
        if (i === activeIndex) el.setAttribute('data-active', '')
        bar.appendChild(el)
        Object.defineProperty(el, 'offsetWidth', { value: w, configurable: true })
    })

    return { row, bar, sibling }
}

function withSelector(dom) {
    const t = tabSelector()
    t.$el = dom.bar
    return t
}

function hiddenStylesOf(bar) {
    return Array.from(bar.querySelectorAll('[data-tab-pill]')).reduce((acc, el) => {
        acc[el.dataset.tabPill] = el.style.display === 'none'
        return acc
    }, {})
}

beforeEach(() => {
    document.body.innerHTML = ''
})

afterEach(() => {
    vi.restoreAllMocks()
})

describe('tabSelector._fit — budget-driven greedy hide, with KEBAB_RESERVE=58', () => {
    test('hides nothing when every pill fits within the available width', () => {
        const dom = buildBar({ widths: [60, 60, 60], activeIndex: -1, rowClientWidth: 500 })
        const t = withSelector(dom)
        t._fit()
        expect(t.hidden).toEqual([])
    })

    test('hides no pills and does not throw when there are none at all', () => {
        const dom = buildBar({ widths: [], rowClientWidth: 500 })
        const t = withSelector(dom)
        expect(() => t._fit()).not.toThrow()
        expect(t.hidden).toEqual([])
    })

    test('greedily hides the earliest pills that would overflow the budget, keeping the active one exempt', () => {
        const dom = buildBar({ widths: [80, 80, 80, 80, 80], activeIndex: 2, rowClientWidth: 362 })
        const t = withSelector(dom)

        t._fit()

        expect(t.hidden.sort()).toEqual(['p4', 'p5'])
        expect(hiddenStylesOf(dom.bar)).toMatchObject({ p4: true, p5: true, p1: false, p2: false, p3: false })
    })

    test('forcing the active pill in despite overflow triggers the second pass to evict an earlier, already-accepted pill', () => {
        const dom = buildBar({ widths: [60, 60, 60, 60, 60], activeIndex: 2, rowClientWidth: 224 })
        const t = withSelector(dom)

        t._fit()

        expect(t.hidden).toEqual(['p4', 'p5', 'p2'])
    })

    test('never hides the very last remaining visible pill, even under an extremely tight budget', () => {
        const dom = buildBar({ widths: [60, 60], activeIndex: 0, rowClientWidth: 100 })
        const t = withSelector(dom)

        t._fit()

        expect(t.hidden).toEqual(['p2'])
    })

    test('is a no-op when the bar itself has no width yet (not laid out)', () => {
        const dom = buildBar({ widths: [60, 60], rowClientWidth: 500, barClientWidth: 0 })
        const t = withSelector(dom)
        t._fit()
        expect(t.hidden).toEqual([])
    })
})

describe('tabSelector._avail — walking up past a sole-child wrapper', () => {
    test('ignores the cramped sole-child wrapper and measures against the real flex row instead', () => {
        document.body.innerHTML = `
            <nav id="row" style="display:flex;column-gap:0px">
                <div id="wrapper"><div id="bar"></div></div>
                <button id="sibling"></button>
            </nav>
        `
        const row = document.getElementById('row')
        const wrapper = document.getElementById('wrapper')
        const bar = document.getElementById('bar')
        const sibling = document.getElementById('sibling')

        // A deliberately tiny wrapper width that WOULD force a hide if _avail
        // used it directly instead of walking up to the real flex row.
        Object.defineProperty(wrapper, 'clientWidth', { value: 150, configurable: true })
        Object.defineProperty(wrapper, 'offsetParent', { value: row, configurable: true })
        Object.defineProperty(row, 'clientWidth', { value: 1000, configurable: true })
        Object.defineProperty(sibling, 'offsetParent', { value: row, configurable: true })
        Object.defineProperty(sibling, 'offsetWidth', { value: 0, configurable: true })
        Object.defineProperty(bar, 'clientWidth', { value: 999, configurable: true })

        const widths = [150, 150]
        widths.forEach((w, i) => {
            const el = document.createElement('button')
            el.dataset.tabPill = 'p' + (i + 1)
            if (i === 0) el.setAttribute('data-active', '')
            bar.appendChild(el)
            Object.defineProperty(el, 'offsetWidth', { value: w, configurable: true })
        })

        const t = tabSelector()
        t.$el = bar

        t._fit()

        expect(t.hidden).toEqual([])
    })
})

describe('tabSelector._avail — absolutely-positioned siblings', () => {
    test('does not bill an absolute sibling (floating redirect anchor) to the tab budget', () => {
        document.body.innerHTML = `
            <nav id="row" style="display:flex;column-gap:8px">
                <div id="bar"></div>
                <button id="sibling" style="position:absolute"></button>
            </nav>
        `
        const row = document.getElementById('row')
        const bar = document.getElementById('bar')
        const sibling = document.getElementById('sibling')

        Object.defineProperty(row, 'clientWidth', { value: 280, configurable: true })
        Object.defineProperty(sibling, 'offsetParent', { value: row, configurable: true })
        Object.defineProperty(sibling, 'offsetWidth', { value: 32, configurable: true })
        Object.defineProperty(bar, 'clientWidth', { value: 999, configurable: true })

        const widths = [70, 70, 70]
        widths.forEach((w, i) => {
            const el = document.createElement('button')
            el.dataset.tabPill = 'p' + (i + 1)
            if (i === 0) el.setAttribute('data-active', '')
            bar.appendChild(el)
            Object.defineProperty(el, 'offsetWidth', { value: w, configurable: true })
        })

        const t = tabSelector()
        t.$el = bar
        t._fit()

        expect(t.hidden).toEqual([])
    })
})

describe('tabSelector._schedule — rAF coalescing', () => {
    beforeEach(() => {
        global.requestAnimationFrame = (cb) => setTimeout(cb, 0)
        global.cancelAnimationFrame = (id) => clearTimeout(id)
        vi.useFakeTimers()
    })

    afterEach(() => {
        vi.useRealTimers()
    })

    test('multiple schedule() calls before the frame fires only run _fit once', () => {
        const dom = buildBar({ widths: [60], rowClientWidth: 500 })
        const t = withSelector(dom)
        t._fit = vi.fn()

        t._schedule()
        t._schedule()
        t._schedule()

        vi.runAllTimers()

        expect(t._fit).toHaveBeenCalledTimes(1)
    })
})

describe('tabSelector.init / destroy', () => {
    beforeEach(() => {
        global.requestAnimationFrame = (cb) => setTimeout(cb, 0)
        global.cancelAnimationFrame = (id) => clearTimeout(id)
    })

    test('init wires a resize listener plus Resize/MutationObservers and fits immediately', () => {
        const dom = buildBar({ widths: [60], rowClientWidth: 500 })
        const t = withSelector(dom)
        t._fit = vi.fn()

        t.init()

        expect(t._fit).toHaveBeenCalledTimes(1)
        expect(t._ro).toBeTruthy()
        expect(t._mo).toBeTruthy()
    })

    test('destroy tears down the resize listener and both observers, and cancels any pending frame', () => {
        const dom = buildBar({ widths: [60], rowClientWidth: 500 })
        const t = withSelector(dom)
        t.init()

        const roDisconnect = vi.spyOn(t._ro, 'disconnect')
        const moDisconnect = vi.spyOn(t._mo, 'disconnect')
        const removeSpy = vi.spyOn(window, 'removeEventListener')

        t.destroy()

        expect(roDisconnect).toHaveBeenCalledTimes(1)
        expect(moDisconnect).toHaveBeenCalledTimes(1)
        expect(removeSpy).toHaveBeenCalledWith('resize', t._onResize)
    })
})
