import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import watchIcons from '../guard.js'

let mark
let report
let stops = []
const watch = (...args) => { const stop = watchIcons(...args); stops.push(stop); return stop }

beforeEach(() => {
    vi.useFakeTimers()
    mark = vi.fn()
    report = vi.fn()
    document.body.innerHTML = ''
    Object.defineProperty(document, 'readyState', { configurable: true, value: 'complete' })
    Range.prototype.getBoundingClientRect = function () {
        return { width: this.toString().trim().length > 6 ? 120 : 24 }
    }
})

afterEach(() => {
    stops.splice(0).forEach((stop) => stop())
    delete Range.prototype.getBoundingClientRect
    vi.restoreAllMocks()
    vi.useRealTimers()
})

function icons(...names) {
    document.body.innerHTML = names.map((n) => `<span class="material-symbols-rounded"> ${n} </span>`).join('')
}

const settle = () => vi.advanceTimersByTimeAsync(6000)

describe('icon-guard', () => {
    test('reports each raw icon name to the server exactly once, however often it rescans', async () => {
        icons('home', 'brand_new_icon')
        watch({ mark, report })
        await settle()
        expect(report).toHaveBeenCalledTimes(1)
        expect(report).toHaveBeenCalledWith('brand_new_icon')

        icons('home', 'brand_new_icon')
        document.dispatchEvent(new Event('click'))
        await settle()
        expect(report).toHaveBeenCalledTimes(1)
    })

    test('never reports an icon that renders fine', async () => {
        icons('home', 'search')
        watch({ mark, report })
        await settle()
        expect(report).not.toHaveBeenCalled()
    })

    test('does nothing when every icon renders as one glyph', async () => {
        icons('home', 'search')
        watch({ mark, report })
        await settle()
        expect(mark).not.toHaveBeenCalled()
    })

    test('marks only the icons that are visibly rendering as raw text, in idle time', async () => {
        icons('home', 'brand_new_icon', 'another_new_one')
        watch({ mark, report })
        expect(mark).not.toHaveBeenCalled()
        await settle()
        expect(mark).toHaveBeenCalledTimes(2)
        expect(mark.mock.calls.map(([el]) => el.textContent.trim())).toEqual(['brand_new_icon', 'another_new_one'])
    })

    test('the default action switches just that element to the full font, never the page', async () => {
        icons('home', 'brand_new_icon')
        watch()
        await settle()
        const [home, odd] = document.querySelectorAll('.material-symbols-rounded')
        expect(odd.classList.contains('ms-full')).toBe(true)
        expect(home.classList.contains('ms-full')).toBe(false)
        expect(document.documentElement.classList.contains('ms-full')).toBe(false)
    })

    test('a short name that renders as one glyph is never touched', async () => {
        icons('mic')
        watch({ mark, report })
        await settle()
        expect(mark).not.toHaveBeenCalled()
    })

    test('ignores text that is not an icon name', async () => {
        document.body.innerHTML = '<span class="material-symbols-rounded">✨ سلام</span>'
        watch({ mark, report })
        await settle()
        expect(mark).not.toHaveBeenCalled()
    })

    test('re-checks after a click reveals a new raw icon, throttled, and never marks the same element twice', async () => {
        icons('home')
        watch({ mark, report })
        await settle()
        expect(mark).not.toHaveBeenCalled()

        icons('home', 'late_icon_name')
        document.dispatchEvent(new Event('click'))
        document.dispatchEvent(new Event('click'))
        await settle()
        expect(mark).toHaveBeenCalledTimes(1)

        document.querySelectorAll('.material-symbols-rounded')[1].classList.add('ms-full')
        document.dispatchEvent(new Event('click'))
        await settle()
        expect(mark).toHaveBeenCalledTimes(1)
    })

})
