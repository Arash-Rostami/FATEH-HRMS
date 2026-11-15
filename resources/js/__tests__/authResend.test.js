import { describe, expect, test, vi, afterEach } from 'vitest'

const registered = {}
globalThis.Alpine = { data: (name, factory) => { registered[name] = factory } }
await import('../auth.js')
document.dispatchEvent(new Event('alpine:init'))
const authResend = registered.authResend

afterEach(() => {
    vi.useRealTimers()
})

describe('authResend', () => {
    test('counts down to zero and stops', () => {
        vi.useFakeTimers()
        const t = authResend()
        t.start(3)
        vi.advanceTimersByTime(2000)
        expect(t.left).toBe(1)
        vi.advanceTimersByTime(5000)
        expect(t.left).toBe(0)
    })

    test('a second start replaces the running countdown instead of stacking intervals', () => {
        vi.useFakeTimers()
        const t = authResend()
        t.start(10)
        vi.advanceTimersByTime(3000)
        t.start(60)
        vi.advanceTimersByTime(1000)
        expect(t.left).toBe(59)
    })

    test('renders Persian digits and ignores a bad seconds value', () => {
        const t = authResend()
        expect(t.fa(45)).toBe('۴۵')
        t.start('abc')
        expect(t.left).toBe(0)
    })
})
