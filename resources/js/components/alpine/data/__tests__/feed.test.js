import { describe, expect, test, vi, beforeEach } from 'vitest'
import feed from '../feed.js'
import { installWireProbe } from '../../mixins/__tests__/_wireMock.js'

beforeEach(() => {
    document.body.innerHTML = ''
    global.Livewire = { hook: vi.fn(() => vi.fn()) }
})

describe('feed.feed(el) — dataset lookup via closest', () => {
    test('reads data-feed off the closest [data-feed] ancestor', () => {
        document.body.innerHTML = '<div data-feed="timeline"><span id="inner"></span></div>'
        const c = feed()
        expect(c.feed(document.getElementById('inner'))).toBe('timeline')
    })

    test('returns undefined when there is no matching ancestor or el is null/undefined', () => {
        const c = feed()
        expect(c.feed(document.createElement('div'))).toBeUndefined()
        expect(c.feed(null)).toBeUndefined()
        expect(c.feed(undefined)).toBeUndefined()
    })
})

describe('feed.toggleMaximize', () => {
    test('opening sets maximizedFeed and calls applyMaximize(true)', () => {
        const c = feed()
        const spy = vi.spyOn(c, 'applyMaximize')
        c.toggleMaximize('timeline')
        expect(c.maximizedFeed).toBe('timeline')
        expect(spy).toHaveBeenCalledWith(true)
    })

    test('toggling the active feed closes it and calls applyMaximize(false)', () => {
        const c = feed()
        const spy = vi.spyOn(c, 'applyMaximize')
        c.maximizedFeed = 'timeline'
        c.toggleMaximize('timeline')
        expect(c.maximizedFeed).toBeNull()
        expect(spy).toHaveBeenCalledWith(false)
    })

    test('opening persists the feed id via _persistMaximize', () => {
        const c = feed()
        const spy = vi.spyOn(c, '_persistMaximize')
        c.toggleMaximize('timeline')
        expect(spy).toHaveBeenCalledWith('timeline')
    })

    test('closing persists null via _persistMaximize', () => {
        const c = feed()
        c.maximizedFeed = 'timeline'
        const spy = vi.spyOn(c, '_persistMaximize')
        c.toggleMaximize('timeline')
        expect(spy).toHaveBeenCalledWith(null)
    })
})

describe('feed.restoreMaximize', () => {
    test('a value matching an existing [data-feed] element re-applies maximize', () => {
        document.body.innerHTML = '<div data-feed="timeline"></div>'
        const c = feed()
        const spy = vi.spyOn(c, 'applyMaximize')
        c.restoreMaximize('timeline')
        expect(c.maximizedFeed).toBe('timeline')
        expect(spy).toHaveBeenCalledWith(true)
    })

    test('a stale value with no matching [data-feed] element cleans up and does nothing else', () => {
        document.body.innerHTML = '<div data-feed="timeline"></div>'
        const c = feed()
        const persistSpy = vi.spyOn(c, '_persistMaximize')
        const applySpy = vi.spyOn(c, 'applyMaximize')
        c.restoreMaximize('gone')
        expect(persistSpy).toHaveBeenCalledWith(null)
        expect(applySpy).not.toHaveBeenCalled()
        expect(c.maximizedFeed).toBeNull()
    })
})

describe('feed.loadMore', () => {
    test('is a no-op while already loading', async () => {
        const c = feed()
        c.loading = true
        c.$wire = { get: vi.fn(() => true), loadMore: vi.fn(() => Promise.resolve()) }
        await c.loadMore()
        expect(c.$wire.loadMore).not.toHaveBeenCalled()
    })

    test('is a no-op when the server reports no more pages', async () => {
        const c = feed()
        c.$wire = { get: vi.fn(() => false), loadMore: vi.fn(() => Promise.resolve()) }
        await c.loadMore()
        expect(c.$wire.loadMore).not.toHaveBeenCalled()
        expect(c.loading).toBe(false)
    })

    test('captures $wire before the await (2 synchronous reads: the hasMorePages guard + loadMore itself)', async () => {
        const c = feed()
        const probed = installWireProbe(c, { get: vi.fn(() => true), loadMore: vi.fn(() => Promise.resolve()) }, 2)
        await c.loadMore()
        expect(probed.loadMore).toHaveBeenCalled()
        expect(c.loading).toBe(false)
    })

    test('a rejected loadMore is logged and swallowed, still resetting loading', async () => {
        const c = feed()
        const errSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
        c.$wire = { get: vi.fn(() => true), loadMore: vi.fn(() => Promise.reject(new Error('x'))) }
        await c.loadMore()
        expect(c.loading).toBe(false)
        expect(errSpy).toHaveBeenCalled()
        errSpy.mockRestore()
    })
})

describe('feed.focusFeed', () => {
    test('captures $wire before the await (1 read), then switches to filmstrip view without re-reading $wire', async () => {
        const c = feed()
        const wire = installWireProbe(c, { focusRecord: vi.fn(() => Promise.resolve()) }, 1)
        c.view = 'grid'
        await c.focusFeed(5)
        expect(wire.focusRecord).toHaveBeenCalledWith(5)
        expect(c.view).toBe('filmstrip')
    })

    test('a rejected focusRecord is logged and swallowed, leaving view untouched', async () => {
        const c = feed()
        const errSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
        c.$wire = { focusRecord: vi.fn(() => Promise.reject(new Error('x'))) }
        c.view = 'grid'
        await c.focusFeed(5)
        expect(c.view).toBe('grid')
        errSpy.mockRestore()
    })
})

describe('feed.scrollNext / scrollPrev', () => {
    test('scroll direction is opposite between next and prev, and both are no-ops without a timeline ref', () => {
        const c = feed()
        c.$refs = {}
        expect(() => c.scrollNext()).not.toThrow()
        expect(() => c.scrollPrev()).not.toThrow()

        const timeline = document.createElement('div')
        Object.defineProperty(timeline, 'offsetWidth', { value: 300 })
        timeline.scrollBy = vi.fn()
        c.$refs = { timeline }

        c.scrollNext()
        expect(timeline.scrollBy).toHaveBeenCalledWith({ left: -300, behavior: 'smooth' })

        c.scrollPrev()
        expect(timeline.scrollBy).toHaveBeenCalledWith({ left: 300, behavior: 'smooth' })
    })
})

describe('feed.revealComments', () => {
    function dom() {
        document.body.innerHTML = `
            <div class="scrollbar-hover-reveal" id="scroller">
                <div id="trigger"></div>
            </div>`
        return { scroller: document.getElementById('scroller'), trigger: document.getElementById('trigger') }
    }

    test('is a no-op when el has no scrollable ancestor', () => {
        const c = feed()
        expect(() => c.revealComments(document.createElement('div'), 1, true)).not.toThrow()
    })

    test('when already loaded, schedules a smooth scroll-to-bottom after COLLAPSE_MS without touching $wire', () => {
        vi.useFakeTimers()
        const { scroller, trigger } = dom()
        scroller.scrollTo = vi.fn()
        Object.defineProperty(scroller, 'scrollHeight', { value: 999 })

        const c = feed()
        c.revealComments(trigger, 1, true)
        expect(scroller.scrollTo).not.toHaveBeenCalled()

        vi.advanceTimersByTime(300)
        expect(scroller.scrollTo).toHaveBeenCalledWith({ top: 999, behavior: 'smooth' })
        vi.useRealTimers()
    })

    test('when not yet loaded, opens comments via $wire first, then scrolls after nextTick', async () => {
        const { scroller, trigger } = dom()
        scroller.scrollTo = vi.fn()
        Object.defineProperty(scroller, 'scrollHeight', { value: 500 })

        const c = feed()
        c.$wire = { openComments: vi.fn(() => Promise.resolve()) }
        c.$nextTick = (fn) => fn()

        vi.useFakeTimers({ shouldAdvanceTime: true })
        c.revealComments(trigger, 7, false)
        await vi.waitFor(() => expect(c.$wire.openComments).toHaveBeenCalledWith(7))

        vi.advanceTimersByTime(300)
        expect(scroller.scrollTo).toHaveBeenCalled()
        vi.useRealTimers()
    })

    test('the pending scroll is skipped if the component was destroyed in the meantime', () => {
        vi.useFakeTimers()
        const { scroller, trigger } = dom()
        scroller.scrollTo = vi.fn()

        const c = feed()
        c._isDestroyed = true
        c.revealComments(trigger, 1, true)
        vi.advanceTimersByTime(300)

        expect(scroller.scrollTo).not.toHaveBeenCalled()
        vi.useRealTimers()
    })
})

describe('feed.updateActiveItem', () => {
    function rect(overrides) {
        return { top: 0, right: 0, left: 0, bottom: 0, width: 0, height: 0, ...overrides }
    }

    test('no-ops when either ref is missing', () => {
        const c = feed()
        c.$refs = {}
        expect(() => c.updateActiveItem()).not.toThrow()
        expect(c.activeId).toBeNull()
    })

    test('desktop: picks the item whose right edge is closest to the 90% reference point', () => {
        Object.defineProperty(window, 'innerWidth', { configurable: true, value: 1024 })
        const c = feed()
        const timeline = document.createElement('div')
        vi.spyOn(timeline, 'getBoundingClientRect').mockReturnValue(rect({ right: 1000, width: 1000 }))

        const feedContainer = document.createElement('div')
        const a = document.createElement('div'); a.dataset.feedId = 'a'
        vi.spyOn(a, 'getBoundingClientRect').mockReturnValue(rect({ right: 500 }))
        const b = document.createElement('div'); b.dataset.feedId = 'b'
        vi.spyOn(b, 'getBoundingClientRect').mockReturnValue(rect({ right: 895 }))
        feedContainer.append(a, b)

        c.$refs = { timeline, feedContainer }
        c.updateActiveItem()
        expect(c.activeId).toBe('b')
    })

    test('mobile: picks the item whose vertical center is closest to the viewport center', () => {
        Object.defineProperty(window, 'innerWidth', { configurable: true, value: 400 })
        const c = feed()
        const timeline = document.createElement('div')
        vi.spyOn(timeline, 'getBoundingClientRect').mockReturnValue(rect({ top: 0, height: 800 }))

        const feedContainer = document.createElement('div')
        const a = document.createElement('div'); a.dataset.feedId = 'a'
        vi.spyOn(a, 'getBoundingClientRect').mockReturnValue(rect({ top: 0, height: 100 }))
        const b = document.createElement('div'); b.dataset.feedId = 'b'
        vi.spyOn(b, 'getBoundingClientRect').mockReturnValue(rect({ top: 350, height: 100 }))
        feedContainer.append(a, b)

        c.$refs = { timeline, feedContainer }
        c.updateActiveItem()
        expect(c.activeId).toBe('b')
    })
})

describe('feed.setupScrollListener — throttled handler', () => {
    test('debounces bursty scroll events into a single updateActiveItem call after SCROLL_THROTTLE_MS', () => {
        vi.useFakeTimers()
        const c = feed()
        const container = document.createElement('div')
        c.$refs = { timeline: container }
        c.updateActiveItem = vi.fn()

        c.setupScrollListener()
        container.dispatchEvent(new Event('scroll'))
        container.dispatchEvent(new Event('scroll'))
        container.dispatchEvent(new Event('scroll'))

        vi.advanceTimersByTime(50)
        expect(c.updateActiveItem).toHaveBeenCalledTimes(1)
        vi.useRealTimers()
    })

    test('is a no-op when $refs.timeline is missing', () => {
        const c = feed()
        c.$refs = {}
        expect(() => c.setupScrollListener()).not.toThrow()
    })
})

describe('feed.init', () => {
    function freshFeed({ view = null, width = 1024 } = {}) {
        const c = feed()
        c.$wire = { get: vi.fn((key) => key === 'view' ? view : null), call: vi.fn() }
        c.$nextTick = vi.fn((fn) => fn())
        c.$watch = vi.fn()
        c.$refs = {}
        c.$root = document.createElement('div')
        Object.defineProperty(window, 'innerWidth', { configurable: true, value: width })
        return c
    }

    test('defaults view to "filmstrip" when the server has none set', () => {
        const c = freshFeed({ view: null })
        c.init()
        expect(c.view).toBe('filmstrip')
    })

    test('below the desktop breakpoint, forces filmstrip view and informs the server', () => {
        const c = freshFeed({ view: 'grid', width: 500 })
        c.init()
        expect(c.view).toBe('filmstrip')
        expect(c.$wire.call).toHaveBeenCalledWith('toggleView', 'filmstrip')
    })

    test('at/above the desktop breakpoint, the server view is respected', () => {
        const c = freshFeed({ view: 'grid', width: 1024 })
        c.init()
        expect(c.view).toBe('grid')
        expect(c.$wire.call).not.toHaveBeenCalled()
    })
})

describe('feed.destroy', () => {
    test('tears down listeners/timers/morph hook without throwing', () => {
        const c = feed()
        c.$wire = { get: vi.fn(() => 'filmstrip') }
        c.$nextTick = vi.fn((fn) => fn())
        c.$watch = vi.fn()
        c.$refs = {}
        c.$root = document.createElement('div')

        c.init()
        expect(() => c.destroy()).not.toThrow()
        expect(c._isDestroyed).toBe(true)
        expect(c._enforce).toBeNull()
    })
})
