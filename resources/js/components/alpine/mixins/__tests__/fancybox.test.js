import { describe, expect, test, vi, beforeEach } from 'vitest'

const state = vi.hoisted(() => ({ instances: [], Fancybox: null }))

vi.mock('@fancyapps/ui', () => {
    state.Fancybox = {
        Plugins: { Hash: () => ({}) },
        bind: vi.fn(),
        getInstance: () => state.instances.find((box) => !box.destroyed),
    }
    return { Fancybox: state.Fancybox }
})

const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

async function boot() {
    vi.resetModules()
    const { default: fancyboxMixin } = await import('../fancybox.js')
    fancyboxMixin().initFancybox()
    await flush()
    return fancyboxMixin
}

function openBox() {
    const box = { destroyed: false, destroy: vi.fn(() => { box.destroyed = true }) }
    state.instances.push(box)
    return box
}

beforeEach(() => {
    state.instances = []
})

describe('fancyboxMixin.initFancybox', () => {
    test('drops the Hash plugin before binding, so a lightbox never writes a history entry that Livewire answers by restoring an old page', async () => {
        await boot()

        expect(state.Fancybox.Plugins.Hash).toBeUndefined()
        expect(state.Fancybox.bind).toHaveBeenCalledWith(document.documentElement, '[data-fancybox]', expect.any(Object))
    })

    test('keeps the library key map and renders captions as text, never markup', async () => {
        await boot()
        const options = state.Fancybox.bind.mock.calls[0][2]
        const caption = '<b id="pwn">x</b>'

        const node = options.Carousel.formatCaption({}, { caption })

        expect(options.keyboard).not.toBe(true)
        expect(options.formatCaption).toBeUndefined()
        expect(node.textContent).toBe(caption)
        expect(node.children.length).toBe(0)
        expect(options.Carousel.formatCaption({}, {})).toBe('')
    })

    test('binds once however many components call it', async () => {
        const fancyboxMixin = await boot()
        const calls = state.Fancybox.bind.mock.calls.length

        fancyboxMixin().initFancybox()
        await flush()

        expect(state.Fancybox.bind.mock.calls.length).toBe(calls)
    })

    test('a page swap destroys every open lightbox first, so no dead full-screen dialog is saved into the back-button snapshot', async () => {
        await boot()
        const first = openBox()
        const second = openBox()

        document.dispatchEvent(new CustomEvent('livewire:navigating'))

        expect(first.destroy).toHaveBeenCalledTimes(1)
        expect(second.destroy).toHaveBeenCalledTimes(1)
        expect(state.Fancybox.getInstance()).toBeUndefined()
    })
})
