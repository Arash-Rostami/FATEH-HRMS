import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'

let hookFailCallback

function mockLivewire() {
    const hook = vi.fn((event, cb) => {
        cb({
            fail: (failCb) => {
                hookFailCallback = failCb
            },
        })
    })
    window.Livewire = { hook }
    return hook
}

function dispatchInit() {
    document.dispatchEvent(new Event('livewire:init'))
}

beforeEach(async () => {
    delete window.Livewire
    hookFailCallback = undefined
    vi.resetModules()
    await import('../livewire-errors.js')
})

afterEach(() => {
    delete window.Livewire
})

describe('livewire-errors — registration gating', () => {
    test('does nothing at import time or via livewire:init while window.Livewire is still undefined', () => {
        expect(() => dispatchInit()).not.toThrow()
        expect(hookFailCallback).toBeUndefined()
    })

    test('registers the request hook once window.Livewire becomes available via livewire:init', () => {
        const hook = mockLivewire()
        dispatchInit()
        expect(hook).toHaveBeenCalledWith('request', expect.any(Function))
        expect(hookFailCallback).toBeInstanceOf(Function)
    })

    test('a second livewire:init dispatch does not re-register the hook', () => {
        const hook = mockLivewire()
        const requestHooks = () => hook.mock.calls.filter(([event]) => event === 'request').length
        dispatchInit()
        expect(requestHooks()).toBe(1)
        dispatchInit()
        expect(requestHooks()).toBe(1)
    })
})

describe('livewire-errors — the fail handler', () => {
    function arm() {
        mockLivewire()
        dispatchInit()
    }

    test('ignores validation errors (422) without preventing default or dispatching a toast', () => {
        arm()
        const preventDefault = vi.fn()
        const toastSpy = vi.fn()
        window.addEventListener('toast', toastSpy)

        hookFailCallback({ status: 422, content: {}, preventDefault })

        expect(preventDefault).not.toHaveBeenCalled()
        expect(toastSpy).not.toHaveBeenCalled()
        window.removeEventListener('toast', toastSpy)
    })

    test('on expired session (419) with a refreshed token toasts a retry hint and does not reload', async () => {
        arm()
        document.body.innerHTML = '<script data-csrf="old"></script>'
        const reload = vi.fn()
        vi.stubGlobal('location', { reload, href: '/x' })
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, redirected: false, text: async () => '<script data-csrf="fresh"></script>' }))
        const toastSpy = vi.fn()
        window.addEventListener('toast', toastSpy)

        hookFailCallback({ status: 419, content: {}, preventDefault: vi.fn() })
        await vi.waitFor(() => expect(toastSpy).toHaveBeenCalledTimes(1))

        expect(toastSpy.mock.calls[0][0].detail.message).toBe('دوباره تلاش کنید')
        expect(document.querySelector('[data-csrf]').getAttribute('data-csrf')).toBe('fresh')
        expect(reload).not.toHaveBeenCalled()
        vi.unstubAllGlobals()
        window.removeEventListener('toast', toastSpy)
    })

    test('a 419 for a request sent with an old token, after the token was already refreshed, toasts a retry without fetching or reloading', async () => {
        document.body.innerHTML = '<script data-csrf="old"></script>'
        arm()
        document.querySelector('[data-csrf]').setAttribute('data-csrf', 'fresh')
        const reload = vi.fn()
        const fetchMock = vi.fn()
        vi.stubGlobal('location', { reload, href: '/x' })
        vi.stubGlobal('fetch', fetchMock)
        const toastSpy = vi.fn()
        window.addEventListener('toast', toastSpy)

        hookFailCallback({ status: 419, content: {}, preventDefault: vi.fn() })

        expect(toastSpy).toHaveBeenCalledTimes(1)
        expect(toastSpy.mock.calls[0][0].detail.message).toBe('دوباره تلاش کنید')
        expect(fetchMock).not.toHaveBeenCalled()
        expect(reload).not.toHaveBeenCalled()
        vi.unstubAllGlobals()
        window.removeEventListener('toast', toastSpy)
    })

    test('on expired session (419) prevents default, toasts once, and schedules exactly one reload', async () => {
        arm()
        const reload = vi.fn()
        vi.stubGlobal('location', { reload })
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, redirected: true, url: '/login' }))
        const preventDefault = vi.fn()
        const toastSpy = vi.fn()
        window.addEventListener('toast', toastSpy)

        vi.useFakeTimers()
        hookFailCallback({ status: 419, content: {}, preventDefault })
        hookFailCallback({ status: 419, content: {}, preventDefault })
        await vi.waitFor(() => expect(toastSpy).toHaveBeenCalledTimes(1))

        expect(preventDefault).toHaveBeenCalledTimes(2)
        expect(toastSpy).toHaveBeenCalledTimes(1)
        expect(toastSpy.mock.calls[0][0].detail.message).toContain('نشست شما به پایان رسید')
        expect(reload).not.toHaveBeenCalled()
        vi.advanceTimersByTime(1200)
        expect(reload).toHaveBeenCalledTimes(1)
        vi.useRealTimers()
        vi.unstubAllGlobals()
        window.removeEventListener('toast', toastSpy)
    })

    test('any other status calls preventDefault and dispatches a toast using the server message', () => {
        arm()
        const preventDefault = vi.fn()
        const toastSpy = vi.fn()
        window.addEventListener('toast', toastSpy)

        hookFailCallback({ status: 500, content: { message: 'دیتابیس در دسترس نیست' }, preventDefault })

        expect(preventDefault).toHaveBeenCalled()
        expect(toastSpy).toHaveBeenCalledTimes(1)
        expect(toastSpy.mock.calls[0][0].detail).toEqual({ message: 'دیتابیس در دسترس نیست', type: 'error' })
        window.removeEventListener('toast', toastSpy)
    })

    test('falls back to a generic Persian error message when content.message is missing', () => {
        arm()
        const preventDefault = vi.fn()
        const toastSpy = vi.fn()
        window.addEventListener('toast', toastSpy)

        hookFailCallback({ status: 500, content: {}, preventDefault })

        expect(toastSpy.mock.calls[0][0].detail.message).toBe('خطایی رخ داد؛ دوباره تلاش کنید')
        window.removeEventListener('toast', toastSpy)
    })

    test('falls back to the generic message when content itself is missing (optional chaining)', () => {
        arm()
        const preventDefault = vi.fn()
        const toastSpy = vi.fn()
        window.addEventListener('toast', toastSpy)

        hookFailCallback({ status: 500, content: undefined, preventDefault })

        expect(toastSpy.mock.calls[0][0].detail.message).toBe('خطایی رخ داد؛ دوباره تلاش کنید')
        window.removeEventListener('toast', toastSpy)
    })
})
