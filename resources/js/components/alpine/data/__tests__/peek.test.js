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

const snap = async (c) => {
    c.capture()
    await vi.advanceTimersByTimeAsync(50)
}

beforeEach(() => {
    Object.defineProperty(document, 'hidden', { configurable: true, get: () => false })
    sessionStorage.clear()
    delete document.documentElement.dataset.uid
    vi.useFakeTimers()
    window.innerWidth = 1000
    window.innerHeight = 800
})

afterEach(() => {
    vi.useRealTimers()
    vi.unstubAllGlobals()
})

describe('peek miniature capture', () => {
    test('copies the active tab as inert markup: live attributes, scripts and media are dropped, x-cloak is kept', async () => {
        document.body.innerHTML = '<main><div wire:id="a" x-data="g()" id="i" class="keep">'
            + '<span wire:click="go" :class="y" @click="z" onclick="evil()" x-cloak class="s">hi</span>'
            + '<script>bad()</script><video></video></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'post' } }
        await snap(c)
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
        expect(c.frameFor('links')).toBeNull()
        await vi.advanceTimersByTimeAsync(50)
        expect(c.frameFor('links')).toContain('on-demand')
        c.destroy()
    })

    test('a second copy within the refresh window keeps the first one', async () => {
        document.body.innerHTML = '<main><div wire:id="a">first</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'throttle' } }
        await snap(c)
        document.body.innerHTML = '<main><div wire:id="a">second</div></main>'
        await snap(c)
        expect(c.frameFor('throttle')).toContain('first')
        expect(c.frameFor('throttle')).not.toContain('second')
    })

    test('a very long list is cut to its first rows so a huge page like Status still gets a miniature', async () => {
        const rows = Array.from({ length: 900 }, (_, i) => '<li class="row">r' + i + '</li>').join('')
        document.body.innerHTML = '<main><div wire:id="a"><ul>' + rows + '</ul></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'status' } }
        await snap(c)
        const doc = c.frameFor('status')

        expect(doc).not.toBeNull()
        expect(doc).toContain('r0<')
        expect(doc).toContain('r23<')
        expect(doc).not.toContain('r24<')
        expect(doc).not.toContain('r899<')
    })

    test('a dropped node at the end of a deep wrapper does not stop the clean-up of what follows it', async () => {
        document.body.innerHTML = '<main><div wire:id="a"><section><div><script>bad()</script></div></section>'
            + '<p wire:click="x" x-data="y" onclick="z()" class="tail">tail</p></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'deep' } }
        await snap(c)
        const doc = c.frameFor('deep')

        expect(doc).toContain('class="tail"')
        for (const bad of ['wire:click', 'x-data', 'onclick', '<script']) {
            expect(doc).not.toContain(bad)
        }
    })

    test('only real live attributes are stripped; look-alike attributes survive', async () => {
        document.body.innerHTML = '<main><div wire:id="a"><i class="idx" data-ok="1" idle="2" id="gone" onclick="z()">x</i></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'attrs' } }
        await snap(c)
        const doc = c.frameFor('attrs')

        expect(doc).toContain('idle="2"')
        expect(doc).toContain('data-ok="1"')
        expect(doc).not.toContain('id="gone"')
        expect(doc).not.toContain('onclick')
    })

    test('loading overlays and hidden parts are not copied as visible content', async () => {
        document.body.innerHTML = '<main><div wire:id="a"><div wire:loading class="spin">wait</div>'
            + '<div style="display:none">secret-hidden</div><p>shown</p></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'overlay' } }
        await snap(c)
        const doc = c.frameFor('overlay')

        expect(doc).not.toContain('secret-hidden')
        expect(doc).not.toContain('wait')
        expect(doc).toContain('shown')
    })

    test('inline style rules are copied as written, not escaped', async () => {
        document.body.innerHTML = '<main><div wire:id="a"><style>.a > .b { color: red }</style><p class="a">x</p></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'styled' } }
        await snap(c)

        expect(c.frameFor('styled')).toContain('.a > .b { color: red }')
    })

    test('markup is copied however deeply it is nested', async () => {
        const depth = 60
        document.body.innerHTML = '<main><div wire:id="a">' + '<div>'.repeat(depth) + 'deep-text' + '</div>'.repeat(depth) + '</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'nested' } }
        await snap(c)

        expect(c.frameFor('nested')).toContain('deep-text')
    })

    test('an image always loads lazily in the miniature, whatever it asked for', async () => {
        document.body.innerHTML = '<main><div wire:id="a"><img src="/a.png" loading="eager" alt="pic"></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'pictured' } }
        await snap(c)
        const doc = c.frameFor('pictured')

        expect(doc).toContain('loading="lazy"')
        expect(doc).not.toContain('loading="eager"')
        expect(doc).toContain('alt="pic"')
    })

    test('text and attribute values with markup characters stay text', async () => {
        document.body.innerHTML = '<main><div wire:id="a"><p title="a&quot;b">1 &lt; 2 &amp; 3</p></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'escaped' } }
        await snap(c)
        const doc = c.frameFor('escaped')

        expect(doc).toContain('1 &lt; 2 &amp; 3')
        expect(doc).toContain('title="a&quot;b"')
    })

    test('a pane over the size cap gets no miniature and is not re-copied inside the refresh window', async () => {
        const big = '<p>' + 'x'.repeat(700000) + '</p>'
        document.body.innerHTML = '<main><div wire:id="a">' + big + '</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'huge' } }
        await snap(c)
        expect(c.frameFor('huge')).toBeNull()
        document.body.innerHTML = '<main><div wire:id="a">small</div></main>'
        await snap(c)
        expect(c.frameFor('huge')).toBeNull()
    })

    test('hovering the tab you are on refreshes an older copy before the card opens', async () => {
        document.body.innerHTML = '<main><div wire:id="a">first-copy</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'aged-hover' } }
        await snap(c)
        vi.setSystemTime(Date.now() + 11000)
        document.body.innerHTML = '<main><div wire:id="a">second-copy</div></main>'

        c.show('aged-hover', el)
        await vi.advanceTimersByTimeAsync(500)

        expect(c.frame).toContain('second-copy')
    })

    test('a copy that finishes after the card opened never swaps a frame that is already showing', async () => {
        document.body.innerHTML = '<main><div wire:id="a">saved-copy</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'after-refresh' } }
        await snap(c)
        await vi.advanceTimersByTimeAsync(1500)

        vi.resetModules()
        vi.stubGlobal('requestIdleCallback', (fn) => setTimeout(fn, 600))
        const refreshed = (await import('../peek.js')).default()
        refreshed.$root = { dataset: { active: 'after-refresh' } }
        refreshed.$nextTick = (cb) => cb()
        vi.setSystemTime(Date.now() + 60000)
        document.body.innerHTML = '<main><div wire:id="a">live-now</div></main>'

        refreshed.show('after-refresh', el)
        await vi.advanceTimersByTimeAsync(500)
        expect(refreshed.frame).toContain('saved-copy')

        await vi.advanceTimersByTimeAsync(1000)
        expect(refreshed.frame).toContain('saved-copy')
        expect(refreshed.frameFor('after-refresh')).toContain('live-now')
    })

    test('a pane that grows over the size cap keeps the last good miniature instead of blanking it', async () => {
        document.body.innerHTML = '<main><div wire:id="a">last-good</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'grown' } }
        await snap(c)
        vi.setSystemTime(Date.now() + 11000)
        document.body.innerHTML = '<main><div wire:id="a"><p>' + 'x'.repeat(700000) + '</p></div></main>'
        await snap(c)
        expect(c.frameFor('grown')).toContain('last-good')
    })

    test('a copy older than the refresh window is replaced', async () => {
        document.body.innerHTML = '<main><div wire:id="a">old-copy</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'aging' } }
        await snap(c)
        vi.setSystemTime(Date.now() + 11000)
        document.body.innerHTML = '<main><div wire:id="a">new-copy</div></main>'
        await snap(c)
        expect(c.frameFor('aging')).toContain('new-copy')
    })

    test('a pane bigger than one slice is still copied whole', async () => {
        const section = '<section>' + '<span>s</span>'.repeat(30) + '</section>'
        document.body.innerHTML = '<main><div wire:id="a">' + section.repeat(29) + '<section><span>tail-end</span></section></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'sliced' } }
        await snap(c)

        expect(c.frameFor('sliced')).toContain('tail-end')
    })

    test('switching tabs mid-copy leaves no miniature for either tab', async () => {
        document.body.innerHTML = '<main><div wire:id="a">old-pane</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'mid-a' } }
        c.capture()
        c.$root.dataset.active = 'mid-b'
        document.body.innerHTML = '<main><div wire:id="a">new-pane</div></main>'
        await vi.advanceTimersByTimeAsync(50)

        expect(c.frameFor('mid-a')).toBeNull()
        expect(c.frameFor('mid-b')).toBeNull()
    })

    test('a pane re-rendered mid-copy is discarded, not stored half-built', async () => {
        vi.stubGlobal('requestIdleCallback', (fn) => setTimeout(fn, 10))
        const section = '<section>' + '<span>s</span>'.repeat(30) + '</section>'
        document.body.innerHTML = '<main><div wire:id="a">' + section.repeat(31) + '</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'torn-tab' } }
        c.capture()
        await vi.advanceTimersByTimeAsync(10)
        document.querySelector('main').firstElementChild.innerHTML = 'torn'
        await vi.advanceTimersByTimeAsync(100)
        expect(c.frameFor('torn-tab')).toBeNull()

        await snap(c)
        expect(c.frameFor('torn-tab')).toContain('torn')
    })

    test('a copy that finishes after the card opened with a placeholder fills it', async () => {
        vi.stubGlobal('requestIdleCallback', (fn) => setTimeout(fn, 600))
        document.body.innerHTML = '<main><div wire:id="a">late-pane</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'late-fill' } }
        c.show('late-fill', el)
        await vi.advanceTimersByTimeAsync(500)
        expect(c.open).toBe(true)
        expect(c.frame).toBeNull()

        await vi.advanceTimersByTimeAsync(200)
        expect(c.frame).toContain('late-pane')
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

    test('a captured miniature survives a page refresh and is shown even when it is very old', async () => {
        document.body.innerHTML = '<main><div wire:id="a">before-refresh</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'persisted' } }
        c.capture()
        await vi.advanceTimersByTimeAsync(1500)
        expect(sessionStorage.getItem('peek::persisted')).toContain('before-refresh')

        vi.resetModules()
        const refreshed = (await import('../peek.js')).default()
        vi.setSystemTime(Date.now() + 7 * 24 * 3600 * 1000)
        expect(refreshed.frameFor('persisted')).toContain('before-refresh')
    })

    test('the stored miniature belongs to one user and is invisible to another', async () => {
        document.documentElement.dataset.uid = '7'
        document.body.innerHTML = '<main><div wire:id="a">private-pane</div></main>'
        const c = make()
        c.$root = { dataset: { active: 'owned' } }
        c.capture()
        await vi.advanceTimersByTimeAsync(1500)
        expect(sessionStorage.getItem('peek:7:owned')).toContain('private-pane')

        document.documentElement.dataset.uid = '8'
        vi.resetModules()
        const other = (await import('../peek.js')).default()
        expect(other.frameFor('owned')).toBeNull()
    })

    test('a pane over the persist cap is shown now but never written to storage', async () => {
        document.body.innerHTML = '<main><div wire:id="a"><p>' + 'x'.repeat(130000) + '</p></div></main>'
        const c = make()
        c.$root = { dataset: { active: 'roomy' } }
        c.capture()
        await vi.advanceTimersByTimeAsync(1500)
        expect(c.frameFor('roomy')).toContain('xxxx')
        expect(sessionStorage.getItem('peek::roomy')).toBeNull()
    })

    test('blocked or full storage never breaks the miniature', async () => {
        document.body.innerHTML = '<main><div wire:id="a">still-works</div></main>'
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('full') })
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('blocked') })
        const c = make()
        c.$root = { dataset: { active: 'blocked' } }
        expect(() => c.capture()).not.toThrow()
        await expect(vi.advanceTimersByTimeAsync(1500)).resolves.not.toThrow()
        expect(c.frameFor('blocked')).toContain('still-works')
        expect(c.frameFor('unknown-tab')).toBeNull()
        vi.restoreAllMocks()
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
