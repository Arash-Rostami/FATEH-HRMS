import { afterEach, describe, expect, test, vi } from 'vitest'
import registerAppStore from '../app.js'

const register = () => {
    const stores = {}
    registerAppStore({ store: (n, d) => (d ? (stores[n] = d) : stores[n]) })
    return stores.app
}

const promptEvent = (outcome) => Object.assign(new Event('beforeinstallprompt', { cancelable: true }), {
    prompt: vi.fn().mockResolvedValue(undefined),
    userChoice: Promise.resolve({ outcome })
})

afterEach(() => {
    delete window.matchMedia
    delete navigator.clearAppBadge
    delete navigator.storage
    delete navigator.serviceWorker
})

const fakeWorker = (controller) => {
    const sw = Object.assign(new EventTarget(), { controller })
    Object.defineProperty(navigator, 'serviceWorker', { value: sw, configurable: true })
    return sw
}

const captureToasts = () => {
    const toasts = []
    const listener = (e) => toasts.push(e.detail)
    window.addEventListener('toast', listener)
    return { toasts, stop: () => window.removeEventListener('toast', listener) }
}

describe('app store', () => {
    test('starts with no install offer, not installed and no persisted storage', () => {
        const app = register()

        expect(app.installable).toBe(false)
        expect(app.installed).toBe(false)
        expect(app.persisted).toBe(false)
        expect(app.ios).toBe(false)
    })

    test('beforeinstallprompt is captured and install() resolves accepted, then the offer is spent', async () => {
        const app = register()
        const event = promptEvent('accepted')
        window.dispatchEvent(event)

        expect(event.defaultPrevented).toBe(true)
        expect(app.installable).toBe(true)
        expect(await app.install()).toBe('accepted')
        expect(event.prompt).toHaveBeenCalledTimes(1)
        expect(app.installable).toBe(false)
        expect(await app.install()).toBe('unavailable')
    })

    test('install() reports dismissed when the user declines', async () => {
        const app = register()
        window.dispatchEvent(promptEvent('dismissed'))

        expect(await app.install()).toBe('dismissed')
    })

    test('install() is unavailable without a captured event', async () => {
        expect(await register().install()).toBe('unavailable')
    })

    test('appinstalled flips installed on and installable off', () => {
        const app = register()
        window.dispatchEvent(promptEvent('accepted'))
        window.dispatchEvent(new Event('appinstalled'))

        expect(app.installed).toBe(true)
        expect(app.installable).toBe(false)
    })

    test('installed is true when the display mode is standalone', () => {
        window.matchMedia = vi.fn(() => ({ matches: true }))

        expect(register().installed).toBe(true)
    })

    test('windows is true only when the user agent names Windows', () => {
        const ua = vi.spyOn(navigator, 'userAgent', 'get')

        ua.mockReturnValue('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130')
        expect(register().windows).toBe(true)

        ua.mockReturnValue('Mozilla/5.0 (X11; Linux x86_64) Chrome/130')
        expect(register().windows).toBe(false)

        ua.mockRestore()
    })

    test('persist() is false where the storage API is missing and follows the browser answer where present', async () => {
        const app = register()
        expect(await app.persist()).toBe(false)

        navigator.storage = { persist: vi.fn().mockResolvedValue(true) }
        expect(await app.persist()).toBe(true)
        expect(app.persisted).toBe(true)
    })

    test('the badge is cleared at registration when the page starts visible', () => {
        navigator.clearAppBadge = vi.fn()
        register()

        expect(navigator.clearAppBadge).toHaveBeenCalledTimes(1)
    })

    test('clearBadge also drops the per-conversation badge counts kept by the service worker', () => {
        const remove = vi.fn().mockResolvedValue(true)
        window.caches = { delete: remove }
        register().clearBadge()

        expect(remove).toHaveBeenCalledWith('badge-v1')
        delete window.caches
    })

    test('clearBadge runs when the document becomes visible', () => {
        navigator.clearAppBadge = vi.fn()
        register()
        navigator.clearAppBadge.mockClear()
        document.dispatchEvent(new Event('visibilitychange'))

        expect(navigator.clearAppBadge).toHaveBeenCalled()
    })

    test('no update toast when the page had no service worker controller at registration', () => {
        const sw = fakeWorker(null)
        const { toasts, stop } = captureToasts()
        register()
        sw.dispatchEvent(new Event('controllerchange'))
        stop()

        expect(toasts).toHaveLength(0)
    })

    test('one update toast with a reload action on controllerchange, never repeated', () => {
        const sw = fakeWorker({})
        const { toasts, stop } = captureToasts()
        register()
        sw.dispatchEvent(new Event('controllerchange'))
        sw.dispatchEvent(new Event('controllerchange'))
        stop()

        expect(toasts).toHaveLength(1)
        expect(toasts[0]).toMatchObject({ type: 'info', timeout: 15000, message: 'نسخهٔ جدید آماده است' })
        expect(toasts[0].action.label).toBe('تازه کردن صفحه')
        expect(typeof toasts[0].action.handler).toBe('function')
    })

    test('no update toast inside a frame', () => {
        const sw = fakeWorker({})
        const top = vi.spyOn(window, 'top', 'get').mockReturnValue({})
        const { toasts, stop } = captureToasts()
        register()
        sw.dispatchEvent(new Event('controllerchange'))
        stop()
        top.mockRestore()

        expect(toasts).toHaveLength(0)
    })
})
