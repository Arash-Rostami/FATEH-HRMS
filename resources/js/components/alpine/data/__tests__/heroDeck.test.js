import { describe, expect, test, vi, beforeEach } from 'vitest'
import heroDeck, { launchpadPage, matchesQuery, splitMatches } from '../heroDeck.js'

const STORAGE_KEY = 'hero_gadgets'

const rows = [
    { g: 'module', s: '/a', f: 'تقویم' },
    { g: 'program', s: '/b', f: 'گزارش فروش مالی' },
    { g: 'sso', s: '/c', f: 'سامانه فروش crm' },
]

beforeEach(() => {
    localStorage.clear()
})

describe('launchpadPage table tools', () => {
    test('exposes the shared header declutter toggle', () => {
        expect(typeof launchpadPage().toggleHeaderDeclutter).toBe('function')
    })

    test('exposes the maximize state and init restores it through initMaximize', () => {
        const c = launchpadPage()
        expect(c.max).toBe(false)
        expect(typeof c.toggleMaximize).toBe('function')
        c.initMaximize = vi.fn()
        c.initTableTools = vi.fn()
        c.init()
        expect(c.initMaximize).toHaveBeenCalledTimes(1)
        expect(c.initTableTools).toHaveBeenCalledTimes(1)
    })
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

describe('launchpadPage workspace option', () => {
    test('the row button no longer pins a slide on the client', () => {
        expect(launchpadPage().add).toBeUndefined()
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()
    })

    test('the maximize toggle flips max with the leaving flag, used by the viewer wrapper', () => {
        vi.useFakeTimers()
        const c = launchpadPage()
        c.applyMaximize = vi.fn()
        c.toggleMaximize()
        expect(c.max).toBe(true)
        c.toggleMaximize()
        expect(c.maxLeaving).toBe(true)
        vi.advanceTimersByTime(400)
        expect(c.max).toBe(false)
        expect(c.maxLeaving).toBe(false)
        vi.useRealTimers()
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

    test('toggleMaximize(null) closes the pane that is currently maximized', () => {
        const c = heroDeck()
        c.maximizedPane = 'a'
        const spy = vi.spyOn(c, 'applyMaximize')

        c.toggleMaximize(null)

        expect(spy).toHaveBeenCalledWith(false)
        expect(c.maxLeaving).toBe(true)
        vi.useRealTimers()
    })

    test('closeOverlays closes the picker and reports it, otherwise reports nothing', () => {
        const c = heroDeck()
        expect(c.closeOverlays()).toBe(false)

        c.pickerFor = 'a'
        expect(c.closeOverlays()).toBe(true)
        expect(c.pickerFor).toBeNull()
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
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ panes: ['a'], modules: { a: { title: 't', icon: 'i', src: '/a' } } }))
        const c = heroDeck()
        c.$watch = vi.fn()
        c.init()
        expect(c.panes).toEqual(['a'])
        expect(c.modules).toEqual({ a: { title: 't', icon: 'i', src: '/a' } })
    })

    test('a non-array saved "panes" value falls back to an empty deck and drops the orphan modules', () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ panes: 'not-an-array', modules: { a: { src: '/a' } } }))
        const c = heroDeck()
        c.$watch = vi.fn()
        c.init()
        expect(c.panes).toEqual([])
        expect(c.modules).toEqual({})
    })

    test('a corrupt or partial snapshot self-heals: only unique string panes with a module and an http(s) frame address survive, capped at MAX_PANES (6)', () => {
        const mod = (src) => ({ title: 't', icon: 'i', src })
        const modules = { ok: mod('/ok'), script: mod('javascript:alert(1)'), data: mod('data:text/html,x'), empty: mod(''), nosrc: { title: 't' } }
        for (let i = 1; i <= 7; i++) modules['p' + i] = mod('/p' + i)
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            panes: ['ok', 'ok', 'ghost', 'script', 'data', 'empty', 'nosrc', 7, null, { id: 'x' }, '__proto__', 'p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7'],
            modules,
            slide: 'script',
        }))
        const c = heroDeck()
        c.$watch = vi.fn()
        c.init()

        expect(c.panes).toEqual(['ok', 'p1', 'p2', 'p3', 'p4', 'p5'])
        expect(Object.keys(c.modules)).toEqual(c.panes)
        expect(c.slide).toBe(0)
        expect(c.loaded).toEqual({})
    })

    test('a saved value that is not an object (null, a number, a string, an array) yields the empty home deck without throwing', () => {
        ['null', '5', '"x"', '[1,2]', '{"panes":null,"modules":[],"slide":{}}'].forEach((raw) => {
            localStorage.setItem(STORAGE_KEY, raw)
            const c = heroDeck()
            c.$watch = vi.fn()
            expect(() => c.init()).not.toThrow()
            expect(c.panes).toEqual([])
            expect(c.modules).toEqual({})
            expect(c.slide).toBe(0)
        })
    })

    test('a stored maximize id whose pane did not survive the restore is cleared and never maximizes', () => {
        document.documentElement.dataset.uid = '7'
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ panes: ['a'], modules: {}, slide: 'a' }))
        localStorage.setItem('max:hero:7', 'a')
        const c = heroDeck()
        c.$watch = vi.fn()
        const spy = vi.spyOn(c, 'applyMaximize')
        c.init()

        expect(c.maximizedPane).toBeNull()
        expect(c.slide).toBe(0)
        expect(spy).not.toHaveBeenCalled()
        expect(localStorage.getItem('max:hero:7')).toBeNull()
        delete document.documentElement.dataset.uid
    })

    test('a storage event that drops the maximized pane releases the full-screen state instead of leaving its backdrop over an empty deck', () => {
        const c = heroDeck()
        c.$watch = vi.fn()
        c.init()
        c.panes = ['a']
        c.modules = { a: { title: 't', icon: 'i', src: '/a' } }
        c.slide = 'a'
        c.maximizedPane = 'a'
        const applySpy = vi.spyOn(c, 'applyMaximize')
        const persistSpy = vi.spyOn(c, '_persistMaximize')

        window.dispatchEvent(new StorageEvent('storage', { key: STORAGE_KEY, newValue: JSON.stringify({ panes: [], modules: {}, slide: 0 }) }))

        expect(c.slide).toBe(0)
        expect(c.maximizedPane).toBeNull()
        expect(applySpy).toHaveBeenCalledWith(false)
        expect(persistSpy).toHaveBeenCalledWith(null)
    })

    test('destroy gives the page chrome back when the deck goes away while a pane is maximized, and keeps the stored choice', () => {
        const c = heroDeck()
        c.$watch = vi.fn()
        c.init()
        c.maximizedPane = 'a'
        const applySpy = vi.spyOn(c, 'applyMaximize')
        const persistSpy = vi.spyOn(c, '_persistMaximize')

        c.destroy()

        expect(applySpy).toHaveBeenCalledWith(false)
        expect(persistSpy).not.toHaveBeenCalled()
    })

    test('destroy leaves the layout alone when nothing is maximized', () => {
        const c = heroDeck()
        c.$watch = vi.fn()
        c.init()
        const applySpy = vi.spyOn(c, 'applyMaximize')

        c.destroy()

        expect(applySpy).not.toHaveBeenCalled()
    })

    test('mountFrame for a pane whose module is gone mounts nothing and does not throw', () => {
        const slot = document.createElement('div')
        const c = heroDeck()
        expect(() => c.mountFrame('gone', slot)).not.toThrow()
        expect(slot.querySelector('iframe')).toBeNull()
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
        const next = { panes: ['a', 'b'], modules: { a: { title: 'A', icon: 'a', src: '/a' }, b: { title: 'B', icon: 'b', src: '/b' } }, slide: 'b' }

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
        const watchers = {}
        c.$watch = vi.fn((key, cb) => { watchers[key] = cb })
        c.init()
        const watchCb = watchers.slide

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

describe('splitMatches', () => {
    test('wraps matches as plain text parts and never as markup', () => {
        expect(splitMatches('گزارش فروش', 'فروش')).toEqual([{ t: 'گزارش ', hit: false }, { t: 'فروش', hit: true }])
        expect(splitMatches('<b>x</b>', 'b')).toEqual([
            { t: '<', hit: false }, { t: 'b', hit: true }, { t: '>x</', hit: false }, { t: 'b', hit: true }, { t: '>', hit: false },
        ])
    })

    test('an empty query or regex metacharacters never break the split', () => {
        expect(splitMatches('abc', '  ')).toEqual([{ t: 'abc', hit: false }])
        expect(splitMatches('a.c', '.')).toEqual([{ t: 'a', hit: false }, { t: '.', hit: true }, { t: 'c', hit: false }])
    })
})

describe('matchesQuery', () => {
    test('every word must be present, case-insensitively', () => {
        expect(matchesQuery('گزارش فروش مالی', 'فروش مالی')).toBe(true)
        expect(matchesQuery('سامانه CRM', 'crm')).toBe(true)
        expect(matchesQuery('تقویم', 'فروش')).toBe(false)
    })
})

describe('heroDeck picker counts and visibility', () => {
    const open = (c) => {
        const watchers = {}
        c.$watch = vi.fn((key, cb) => { watchers[key] = cb })
        c.init()
        c.rows = rows
        return watchers
    }

    test('counts follow the search per group and the active tab filters tiles', () => {
        const c = heroDeck()
        c.rows = rows
        expect(c.count('module')).toBe(1)
        expect(c.count('program')).toBe(1)

        c.q = 'فروش'
        expect(c.count('program')).toBe(1)
        expect(c.count('module')).toBe(0)

        c.tab = 'sso'
        expect(c.show(1)).toBe(false)
        expect(c.show(2)).toBe(true)
    })

    test('opening the picker selects the first group that has items and never an all tab', () => {
        const c = heroDeck()
        const watchers = open(c)
        expect(c.tab).toBe('')

        watchers.pickerFor('__new__')
        expect(c.tab).toBe('module')

        const d = heroDeck()
        const dw = open(d)
        d.rows = rows.slice(1)
        dw.pickerFor('__new__')
        expect(d.tab).toBe('program')
    })

    test('typing switches to the first tab with matches only when the active tab has none', () => {
        const c = heroDeck()
        const watchers = open(c)
        watchers.pickerFor('__new__')

        c.q = 'فروش'
        watchers.q()
        expect(c.tab).toBe('program')

        c.q = 'crm'
        watchers.q()
        expect(c.tab).toBe('sso')

        c.tab = 'program'
        c.q = 'فروش'
        watchers.q()
        expect(c.tab).toBe('program')
    })

    test('clearing the search keeps the current tab and no match anywhere keeps it too', () => {
        const c = heroDeck()
        const watchers = open(c)
        watchers.pickerFor('__new__')

        c.q = 'crm'
        watchers.q()
        c.q = ''
        watchers.q()
        expect(c.tab).toBe('sso')

        c.q = 'zzz'
        watchers.q()
        expect(c.tab).toBe('sso')
        expect(c.count(c.tab)).toBe(0)
    })

    test('closing the picker resets the query and the tab', () => {
        const c = heroDeck()
        const watchers = open(c)
        watchers.pickerFor('__new__')
        c.q = 'crm'
        watchers.q()

        watchers.pickerFor(null)
        expect(c.q).toBe('')
        expect(c.tab).toBe('')
    })
})

describe('heroDeck.usedSrc', () => {
    test('a src open in another pane is in use; the pane being replaced does not block itself', () => {
        const d = heroDeck()
        d.panes = ['p1', 'p2']
        d.modules = { p1: { src: '/a' }, p2: { src: '/b' } }
        d.pickerFor = '__new__'
        expect(d.usedSrc('/a')).toBe(true)
        expect(d.usedSrc('/z')).toBe(false)

        d.pickerFor = 'p1'
        expect(d.usedSrc('/a')).toBe(false)
        expect(d.usedSrc('/b')).toBe(true)
    })
})
