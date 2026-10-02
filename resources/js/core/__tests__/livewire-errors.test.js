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
        dispatchInit()
        expect(hook).toHaveBeenCalledTimes(1)
        dispatchInit()
        expect(hook).toHaveBeenCalledTimes(1)
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

    test('ignores expired-session errors (419)', () => {
        arm()
        const preventDefault = vi.fn()
        const toastSpy = vi.fn()
        window.addEventListener('toast', toastSpy)

        hookFailCallback({ status: 419, content: {}, preventDefault })

        expect(preventDefault).not.toHaveBeenCalled()
        expect(toastSpy).not.toHaveBeenCalled()
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
