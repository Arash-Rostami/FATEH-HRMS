import { describe, expect, test, vi, beforeEach } from 'vitest'
import heroDeck, { launchpadPage, addDeckModule } from '../heroDeck.js'

const STORAGE_KEY = 'hero_gadgets'

beforeEach(() => {
    localStorage.clear()
})

describe('launchpadPage.limit', () => {
    const OVER = 'تعداد فایل‌ها بیش از حد مجاز است.'

    const makeEvent = (length, max) => {
        const input = { value: 'kept', dataset: { max: String(max), over: OVER }, files: { length } }
        return { target: input, stopPropagation: vi.fn() }
    }

    test('an over-limit selection is cleared and stopped before Livewire sees the upload, with the over toast', () => {
        const c = launchpadPage()
        c.$dispatch = vi.fn()
        const event = makeEvent(4, 3)
        c.limit(event)
        expect(event.target.value).toBe('')
        expect(event.stopPropagation).toHaveBeenCalledTimes(1)
        expect(c.$dispatch).toHaveBeenCalledWith('toast', { message: OVER, type: 'warning' })
    })

    test('an at-limit selection passes through untouched, with no stop and no toast', () => {
        const c = launchpadPage()
        c.$dispatch = vi.fn()
        const event = makeEvent(3, 3)
        c.limit(event)
        expect(event.target.value).toBe('kept')
        expect(event.stopPropagation).not.toHaveBeenCalled()
        expect(c.$dispatch).not.toHaveBeenCalled()
    })
})

describe('addDeckModule', () => {
    test('adds a new module, appends its pane, slides to it, and persists the snapshot', () => {
        vi.spyOn(Date, 'now').mockReturnValue(9)
        expect(addDeckModule({ title: 'برنامه', icon: 'apps', src: '/launchpad?a=1&embed=1' })).toBe('added')
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY))
        expect(saved.panes).toEqual(['p_9'])
        expect(saved.modules.p_9).toEqual({ title: 'برنامه', icon: 'apps', src: '/launchpad?a=1&embed=1' })
        expect(saved.slide).toBe('p_9')
        vi.restoreAllMocks()
    })

    test('the same src already pinned returns "exists" and writes nothing', () => {
        const src = '/launchpad?a=2&embed=1'
        addDeckModule({ title: 'A', icon: 'apps', src })
        const before = localStorage.getItem(STORAGE_KEY)
        expect(addDeckModule({ title: 'B', icon: 'apps', src })).toBe('exists')
        expect(localStorage.getItem(STORAGE_KEY)).toBe(before)
    })

    test('a full deck (MAX_PANES 6) returns "full" and writes nothing', () => {
        const panes = ['p_1', 'p_2', 'p_3', 'p_4', 'p_5', 'p_6']
        const modules = {}
        panes.forEach((id, i) => { modules[id] = { title: `M${i}`, icon: 'apps', src: `/src/${i}` } })
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ panes, modules, slide: 'p_1' }))
        const before = localStorage.getItem(STORAGE_KEY)
        expect(addDeckModule({ title: 'New', icon: 'apps', src: '/new' })).toBe('full')
        expect(localStorage.getItem(STORAGE_KEY)).toBe(before)
    })
})

describe('heroDeck.canAddPane / openAddPicker', () => {
    test('canAddPane is true below MAX_PANES (6) and false at/after it', () => {
        const c = heroDeck()
        expect(c.canAddPane()).toBe(true)
        c.panes = [1, 2, 3, 4, 5, 6]
        expect(c.canAddPane()).toBe(false)
    })

    test('openAddPicker sets pickerFor to the "new" sentinel only when a pane can still be added', () => {
        const c = heroDeck()
        c.openAddPicker()
        expect(c.pickerFor).toBe('__new__')

        c.pickerFor = null
        c.panes = [1, 2, 3, 4, 5, 6]
        c.openAddPicker()
        expect(c.pickerFor).toBeNull()
    })
})

describe('heroDeck.pick', () => {
    test('is a no-op when no picker is active', () => {
        const c = heroDeck()
        c.pick({ title: 't', icon: 'i', src: 's' })
        expect(c.panes).toEqual([])
        expect(c.modules).toEqual({})
    })

    test('adding a new pane generates a timestamp id, appends it, loads the module, and switches slide to it', () => {
        vi.spyOn(Date, 'now').mockReturnValue(12345)
        const c = heroDeck()
        c.pickerFor = '__new__'

        c.pick({ title: 'Weather', icon: 'w', src: '/w' })

        expect(c.panes).toEqual(['p_12345'])
        expect(c.modules['p_12345']).toEqual({ title: 'Weather', icon: 'w', src: '/w' })
        expect(c.loaded['p_12345']).toBe(true)
        expect(c.frameReady['p_12345']).toBe(false)
        expect(c.slide).toBe('p_12345')
        expect(c.pickerFor).toBeNull()
        vi.restoreAllMocks()
    })

    test('re-picking an existing pane updates its module in place without pushing a new pane or changing slide', () => {
        const c = heroDeck()
        c.panes = ['a']
        c.slide = 'z'
        c.pickerFor = 'a'

        c.pick({ title: 'News', icon: 'n', src: '/n' })

        expect(c.panes).toEqual(['a'])
        expect(c.modules.a).toEqual({ title: 'News', icon: 'n', src: '/n' })
        expect(c.slide).toBe('z')
        expect(c.pickerFor).toBeNull()
    })

    test('picking a module that is already pinned switches to its pane instead of adding a second one', () => {
        const c = heroDeck()
        c.panes = ['a']
        c.modules = { a: { title: 'News', icon: 'n', src: '/n' } }
        c.pickerFor = '__new__'

        c.pick({ title: 'News', icon: 'n', src: '/n' })

        expect(c.panes).toEqual(['a'])
        expect(c.slide).toBe('a')
        expect(c.pickerFor).toBeNull()
    })

    test('re-picking an existing pane with a module already pinned elsewhere switches to that pane', () => {
        const c = heroDeck()
        c.panes = ['a', 'b']
        c.modules = { a: { title: 'A', icon: 'a', src: '/a' }, b: { title: 'B', icon: 'b', src: '/b' } }
        c.pickerFor = 'a'

        c.pick({ title: 'B', icon: 'b', src: '/b' })

        expect(c.modules.a.src).toBe('/a')
        expect(c.slide).toBe('b')
        expect(c.pickerFor).toBeNull()
    })

    test('persists panes/modules to localStorage after picking', () => {
        const c = heroDeck()
        c.pickerFor = '__new__'
        vi.spyOn(Date, 'now').mockReturnValue(1)

        c.pick({ title: 'A', icon: 'a', src: '/a' })

        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY))
        expect(saved.panes).toEqual(['p_1'])
        expect(saved.modules.p_1).toEqual({ title: 'A', icon: 'a', src: '/a' })
        vi.restoreAllMocks()
    })
})

describe('heroDeck.remove', () => {
    test('removes the pane from the list and deletes its module/loaded/frameReady entries', () => {
        const c = heroDeck()
        c.panes = ['a', 'b']
        c.modules = { a: {}, b: {} }
        c.loaded = { a: true, b: true }
        c.frameReady = { a: true, b: true }

        c.remove('a')

        expect(c.panes).toEqual(['b'])
        expect(c.modules).toEqual({ b: {} })
        expect(c.loaded).toEqual({ b: true })
        expect(c.frameReady).toEqual({ b: true })
    })

    test('clears maximizedPane and calls applyMaximize(false) when removing the currently-maximized pane', () => {
        const c = heroDeck()
        c.panes = ['a']
        c.maximizedPane = 'a'
        const spy = vi.spyOn(c, 'applyMaximize')

        c.remove('a')

        expect(c.maximizedPane).toBeNull()
        expect(spy).toHaveBeenCalledWith(false)
    })

    test('persists null via _persistMaximize when removing the currently-maximized pane', () => {
        const c = heroDeck()
        c.panes = ['a']
        c.maximizedPane = 'a'
        const spy = vi.spyOn(c, '_persistMaximize')

        c.remove('a')

        expect(spy).toHaveBeenCalledWith(null)
    })

    test('does not touch maximizedPane/applyMaximize when removing a different pane', () => {
        const c = heroDeck()
        c.panes = ['a', 'b']
        c.maximizedPane = 'b'
        const spy = vi.spyOn(c, 'applyMaximize')

        c.remove('a')

        expect(c.maximizedPane).toBe('b')
        expect(spy).not.toHaveBeenCalled()
    })

    test('resets slide to 0 when removing the active pane, otherwise leaves slide untouched', () => {
        const c = heroDeck()
        c.panes = ['a', 'b']
        c.slide = 'a'
        c.remove('a')
        expect(c.slide).toBe(0)

        const c2 = heroDeck()
        c2.panes = ['a', 'b']
        c2.slide = 'b'
        c2.remove('a')
        expect(c2.slide).toBe('b')
    })

    test('removing an id not present in panes still deletes module entries and persists, without throwing', () => {
        const c = heroDeck()
        c.panes = ['a']
        c.modules = { ghost: {} }
        expect(() => c.remove('ghost')).not.toThrow()
        expect(c.panes).toEqual(['a'])
        expect(c.modules).toEqual({})
    })
})

describe('heroDeck.onFrameLoad', () => {
    test('marks the given pane as frame-ready', () => {
        const c = heroDeck()
        c.onFrameLoad('x')
        expect(c.frameReady.x).toBe(true)
    })
})

describe('heroDeck.toggleMaximize — graceful close', () => {
    beforeEach(() => vi.useFakeTimers())

    test('opening sets maximizedPane and calls applyMaximize(true)', () => {
        const c = heroDeck()
        const spy = vi.spyOn(c, 'applyMaximize')

        c.toggleMaximize('a')

        expect(c.maximizedPane).toBe('a')
        expect(spy).toHaveBeenCalledWith(true)
        vi.useRealTimers()
    })

    test('opening persists the pane id via _persistMaximize', () => {
        const c = heroDeck()
        const spy = vi.spyOn(c, '_persistMaximize')

        c.toggleMaximize('a')

        expect(spy).toHaveBeenCalledWith('a')
        vi.useRealTimers()
    })

    test('closing the active pane sets maxLeaving immediately, calls applyMaximize(false), and only clears state after MAX_WIDGET_CLOSE_MS', () => {
        const c = heroDeck()
        c.maximizedPane = 'a'
        const spy = vi.spyOn(c, 'applyMaximize')

        c.toggleMaximize('a')

        expect(c.maxLeaving).toBe(true)
        expect(spy).toHaveBeenCalledWith(false)
        expect(c.maximizedPane).toBe('a')

        vi.advanceTimersByTime(349)
        expect(c.maximizedPane).toBe('a')

        vi.advanceTimersByTime(1)
        expect(c.maximizedPane).toBeNull()
        expect(c.maxLeaving).toBe(false)
        vi.useRealTimers()
    })

    test('closing persists null via _persistMaximize at the start of the close sequence', () => {
        const c = heroDeck()
        c.maximizedPane = 'a'
        const spy = vi.spyOn(c, '_persistMaximize')

        c.toggleMaximize('a')

        expect(spy).toHaveBeenCalledWith(null)
        vi.useRealTimers()
    })
})

describe('heroDeck.restoreMaximize', () => {
    test('a value matching an existing pane re-applies maximize', () => {
        const c = heroDeck()
        c.panes = ['a', 'b']
        const spy = vi.spyOn(c, 'applyMaximize')

        c.restoreMaximize('b')

        expect(c.maximizedPane).toBe('b')
        expect(spy).toHaveBeenCalledWith(true)
    })

    test('also switches slide to the restored pane, so its x-show="slide === pane" wrapper (and the maximize chrome/close button inside it) actually renders instead of staying blank — this in turn is what the real init()-registered "slide" $watch (tested below) uses to mark the pane loaded', () => {
        const c = heroDeck()
        c.panes = ['a', 'b']
        c.slide = 0

        c.restoreMaximize('b')

        expect(c.slide).toBe('b')
    })

    test('a stale value not present in panes cleans up and does nothing else', () => {
        const c = heroDeck()
        c.panes = ['a']
        const persistSpy = vi.spyOn(c, '_persistMaximize')
        const applySpy = vi.spyOn(c, 'applyMaximize')

        c.restoreMaximize('gone')

        expect(persistSpy).toHaveBeenCalledWith(null)
        expect(applySpy).not.toHaveBeenCalled()
        expect(c.maximizedPane).toBeNull()
    })
})

describe('heroDeck.persist', () => {
    test('writes the current panes/modules to localStorage as JSON', () => {
        const c = heroDeck()
        c.panes = ['a']
        c.modules = { a: { title: 't' } }
        c.persist()
        expect(JSON.parse(localStorage.getItem(STORAGE_KEY))).toEqual({ panes: ['a'], modules: { a: { title: 't' } }, slide: 0 })
    })

    test('persists the open slide and restores it on init only when its pane and module entry still exist', () => {
        const c = heroDeck()
        c.panes = ['a']
        c.modules = { a: { src: '/a' } }
        c.slide = 'a'
        c.persist()
        expect(JSON.parse(localStorage.getItem(STORAGE_KEY)).slide).toBe('a')

        const d = heroDeck()
        d.$watch = vi.fn()
        d.init()
        expect(d.slide).toBe('a')
        expect(d.loaded.a).toBe(true)

        localStorage.setItem(STORAGE_KEY, JSON.stringify({ panes: ['a'], modules: {}, slide: 'gone' }))
        const e = heroDeck()
        e.$watch = vi.fn()
        e.init()
        expect(e.slide).toBe(0)

        localStorage.setItem(STORAGE_KEY, JSON.stringify({ panes: ['a'], modules: {}, slide: 'a' }))
        const f = heroDeck()
        f.$watch = vi.fn()
        f.init()
        expect(f.slide).toBe(0)
        expect(f.loaded.a).toBeUndefined()
    })

    test('remove drops the pooled iframe of that pane', () => {
        const slot = document.createElement('div')
        const c = heroDeck()
        c.panes = ['a']
        c.modules = { a: { src: '/a' } }
        c.mountFrame('a', slot)
        expect(slot.querySelector('iframe')).not.toBeNull()
        c.remove('a')
        expect(slot.querySelector('iframe')).toBeNull()
    })

    test('a storage write failure is swallowed, not thrown', () => {
        const spy = vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('quota') })
        const c = heroDeck()
        expect(() => c.persist()).not.toThrow()
        spy.mockRestore()
    })
})

describe('heroDeck.init', () => {
    test('with nothing saved, panes/modules stay at their empty defaults', () => {
        const c = heroDeck()
        c.$watch = vi.fn()
        c.init()
        expect(c.panes).toEqual([])
        expect(c.modules).toEqual({})
    })

    test('restores panes/modules from a valid saved snapshot', () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ panes: ['a'], modules: { a: { title: 't' } } }))
        const c = heroDeck()
        c.$watch = vi.fn()
        c.init()
        expect(c.panes).toEqual(['a'])
        expect(c.modules).toEqual({ a: { title: 't' } })
    })

    test('a non-array saved "panes" value falls back to an empty array', () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ panes: 'not-an-array', modules: { a: {} } }))
        const c = heroDeck()
        c.$watch = vi.fn()
        c.init()
        expect(c.panes).toEqual([])
        expect(c.modules).toEqual({ a: {} })
    })

    test('corrupt JSON is caught and the stale key is removed, without throwing', () => {
        localStorage.setItem(STORAGE_KEY, '{not json')
        const c = heroDeck()
        c.$watch = vi.fn()
        expect(() => c.init()).not.toThrow()
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()
    })

    test('a storage event for the deck key replaces panes/modules without touching the slide', () => {
        const c = heroDeck()
        c.$watch = vi.fn()
        c.init()
        c.slide = 'a'
        const next = { panes: ['a', 'b'], modules: { a: { src: '/a' }, b: { src: '/b' } }, slide: 'b' }

        window.dispatchEvent(new StorageEvent('storage', { key: STORAGE_KEY, newValue: JSON.stringify(next) }))

        expect(c.panes).toEqual(['a', 'b'])
        expect(c.modules).toEqual(next.modules)
        expect(c.slide).toBe('a')
    })

    test('a storage event with a malformed value or another key is ignored', () => {
        const c = heroDeck()
        c.$watch = vi.fn()
        c.init()
        c.panes = ['a']
        c.modules = { a: { src: '/a' } }

        window.dispatchEvent(new StorageEvent('storage', { key: STORAGE_KEY, newValue: '{not json' }))
        window.dispatchEvent(new StorageEvent('storage', { key: 'other', newValue: JSON.stringify({ panes: ['x'], modules: {} }) }))

        expect(c.panes).toEqual(['a'])
        expect(c.modules).toEqual({ a: { src: '/a' } })
    })

    test('registers a "slide" watcher that lazily marks a target pane as loaded once its module exists, but ignores slide 0', () => {
        const c = heroDeck()
        let watchCb
        c.$watch = vi.fn((key, cb) => { watchCb = cb })
        c.init()

        c.modules = { a: {} }
        c.loaded = {}

        watchCb(0)
        expect(c.loaded.a).toBeUndefined()

        watchCb('a')
        expect(c.loaded.a).toBe(true)

        watchCb('unknown')
        expect(c.loaded.unknown).toBeUndefined()
    })
})
