import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import '../filament-menu.js'

function makeAlpine() {
    const stores = {}
    return {
        store: vi.fn((name, def) => {
            if (def !== undefined) { stores[name] = def; return undefined }
            return stores[name]
        }),
    }
}

function freshStore() {
    window.Alpine = makeAlpine()
    window.filamentMenu.registerAlpineStore()
    return window.Alpine.store('filamentMenu')
}

function mockNotification() {
    const calls = []
    window.FilamentNotification = vi.fn(function () {
        const inst = {
            title: vi.fn(function (t) { calls.push({ method: 'title', arg: t }); return inst }),
            body: vi.fn(function (b) { calls.push({ method: 'body', arg: b }); return inst }),
            success: vi.fn(() => calls.push({ method: 'success' })),
            danger: vi.fn(() => calls.push({ method: 'danger' })),
            warning: vi.fn(() => calls.push({ method: 'warning' })),
            duration: vi.fn(function (d) { calls.push({ method: 'duration', arg: d }); return inst }),
            send: vi.fn(() => calls.push({ method: 'send' })),
        }
        return inst
    })
    return calls
}

function withMockedLocation(href = 'http://localhost/') {
    const original = window.location
    const reload = vi.fn()
    Object.defineProperty(window, 'location', { configurable: true, value: { href, reload } })
    return { reload, restore: () => Object.defineProperty(window, 'location', { configurable: true, value: original }) }
}

function dispatchKey(target, { key, ctrlKey = false, metaKey = false }) {
    const ev = new KeyboardEvent('keydown', { key, ctrlKey, metaKey, cancelable: true, bubbles: true })
    target.dispatchEvent(ev)
    return ev
}

beforeEach(() => {
    localStorage.clear()
    document.body.innerHTML = ''
    delete window.Alpine
    delete window.Livewire
    delete window.FilamentNotification
    delete global.fetch
    delete global.caches
    delete global.documentPictureInPicture
    delete navigator.storage
    delete navigator.wakeLock
    delete navigator.clipboard
    delete navigator.canShare
    delete navigator.share
    window.filamentMenu.pipWindow = null
    document.title = ''
    Object.defineProperty(document, 'fullscreenElement', { configurable: true, value: null })
})

afterEach(() => {
    vi.useRealTimers()
})

describe('module side effect', () => {
    test('importing the module registers a single FilamentMenuManager instance on window.filamentMenu', () => {
        expect(window.filamentMenu).toBeTruthy()
        expect(typeof window.filamentMenu.registerAlpineStore).toBe('function')
        expect(typeof window.filamentMenu.navigate).toBe('function')
    })
})

describe('filamentMenu store: init()', () => {
    test('tracks document.fullscreenElement via a fullscreenchange listener', () => {
        const s = freshStore()
        s.init()
        expect(s.fullscreen).toBe(false)

        Object.defineProperty(document, 'fullscreenElement', { configurable: true, value: document.body })
        document.dispatchEvent(new Event('fullscreenchange'))
        expect(s.fullscreen).toBe(true)

        Object.defineProperty(document, 'fullscreenElement', { configurable: true, value: null })
        document.dispatchEvent(new Event('fullscreenchange'))
        expect(s.fullscreen).toBe(false)
    })
})

describe('filamentMenu store: resetServerCache', () => {
    test('success path notifies success, resets `resetting`, and schedules a reload after 1200ms', async () => {
        vi.useFakeTimers()
        global.fetch = vi.fn(() => Promise.resolve({ ok: true }))
        const calls = mockNotification()
        const { reload, restore } = withMockedLocation()
        const s = freshStore()

        const p = s.resetServerCache()
        expect(s.resetting).toBe(true)
        await p

        expect(s.resetting).toBe(false)
        expect(calls.some((c) => c.method === 'success')).toBe(true)
        expect(reload).not.toHaveBeenCalled()

        vi.advanceTimersByTime(1200)
        expect(reload).toHaveBeenCalled()
        restore()
    })

    test('a non-ok response notifies danger instead of success, and does not schedule a reload', async () => {
        vi.useFakeTimers()
        global.fetch = vi.fn(() => Promise.resolve({ ok: false }))
        const calls = mockNotification()
        const { reload, restore } = withMockedLocation()
        const s = freshStore()

        await s.resetServerCache()

        expect(s.resetting).toBe(false)
        expect(calls.some((c) => c.method === 'danger')).toBe(true)
        vi.advanceTimersByTime(5000)
        expect(reload).not.toHaveBeenCalled()
        restore()
    })

    test('a rejected fetch is caught and also notifies danger', async () => {
        global.fetch = vi.fn(() => Promise.reject(new Error('network')))
        const calls = mockNotification()
        const s = freshStore()

        await s.resetServerCache()
        expect(calls.some((c) => c.method === 'danger')).toBe(true)
        expect(s.resetting).toBe(false)
    })
})

describe('filamentMenu store: toggleCalendar', () => {
    test('success path reloads the page', async () => {
        global.fetch = vi.fn(() => Promise.resolve({ ok: true }))
        const { reload, restore } = withMockedLocation()
        const s = freshStore()

        await s.toggleCalendar()
        expect(reload).toHaveBeenCalled()
        restore()
    })

    test('failure path notifies danger and does not reload', async () => {
        global.fetch = vi.fn(() => Promise.resolve({ ok: false }))
        const calls = mockNotification()
        const { reload, restore } = withMockedLocation()
        const s = freshStore()

        await s.toggleCalendar()
        expect(calls.some((c) => c.method === 'danger')).toBe(true)
        expect(reload).not.toHaveBeenCalled()
        restore()
    })
})

describe('filamentMenu store: toggleFullscreen', () => {
    test('requests fullscreen when not currently fullscreen', async () => {
        document.documentElement.requestFullscreen = vi.fn(() => Promise.resolve())
        document.exitFullscreen = vi.fn(() => Promise.resolve())
        const s = freshStore()

        await s.toggleFullscreen()
        expect(document.documentElement.requestFullscreen).toHaveBeenCalled()
        expect(document.exitFullscreen).not.toHaveBeenCalled()
    })

    test('exits fullscreen when document.fullscreenElement is set', async () => {
        Object.defineProperty(document, 'fullscreenElement', { configurable: true, value: document.body })
        document.documentElement.requestFullscreen = vi.fn(() => Promise.resolve())
        document.exitFullscreen = vi.fn(() => Promise.resolve())
        const s = freshStore()

        await s.toggleFullscreen()
        expect(document.exitFullscreen).toHaveBeenCalled()
        expect(document.documentElement.requestFullscreen).not.toHaveBeenCalled()
    })

    test('a rejected fullscreen request/exit is swallowed, not propagated', async () => {
        document.documentElement.requestFullscreen = vi.fn(() => Promise.reject(new Error('denied')))
        const s = freshStore()
        await expect(s.toggleFullscreen()).resolves.toBeUndefined()
    })
})

describe('filamentMenu store: toggleWakeLock', () => {
    test('acquires a wake lock and stores it, wiring a release listener', async () => {
        const fakeLock = { release: vi.fn(() => Promise.resolve()), addEventListener: vi.fn() }
        Object.defineProperty(navigator, 'wakeLock', { configurable: true, value: { request: vi.fn(() => Promise.resolve(fakeLock)) } })
        const s = freshStore()

        await s.toggleWakeLock()
        expect(s.wakeLock).toBe(fakeLock)
        expect(fakeLock.addEventListener).toHaveBeenCalledWith('release', expect.any(Function))
    })

    test('releases an already-held wake lock, clearing it even if release() rejects', async () => {
        const release = vi.fn(() => Promise.reject(new Error('x')))
        const s = freshStore()
        s.wakeLock = { release, addEventListener: vi.fn() }

        await s.toggleWakeLock()
        expect(release).toHaveBeenCalled()
        expect(s.wakeLock).toBeNull()
    })

    test('notifies a warning when the Wake Lock API is unsupported', async () => {
        const calls = mockNotification()
        const s = freshStore()

        await s.toggleWakeLock()
        expect(calls.some((c) => c.method === 'warning')).toBe(true)
        expect(s.wakeLock).toBeNull()
    })
})

describe('registerKeyboardShortcuts', () => {
    test('is ignored when the event target is a text input', () => {
        document.body.innerHTML = '<input id="i">'
        const spy = vi.spyOn(window.history, 'back').mockImplementation(() => {})
        dispatchKey(document.getElementById('i'), { key: 'ArrowLeft' })
        expect(spy).not.toHaveBeenCalled()
        spy.mockRestore()
    })

    test('is ignored when the event target is contentEditable', () => {
        document.body.innerHTML = '<div id="e"></div>'
        const el = document.getElementById('e')
        Object.defineProperty(el, 'isContentEditable', { configurable: true, value: true })
        const spy = vi.spyOn(window.history, 'back').mockImplementation(() => {})
        dispatchKey(el, { key: 'ArrowLeft' })
        expect(spy).not.toHaveBeenCalled()
        spy.mockRestore()
    })

    test('Ctrl+S intercepts and clicks the first submit button, even while focused inside an input', () => {
        document.body.innerHTML = '<input id="i"><button type="submit" id="btn"></button>'
        const clickSpy = vi.fn()
        document.getElementById('btn').addEventListener('click', clickSpy)

        const ev = dispatchKey(document.getElementById('i'), { key: 's', ctrlKey: true })
        expect(clickSpy).toHaveBeenCalled()
        expect(ev.defaultPrevented).toBe(true)
    })

    test('Ctrl+S with no submit button present does not throw and does not prevent default', () => {
        document.body.innerHTML = '<input id="i">'
        const ev = dispatchKey(document.getElementById('i'), { key: 's', ctrlKey: true })
        expect(ev.defaultPrevented).toBe(false)
    })

    test('"?" opens the shortcuts help notification with the expected duration', () => {
        const calls = mockNotification()
        const ev = dispatchKey(document.body, { key: '?' })
        expect(ev.defaultPrevented).toBe(true)
        expect(calls.some((c) => c.method === 'warning')).toBe(true)
        expect(calls.some((c) => c.method === 'duration' && c.arg === 8000)).toBe(true)
    })

    test('"/" focuses the first input[type=search] and prevents default', () => {
        document.body.innerHTML = '<input type="search" id="s">'
        const ev = dispatchKey(document.body, { key: '/' })
        expect(document.activeElement.id).toBe('s')
        expect(ev.defaultPrevented).toBe(true)
    })

    test('"/" with no search input present leaves default un-prevented', () => {
        const ev = dispatchKey(document.body, { key: '/' })
        expect(ev.defaultPrevented).toBe(false)
    })

    test('F11 toggles fullscreen through window.Alpine.store(\'filamentMenu\')', () => {
        const s = freshStore()
        const spy = vi.spyOn(s, 'toggleFullscreen').mockResolvedValue()
        const ev = dispatchKey(document.body, { key: 'F11' })
        expect(spy).toHaveBeenCalled()
        expect(ev.defaultPrevented).toBe(true)
    })

    test('F11 does not throw when no Alpine store is registered yet', () => {
        expect(() => dispatchKey(document.body, { key: 'F11' })).not.toThrow()
    })

    test('ArrowRight navigates forward in history', () => {
        const spy = vi.spyOn(window.history, 'forward').mockImplementation(() => {})
        const ev = dispatchKey(document.body, { key: 'ArrowRight' })
        expect(spy).toHaveBeenCalled()
        expect(ev.defaultPrevented).toBe(true)
        spy.mockRestore()
    })

    test('ArrowLeft navigates back in history', () => {
        const spy = vi.spyOn(window.history, 'back').mockImplementation(() => {})
        const ev = dispatchKey(document.body, { key: 'ArrowLeft' })
        expect(spy).toHaveBeenCalled()
        expect(ev.defaultPrevented).toBe(true)
        spy.mockRestore()
    })

    test('ArrowDown increments the page query param and navigates forward a page', () => {
        const original = window.location
        Object.defineProperty(window, 'location', { configurable: true, value: { href: 'http://localhost/foo?page=2' } })
        window.Livewire = { navigate: vi.fn() }

        const ev = dispatchKey(document.body, { key: 'ArrowDown' })
        expect(ev.defaultPrevented).toBe(true)
        expect(window.Livewire.navigate).toHaveBeenCalledWith('http://localhost/foo?page=3')

        Object.defineProperty(window, 'location', { configurable: true, value: original })
    })

    test('ArrowDown defaults to page 1 when no page param is present, navigating to page 2', () => {
        const original = window.location
        Object.defineProperty(window, 'location', { configurable: true, value: { href: 'http://localhost/foo' } })
        window.Livewire = { navigate: vi.fn() }

        dispatchKey(document.body, { key: 'ArrowDown' })
        expect(window.Livewire.navigate).toHaveBeenCalledWith('http://localhost/foo?page=2')

        Object.defineProperty(window, 'location', { configurable: true, value: original })
    })

    test('ArrowUp decrements the page query param when page > 1', () => {
        const original = window.location
        Object.defineProperty(window, 'location', { configurable: true, value: { href: 'http://localhost/foo?page=3' } })
        window.Livewire = { navigate: vi.fn() }

        const ev = dispatchKey(document.body, { key: 'ArrowUp' })
        expect(ev.defaultPrevented).toBe(true)
        expect(window.Livewire.navigate).toHaveBeenCalledWith('http://localhost/foo?page=2')

        Object.defineProperty(window, 'location', { configurable: true, value: original })
    })

    test('ArrowUp on page 1 is a no-op: default not prevented, no navigation', () => {
        const original = window.location
        Object.defineProperty(window, 'location', { configurable: true, value: { href: 'http://localhost/foo?page=1' } })
        window.Livewire = { navigate: vi.fn() }

        const ev = dispatchKey(document.body, { key: 'ArrowUp' })
        expect(ev.defaultPrevented).toBe(false)
        expect(window.Livewire.navigate).not.toHaveBeenCalled()

        Object.defineProperty(window, 'location', { configurable: true, value: original })
    })
})

describe('navigate', () => {
    test('uses Livewire.navigate when available', () => {
        window.Livewire = { navigate: vi.fn() }
        window.filamentMenu.navigate('/foo')
        expect(window.Livewire.navigate).toHaveBeenCalledWith('/foo')
    })

    test('falls back to location.href when Livewire.navigate is unavailable', () => {
        const original = window.location
        Object.defineProperty(window, 'location', { configurable: true, value: { href: '' } })

        window.filamentMenu.navigate('/bar')
        expect(window.location.href).toBe('/bar')

        Object.defineProperty(window, 'location', { configurable: true, value: original })
    })
})

describe('clearStorage', () => {
    test('is a no-op when navigator.storage.estimate is unavailable', () => {
        expect(() => window.filamentMenu.clearStorage()).not.toThrow()
    })

    test('declining the confirm dialog leaves storage untouched', async () => {
        Object.defineProperty(navigator, 'storage', { configurable: true, value: { estimate: () => Promise.resolve({ usage: 1048576 }) } })
        const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false)
        localStorage.setItem('keep', '1')

        window.filamentMenu.clearStorage()
        await new Promise((r) => setTimeout(r, 0))

        expect(localStorage.getItem('keep')).toBe('1')
        confirmSpy.mockRestore()
    })

    test('accepting the confirm dialog (with the computed MB size) clears storage/caches and reloads', async () => {
        Object.defineProperty(navigator, 'storage', { configurable: true, value: { estimate: () => Promise.resolve({ usage: 2097152 }) } })
        const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true)
        const reload = vi.fn()
        const original = window.location
        Object.defineProperty(window, 'location', { configurable: true, value: { reload } })
        const deleteFn = vi.fn()
        global.caches = { keys: vi.fn(() => Promise.resolve(['a', 'b'])), delete: deleteFn }
        localStorage.setItem('x', '1')

        window.filamentMenu.clearStorage()
        await new Promise((r) => setTimeout(r, 0))
        await new Promise((r) => setTimeout(r, 0))

        expect(confirmSpy).toHaveBeenCalledWith('2.0 مگابایت کش پاک شود؟')
        expect(localStorage.getItem('x')).toBeNull()
        expect(reload).toHaveBeenCalled()
        expect(deleteFn).toHaveBeenCalledWith('a')
        expect(deleteFn).toHaveBeenCalledWith('b')

        confirmSpy.mockRestore()
        Object.defineProperty(window, 'location', { configurable: true, value: original })
    })
})

describe('requestPiP', () => {
    test('notifies "not supported" when documentPictureInPicture is unavailable', () => {
        const calls = mockNotification()
        window.filamentMenu.requestPiP()
        expect(calls.some((c) => c.method === 'warning')).toBe(true)
    })

    test('focuses an already-open PiP window instead of requesting a new one', () => {
        global.documentPictureInPicture = { requestWindow: vi.fn() }
        const focusSpy = vi.fn()
        window.filamentMenu.pipWindow = { closed: false, focus: focusSpy }

        window.filamentMenu.requestPiP()
        expect(focusSpy).toHaveBeenCalled()
        expect(global.documentPictureInPicture.requestWindow).not.toHaveBeenCalled()
    })
})

describe('share', () => {
    test('uses navigator.share when canShare approves, notifying success on resolve', async () => {
        document.title = 'My Title'
        const calls = mockNotification()
        const shareFn = vi.fn(() => Promise.resolve())
        Object.defineProperty(navigator, 'canShare', { configurable: true, value: () => true })
        Object.defineProperty(navigator, 'share', { configurable: true, value: shareFn })

        window.filamentMenu.share()
        await new Promise((r) => setTimeout(r, 0))

        expect(shareFn).toHaveBeenCalled()
        expect(calls.some((c) => c.method === 'success')).toBe(true)
    })

    test('falls back to clipboard.writeText when canShare is unavailable', async () => {
        const writeText = vi.fn(() => Promise.resolve())
        Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText } })

        window.filamentMenu.share()
        await new Promise((r) => setTimeout(r, 0))

        expect(writeText).toHaveBeenCalled()
    })

    test('a rejected share/clipboard promise is swallowed, not propagated', async () => {
        Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: () => Promise.reject(new Error('x')) } })
        expect(() => window.filamentMenu.share()).not.toThrow()
        await new Promise((r) => setTimeout(r, 0))
    })

    test('is a silent no-op when neither share nor clipboard is available', () => {
        expect(() => window.filamentMenu.share()).not.toThrow()
    })
})
