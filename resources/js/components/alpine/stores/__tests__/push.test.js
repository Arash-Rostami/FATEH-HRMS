import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import push from '../push.js'

class FakeNotification {
    constructor(title, options) {
        FakeNotification.instances.push({ title, options })
    }
}
FakeNotification.permission = 'default'
FakeNotification.requestPermission = vi.fn(() => Promise.resolve('granted'))

function freshStore() {
    let def = null
    push({ store: (name, d) => { def = d } })
    def.init()
    return def
}

beforeEach(() => {
    localStorage.clear()
    FakeNotification.instances = []
    FakeNotification.permission = 'default'
    FakeNotification.requestPermission = vi.fn(() => Promise.resolve('granted'))
})

afterEach(() => {
    delete global.Notification
})

describe('push.supported', () => {
    test('is false when the Notification global does not exist at store-creation time', () => {
        delete global.Notification
        const s = freshStore()
        expect(s.supported).toBe(false)
    })

    test('is true when the Notification global exists at store-creation time', () => {
        global.Notification = FakeNotification
        const s = freshStore()
        expect(s.supported).toBe(true)
    })
})

describe('push.isEnabled', () => {
    test('is false when unsupported, regardless of localStorage', () => {
        delete global.Notification
        localStorage.setItem('chat-push-channel', '1')
        const s = freshStore()
        expect(s.isEnabled('channel')).toBe(false)
    })

    test('is false when permission is not granted, even if localStorage flag is set', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'denied'
        localStorage.setItem('chat-push-channel', '1')
        const s = freshStore()
        expect(s.isEnabled('channel')).toBe(false)
    })

    test('reflects the per-scope localStorage flag loaded at init(), and stays in sync via a cross-tab storage event — a same-window write alone does not reach it, matching real browser "storage" event semantics', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        const s = freshStore()

        expect(s.isEnabled('contact')).toBe(false)

        localStorage.setItem('chat-push-contact', '1')
        expect(s.isEnabled('contact')).toBe(false)

        window.dispatchEvent(new StorageEvent('storage', { key: 'chat-push-contact' }))
        expect(s.isEnabled('contact')).toBe(true)
    })

    test('a localStorage read error during init() is swallowed, leaving enabled false — isEnabled() itself never touches localStorage anymore (fixed 2026-10-05, reactivity bug)', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        const original = global.localStorage
        global.localStorage = { getItem: () => { throw new Error('blocked') } }

        let s
        expect(() => { s = freshStore() }).not.toThrow()
        expect(s.isEnabled('channel')).toBe(false)

        global.localStorage = original
    })
})

describe('push.toggle', () => {
    test('is a no-op when unsupported', async () => {
        delete global.Notification
        const s = freshStore()
        await s.toggle('channel')
        expect(localStorage.getItem('chat-push-channel')).toBeNull()
    })

    test('disables (writes "0") when already enabled', async () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        localStorage.setItem('chat-push-channel', '1')
        const s = freshStore()

        await s.toggle('channel')
        expect(localStorage.getItem('chat-push-channel')).toBe('0')
    })

    test('enables directly (no permission prompt) when permission is already granted', async () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        const s = freshStore()

        await s.toggle('channel')
        expect(localStorage.getItem('chat-push-channel')).toBe('1')
        expect(FakeNotification.requestPermission).not.toHaveBeenCalled()
    })

    test('prompts for permission when not yet granted, and enables if the user grants it', async () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'default'
        FakeNotification.requestPermission = vi.fn(() => Promise.resolve('granted'))
        const s = freshStore()

        await s.toggle('contact')
        expect(FakeNotification.requestPermission).toHaveBeenCalled()
        expect(localStorage.getItem('chat-push-contact')).toBe('1')
    })

    test('does not enable when the user denies the permission prompt', async () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'default'
        FakeNotification.requestPermission = vi.fn(() => Promise.resolve('denied'))
        const s = freshStore()

        await s.toggle('contact')
        expect(localStorage.getItem('chat-push-contact')).toBeNull()
    })
})

describe('push.notify', () => {
    function withHidden(hidden) {
        Object.defineProperty(document, 'hidden', { configurable: true, value: hidden })
    }

    test('does nothing while the tab is visible (document.hidden is false)', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        localStorage.setItem('chat-push-channel', '1')
        withHidden(false)
        const s = freshStore()

        s.notify('title', 'body', 'channel')
        expect(FakeNotification.instances).toHaveLength(0)
    })

    test('does nothing when the scope is not enabled, even while hidden', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        withHidden(true)
        const s = freshStore()

        s.notify('title', 'body', 'channel')
        expect(FakeNotification.instances).toHaveLength(0)
    })

    test('constructs a Notification with the given title/body/icon when hidden and enabled', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        localStorage.setItem('chat-push-channel', '1')
        withHidden(true)
        const s = freshStore()

        s.notify('New message', 'hello')
        expect(FakeNotification.instances).toEqual([{ title: 'New message', options: { body: 'hello', icon: '/favicon.ico' } }])
    })

    test('badges the tab title per push while hidden and restores it on return', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        localStorage.setItem('chat-push-channel', '1')
        document.title = 'Fateh'
        withHidden(true)
        const s = freshStore()

        s.notify('a', 'b')
        s.notify('a', 'b')
        expect(document.title).toBe('(۲) پیام جدید')

        withHidden(false)
        document.dispatchEvent(new Event('visibilitychange'))
        expect(document.title).toBe('Fateh')
    })

    test('stays silent when Web Push is live and a push for this message was seen, and consumes that evidence', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        localStorage.setItem('chat-push-channel', '1')
        withHidden(true)
        const s = freshStore()
        s.live = true
        s.pushSeen.channel = 1

        s.notify('t', 'b')

        expect(FakeNotification.instances).toHaveLength(0)
        expect(s.pushSeen.channel).toBe(0)
    })

    test('a push seen for another scope never swallows this scope\'s fallback popup', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        localStorage.setItem('chat-push-contact', '1')
        withHidden(true)
        const s = freshStore()
        s.live = true
        s.pushSeen.channel = 1

        s.notify('t', 'b', 'contact')

        expect(FakeNotification.instances).toHaveLength(1)
        expect(s.pushSeen.channel).toBe(1)
    })

    test('falls back to its own popup when Web Push is live but no push arrived for the unread rise', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        localStorage.setItem('chat-push-channel', '1')
        withHidden(true)
        const s = freshStore()
        s.live = true

        s.notify('t', 'b')

        expect(FakeNotification.instances).toHaveLength(1)
    })

    test('pollsHidden follows the bell only, so a live subscription never switches the safety poll off', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        localStorage.setItem('chat-push-channel', '1')
        const s = freshStore()

        expect(s.pollsHidden('channel')).toBe(true)
        expect(s.pollsHidden('contact')).toBe(false)
        s.live = true
        expect(s.pollsHidden('channel')).toBe(true)
    })

    test('init does not throw without navigator.serviceWorker', () => {
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        localStorage.setItem('chat-push-channel', '1')

        expect(() => freshStore()).not.toThrow()
    })

    test('a throwing Notification constructor is swallowed, not propagated', () => {
        class ThrowingNotification extends FakeNotification {
            constructor() { super(); throw new Error('blocked') }
        }
        ThrowingNotification.permission = 'granted'
        global.Notification = ThrowingNotification
        localStorage.setItem('chat-push-channel', '1')
        withHidden(true)
        const s = freshStore()

        expect(() => s.notify('t', 'b')).not.toThrow()
    })
})

describe('push web push sync', () => {
    function subscription(endpoint) {
        return { endpoint, toJSON: () => ({ keys: { p256dh: 'p', auth: 'a' } }), unsubscribe: vi.fn(async () => true) }
    }

    function bareStore() {
        let def = null
        push({ store: (name, d) => { def = d } })
        return def
    }

    function webPushEnv(existing = null) {
        const listeners = {}
        const state = { sub: existing }
        const manager = {
            getSubscription: vi.fn(async () => state.sub),
            subscribe: vi.fn(async () => { state.sub = subscription('https://fcm.googleapis.com/fcm/send/new'); return state.sub })
        }
        Object.defineProperty(navigator, 'serviceWorker', {
            configurable: true,
            value: { ready: Promise.resolve({ pushManager: manager }), addEventListener: (type, handler) => { listeners[type] = handler } }
        })
        window.PushManager = class {}
        document.head.insertAdjacentHTML('beforeend', '<meta name="vapid-key" content="QUJD"><meta name="csrf-token" content="tok">')
        global.Notification = FakeNotification
        FakeNotification.permission = 'granted'
        global.fetch = vi.fn(async () => ({ ok: true, status: 204 }))
        return { listeners, state, manager }
    }

    afterEach(() => {
        delete navigator.serviceWorker
        delete window.PushManager
        document.head.querySelectorAll('meta[name="vapid-key"], meta[name="csrf-token"]').forEach((m) => m.remove())
        delete global.fetch
        sessionStorage.clear()
    })

    test('_restore turns live on only when the stored endpoint equals the browser subscription', async () => {
        webPushEnv(subscription('E1'))
        const s = bareStore()

        localStorage.setItem('chat-push-live', 'E1')
        await s._restore()
        expect(s.live).toBe(true)

        localStorage.setItem('chat-push-live', 'OLD')
        await s._restore()
        expect(s.live).toBe(false)
    })

    test('a push-confirmed message stores the endpoint and flips live on', async () => {
        const env = webPushEnv(subscription('E1'))
        const s = bareStore()
        s.init()

        await env.listeners.message({ data: { type: 'push-confirmed' } })

        expect(s.live).toBe(true)
        expect(localStorage.getItem('chat-push-live')).toBe('E1')
    })

    test('a first subscribe stays not live until confirmed, and posts the token and confirm flag', async () => {
        webPushEnv()
        const s = bareStore()
        s.enabled.channel = true

        await s._sync(true)

        expect(s.live).toBe(false)
        const [url, init] = global.fetch.mock.calls[0]
        expect(url).toBe('/push/subscription')
        expect(init.headers['X-CSRF-TOKEN']).toBe('tok')
        expect(JSON.parse(init.body)).toMatchObject({ _token: 'tok', confirm: true, scopes: { contact: false, channel: true } })
    })

    test('a confirm that lands before the subscribe request resolves is not overwritten', async () => {
        const env = webPushEnv()
        const s = bareStore()
        s.init()
        s.enabled.channel = true
        global.fetch = vi.fn(async () => {
            await env.listeners.message({ data: { type: 'push-confirmed' } })
            return { ok: true, status: 204 }
        })

        await s._sync(true)

        expect(s.live).toBe(true)
    })

    test('a rejected sync (logged out or throttled) leaves live off', async () => {
        webPushEnv(subscription('E1'))
        const s = bareStore()
        s.enabled.channel = true
        localStorage.setItem('chat-push-live', 'E1')
        s.live = true
        global.fetch = vi.fn(async () => ({ ok: false, status: 401 }))

        await s._sync()

        expect(s.live).toBe(false)
    })

    test('a throttled or failing re-sync keeps live as it was, and only a logged-out answer turns it off', async () => {
        webPushEnv(subscription('E1'))
        const s = bareStore()
        s.enabled.channel = true
        s.live = true
        localStorage.setItem('chat-push-live', 'E1')

        global.fetch = vi.fn(async () => ({ ok: false, status: 429 }))
        await s._sync()
        expect(s.live).toBe(true)

        global.fetch = vi.fn(async () => ({ ok: false, status: 500 }))
        await s._sync()
        expect(s.live).toBe(true)

        global.fetch = vi.fn(async () => ({ ok: false, status: 419 }))
        await s._sync()
        expect(s.live).toBe(false)
    })

    test('turning every scope off unsubscribes, posts both scopes false and clears the stored endpoint', async () => {
        const sub = subscription('E1')
        webPushEnv(sub)
        const s = bareStore()
        localStorage.setItem('chat-push-live', 'E1')
        s.live = true

        await s._sync(false)

        expect(JSON.parse(global.fetch.mock.calls[0][1].body).scopes).toEqual({ contact: false, channel: false })
        expect(sub.unsubscribe).toHaveBeenCalled()
        expect(s.live).toBe(false)
        expect(localStorage.getItem('chat-push-live')).toBeNull()
    })

    test('revoked permission resets live and the stored endpoint', () => {
        webPushEnv(subscription('E1'))
        FakeNotification.permission = 'denied'
        const s = bareStore()
        s.live = true
        localStorage.setItem('chat-push-live', 'E1')

        expect(s._webPushReady()).toBe(false)
        expect(s.live).toBe(false)
        expect(localStorage.getItem('chat-push-live')).toBeNull()
    })

    test('a page without the vapid meta (login page) turns live off but keeps the stored endpoint', () => {
        webPushEnv(subscription('E1'))
        document.head.querySelector('meta[name="vapid-key"]').remove()
        const s = bareStore()
        s.live = true
        localStorage.setItem('chat-push-live', 'E1')

        expect(s._webPushReady()).toBe(false)
        expect(s.live).toBe(false)
        expect(localStorage.getItem('chat-push-live')).toBe('E1')
    })

    test('_resume skips the sync while live on the same login but re-syncs after the csrf token changes', async () => {
        webPushEnv(subscription('E1'))
        const s = bareStore()
        s.enabled.channel = true
        localStorage.setItem('chat-push-live', 'E1')
        sessionStorage.setItem('chat-push-synced', 'tok|' + Date.now())

        await s._resume()
        expect(s.live).toBe(true)
        expect(global.fetch).not.toHaveBeenCalled()

        document.querySelector('meta[name="csrf-token"]').content = 'new-login'
        await s._resume()
        await vi.waitFor(() => expect(global.fetch).toHaveBeenCalledTimes(1))
    })

    test('_resume heals a dead server row by re-syncing once the last sync is older than one hour', async () => {
        webPushEnv(subscription('E1'))
        const s = bareStore()
        s.enabled.channel = true
        localStorage.setItem('chat-push-live', 'E1')
        sessionStorage.setItem('chat-push-synced', 'tok|' + (Date.now() - 7 * 60 * 60 * 1000))

        await s._resume()

        await vi.waitFor(() => expect(global.fetch).toHaveBeenCalledTimes(1))
    })

    test('a received chat-push proves delivery works and turns live on, so a lost confirm cannot double the popups', async () => {
        const env = webPushEnv(subscription('E1'))
        const s = bareStore()
        s.init()
        s.enabled.channel = true
        document.title = 'Fateh'
        Object.defineProperty(document, 'hidden', { configurable: true, value: true })

        await env.listeners.message({ data: { type: 'chat-push' } })

        expect(s.live).toBe(true)
        expect(s.pushSeen.channel).toBe(1)
        expect(localStorage.getItem('chat-push-live')).toBe('E1')
        expect(document.title).toBe('(۱) پیام جدید')
        Object.defineProperty(document, 'hidden', { configurable: true, value: false })
    })

    test('_resume keeps re-requesting the confirmation on every page while not live', async () => {
        webPushEnv(subscription('E1'))
        const s = bareStore()
        s.enabled.channel = true
        sessionStorage.setItem('chat-push-synced', 'tok')

        await s._resume()

        await vi.waitFor(() => expect(global.fetch).toHaveBeenCalledTimes(1))
        expect(JSON.parse(global.fetch.mock.calls[0][1].body).confirm).toBe(true)
    })

    test('a subscription made with a different VAPID key is replaced', async () => {
        const old = { ...subscription('OLD'), options: { applicationServerKey: new Uint8Array([1, 2, 3]).buffer } }
        const env = webPushEnv(old)
        const s = bareStore()
        s.enabled.channel = true

        await s._sync()

        expect(old.unsubscribe).toHaveBeenCalled()
        expect(env.manager.subscribe).toHaveBeenCalled()
        expect(JSON.parse(global.fetch.mock.calls[0][1].body).endpoint).toBe('https://fcm.googleapis.com/fcm/send/new')
    })

    test('a confirmation addressed to a different endpoint is ignored', async () => {
        const env = webPushEnv(subscription('E2'))
        const s = bareStore()
        s.init()

        await env.listeners.message({ data: { type: 'push-confirmed', endpoint: 'E1' } })
        expect(s.live).toBe(false)

        await env.listeners.message({ data: { type: 'push-confirmed', endpoint: 'E2' } })
        expect(s.live).toBe(true)
    })

    test('a worker push for a scope that is switched off does not badge the title', async () => {
        const env = webPushEnv(subscription('E1'))
        const s = bareStore()
        s.init()
        s.enabled.channel = true
        document.title = 'Fateh'
        Object.defineProperty(document, 'hidden', { configurable: true, value: true })

        await env.listeners.message({ data: { type: 'chat-push', scope: 'contact' } })
        expect(document.title).toBe('Fateh')

        await env.listeners.message({ data: { type: 'chat-push', scope: 'channel' } })
        expect(document.title).toMatch(/^\(۱\)/)
        Object.defineProperty(document, 'hidden', { configurable: true, value: false })
    })

    test('opting out unsubscribes the browser even when the server cannot be reached', async () => {
        const sub = subscription('E1')
        webPushEnv(sub)
        global.fetch = vi.fn(async () => { throw new Error('offline') })
        const s = bareStore()
        s.live = true

        await s._sync(false)

        expect(sub.unsubscribe).toHaveBeenCalled()
        expect(s.live).toBe(false)
    })

    test('overlapping syncs run in order and the last one carries the newest flags', async () => {
        webPushEnv()
        const s = bareStore()
        s.enabled.contact = true
        s.enabled.channel = true
        const bodies = []
        let release
        global.fetch = vi.fn(async (_url, init) => {
            bodies.push(JSON.parse(init.body))
            if (bodies.length === 1) await new Promise((resolve) => { release = resolve })
            return { ok: true, status: 204 }
        })

        const first = s._sync(false)
        await vi.waitFor(() => expect(bodies).toHaveLength(1))
        s.enabled.contact = false
        s._sync(false)
        release()
        await first

        await vi.waitFor(() => expect(bodies).toHaveLength(2))
        expect(bodies[1].scopes).toEqual({ contact: false, channel: true })
    })

    test('a stale sync that finishes after another tab changed the flags re-syncs with the newest flags', async () => {
        webPushEnv()
        const s = bareStore()
        s.enabled.contact = true
        s.enabled.channel = true
        const bodies = []
        global.fetch = vi.fn(async (_url, init) => {
            bodies.push(JSON.parse(init.body))
            if (bodies.length === 1) s.enabled.contact = false
            return { ok: true, status: 204 }
        })

        await s._sync(false)

        await vi.waitFor(() => expect(bodies).toHaveLength(2))
        expect(bodies[1].scopes).toEqual({ contact: false, channel: true })
    })

    test('an opt-out queued behind a running sync gets its own promise, so fail-closed sees the queued result', async () => {
        const sub = subscription('E1')
        webPushEnv(sub)
        const s = bareStore()
        s.enabled.contact = true
        s.enabled.channel = true
        localStorage.setItem('chat-push-live', 'E1')
        let calls = 0
        global.fetch = vi.fn(async () => {
            calls++
            return calls === 1 ? { ok: true, status: 204 } : { ok: false, status: 500 }
        })

        const first = s._sync(false)
        s.enabled.contact = false
        const queued = s._sync(false)
        await first
        await queued
        await s._failClosed()

        expect(calls).toBe(2)
        expect(sub.unsubscribe).toHaveBeenCalled()
    })

    test('a failed single-scope opt-out fails closed by unsubscribing the browser', async () => {
        const sub = subscription('E1')
        webPushEnv(sub)
        global.fetch = vi.fn(async () => ({ ok: false, status: 500 }))
        const s = bareStore()
        s.enabled.channel = true
        s.live = true
        localStorage.setItem('chat-push-live', 'E1')

        await s._sync(false)
        await s._failClosed()

        expect(localStorage.getItem('chat-push-dirty')).toBe('1')
        expect(sub.unsubscribe).toHaveBeenCalled()
        expect(s.live).toBe(false)
        expect(localStorage.getItem('chat-push-live')).toBeNull()
    })

    test('_resume retries a sync that failed earlier even while live on the same login', async () => {
        webPushEnv(subscription('E1'))
        const s = bareStore()
        s.enabled.channel = true
        localStorage.setItem('chat-push-live', 'E1')
        localStorage.setItem('chat-push-dirty', '1')
        sessionStorage.setItem('chat-push-synced', 'tok|' + Date.now())

        await s._resume()

        await vi.waitFor(() => expect(global.fetch).toHaveBeenCalledTimes(1))
    })
})
