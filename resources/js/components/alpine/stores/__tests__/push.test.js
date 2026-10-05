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
