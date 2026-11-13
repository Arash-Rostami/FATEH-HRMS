import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import peek from '../peek.js'

const railButton = (top) => ({
    getBoundingClientRect: () => ({ top, left: 900, height: 48 }),
    getAttribute: () => 'گالری',
    querySelector: () => ({ textContent: ' photo_library ' }),
})
const el = railButton(100)
const lowEl = railButton(760)

function make() {
    const c = peek()
    c.$nextTick = (cb) => cb()
    return c
}

beforeEach(() => {
    Object.defineProperty(document, 'hidden', { configurable: true, get: () => false })
    vi.useFakeTimers()
    window.innerWidth = 1000
    window.innerHeight = 800
})

afterEach(() => {
    vi.useRealTimers()
})

describe('peek miniature capture', () => {
    test('copies the active tab as inert markup: live attributes, scripts and media are dropped, x-cloak is kept', () => {
        document.body.innerHTML = '<main><div wire:id="a" x-data="g()" id="i" class="keep">'
            + '<span wire:click="go" :class="y" @click="z" onclick="evil()" x-cloak class="s">hi</span>'
            + '<script>bad()</script><video></video></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'post' } }
        c.capture()
        const doc = c.frameFor('post')

        expect(doc).toContain('<!doctype html>')
        expect(doc).toContain('class="keep"')
        expect(doc).toContain('x-cloak')
        expect(doc).toContain('hi')
        for (const bad of ['wire:id', 'x-data', 'wire:click', 'onclick', '<script', '<video', ' id="i"']) {
            expect(doc).not.toContain(bad)
        }
    })

    test('nothing is copied until the rail asks for it: no observer, no timers', async () => {
        document.body.innerHTML = '<main><div wire:id="a">on-demand</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'links' } }
        expect(c.init).toBeUndefined()
        await vi.advanceTimersByTimeAsync(5000)
        expect(c.frameFor('links')).toBeNull()
        c.capture()
        expect(c.frameFor('links')).toContain('on-demand')
        c.destroy()
    })

    test('a second copy within the refresh window keeps the first one', () => {
        document.body.innerHTML = '<main><div wire:id="a">first</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'throttle' } }
        c.capture()
        document.body.innerHTML = '<main><div wire:id="a">second</div></main>'
        c.capture()
        expect(c.frameFor('throttle')).toContain('first')
        expect(c.frameFor('throttle')).not.toContain('second')
    })

    test('a very long list is cut to its first rows so a huge page like Status still gets a miniature', () => {
        const rows = Array.from({ length: 900 }, (_, i) => '<li class="row">r' + i + '</li>').join('')
        document.body.innerHTML = '<main><div wire:id="a"><ul>' + rows + '</ul></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'status' } }
        c.capture()
        const doc = c.frameFor('status')

        expect(doc).not.toBeNull()
        expect(doc).toContain('r0<')
        expect(doc).toContain('r23<')
        expect(doc).not.toContain('r24<')
        expect(doc).not.toContain('r899<')
    })

    test('a dropped node at the end of a deep wrapper does not stop the clean-up of what follows it', () => {
        document.body.innerHTML = '<main><div wire:id="a"><section><div><script>bad()</script></div></section>'
            + '<p wire:click="x" x-data="y" onclick="z()" class="tail">tail</p></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'deep' } }
        c.capture()
        const doc = c.frameFor('deep')

        expect(doc).toContain('class="tail"')
        for (const bad of ['wire:click', 'x-data', 'onclick', '<script']) {
            expect(doc).not.toContain(bad)
        }
    })

    test('only real live attributes are stripped; look-alike attributes survive', () => {
        document.body.innerHTML = '<main><div wire:id="a"><i class="idx" data-ok="1" idle="2" id="gone" onclick="z()">x</i></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'attrs' } }
        c.capture()
        const doc = c.frameFor('attrs')

        expect(doc).toContain('idle="2"')
        expect(doc).toContain('data-ok="1"')
        expect(doc).not.toContain('id="gone"')
        expect(doc).not.toContain('onclick')
    })

    test('loading overlays and hidden parts are not copied as visible content', () => {
        document.body.innerHTML = '<main><div wire:id="a"><div wire:loading class="spin">wait</div>'
            + '<div style="display:none">secret-hidden</div><p>shown</p></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'overlay' } }
        c.capture()
        const doc = c.frameFor('overlay')

        expect(doc).not.toContain('secret-hidden')
        expect(doc).toContain('shown')
        expect(doc).toContain('display: none')
    })

    test('a pane over the size cap gets no miniature and is not re-copied inside the refresh window', () => {
        const big = '<p>' + 'x'.repeat(700000) + '</p>'
        document.body.innerHTML = '<main><div wire:id="a">' + big + '</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'huge' } }
        c.capture()
        expect(c.frameFor('huge')).toBeNull()
        document.body.innerHTML = '<main><div wire:id="a">small</div></main>'
        c.capture()
        expect(c.frameFor('huge')).toBeNull()
    })

    test('a copy older than the refresh window is replaced', async () => {
        document.body.innerHTML = '<main><div wire:id="a">old-copy</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'aging' } }
        c.capture()
        vi.setSystemTime(Date.now() + 11000)
        document.body.innerHTML = '<main><div wire:id="a">new-copy</div></main>'
        c.capture()
        expect(c.frameFor('aging')).toContain('new-copy')
    })

    test('a hidden browser tab copies nothing', () => {
        Object.defineProperty(document, 'hidden', { configurable: true, get: () => true })
        document.body.innerHTML = '<main><div wire:id="a">bg</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'background' } }
        c.capture()
        expect(c.frameFor('background')).toBeNull()
    })

    test('below the desktop breakpoint nothing is copied', () => {
        const original = window.matchMedia
        window.matchMedia = vi.fn(() => ({ matches: false }))
        document.body.innerHTML = '<main><div wire:id="a">phone</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'phone' } }
        c.capture()
        expect(c.frameFor('phone')).toBeNull()
        window.matchMedia = original
    })

    test('a tab that was never captured has no miniature', () => {
        const c = make()
        expect(c.frameFor('never-seen')).toBeNull()
    })

    test('hovering the icon of the tab you are on builds its miniature even if the pointer never re-entered the rail', async () => {
        document.body.innerHTML = '<main><div wire:id="a">fresh-tab</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'just-clicked' } }
        c.show('just-clicked', el)
        await vi.advanceTimersByTimeAsync(500)
        expect(c.frame).toContain('fresh-tab')
    })

    test('opening the card attaches the stored miniature to it', async () => {
        document.body.innerHTML = '<main><div wire:id="a">content</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'faqs' } }
        c.capture()
        c.show('faqs', el)
        await vi.advanceTimersByTimeAsync(500)
        expect(c.frame).toContain('content')
    })
})

describe('peek card', () => {
    beforeEach(() => {
        document.body.innerHTML = '<main><div wire:id="a">pane</div></main>'
    })

    test('opens after the hover delay with the tab name and icon read from the rail button', async () => {
        const c = make()
        c.$root = { dataset: { active: 'post' } }
        c.show('never-opened', el)
        expect(c.open).toBe(false)
        await vi.advanceTimersByTimeAsync(500)
        expect(c.open).toBe(true)
        expect(c.label).toBe('گالری')
        expect(c.icon).toBe('photo_library')
        expect(c.frame).toBeNull()
        expect(c.pos.right).toBe(1000 - 900 + 12)
    })

    test('the card is clamped inside the window, even for an icon near the bottom edge', async () => {
        const c = make()
        c.$root = { dataset: { active: 'post' } }
        c.show('x', lowEl)
        await vi.advanceTimersByTimeAsync(500)
        expect(c.pos.top).toBeGreaterThanOrEqual(8)
        expect(c.pos.top + 260).toBeLessThanOrEqual(800 - 8)
        expect(c.pos.right).toBeGreaterThanOrEqual(8)
    })

    test('leaving before the delay never opens it', async () => {
        const c = make()
        c.$root = { dataset: { active: 'post' } }
        c.show('x', el)
        c.hide()
        await vi.advanceTimersByTimeAsync(600)
        expect(c.open).toBe(false)
    })

    test('moving onto the card keeps it open', async () => {
        const c = make()
        c.$root = { dataset: { active: 'post' } }
        c.show('x', el)
        await vi.advanceTimersByTimeAsync(500)
        c.hide()
        c.keep()
        await vi.advanceTimersByTimeAsync(300)
        expect(c.open).toBe(true)
    })
})
