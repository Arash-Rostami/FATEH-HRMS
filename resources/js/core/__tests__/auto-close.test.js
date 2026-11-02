import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'

const load = async () => {
    vi.resetModules()
    delete window.__tablePanelAutocloseBound
    await import('../auto-close.js')
    document.dispatchEvent(new Event('alpine:init'))
}

const registeredHook = () => {
    const call = window.Livewire.hook.mock.calls[0]
    return { hookName: call[0], handler: call[1] }
}

const dropdown = (data) => {
    const el = document.createElement('div')
    el.setAttribute('x-data', 'filamentDropdown')
    el._componentData = data ?? { close: vi.fn() }
    document.body.appendChild(el)
    return el
}

beforeEach(() => {
    window.Livewire = { hook: vi.fn() }
    window.Alpine = { $data: (el) => el._componentData }
})

afterEach(() => {
    document.body.innerHTML = ''
})

describe('auto-close — table filter and column-manager panels close after Apply', () => {
    test('registers exactly one commit hook, even when alpine:init fires twice', async () => {
        await load()
        document.dispatchEvent(new Event('alpine:init'))

        expect(window.Livewire.hook).toHaveBeenCalledTimes(1)
        expect(registeredHook().hookName).toBe('commit')
    })

    test('an applyTableFilters commit closes every filament dropdown after success', async () => {
        await load()
        const first = dropdown()
        const second = dropdown()
        const succeed = vi.fn((cb) => cb())

        registeredHook().handler({ commit: { calls: [{ method: 'applyTableFilters' }] }, succeed })

        expect(first._componentData.close).toHaveBeenCalled()
        expect(second._componentData.close).toHaveBeenCalled()
    })

    test('an applyTableColumnManager commit also closes them', async () => {
        await load()
        const open = dropdown()
        const succeed = vi.fn((cb) => cb())

        registeredHook().handler({ commit: { calls: [{ method: 'applyTableColumnManager' }] }, succeed })

        expect(open._componentData.close).toHaveBeenCalled()
    })

    test('an unrelated commit never touches the dropdowns', async () => {
        await load()
        const open = dropdown()
        const succeed = vi.fn((cb) => cb())

        registeredHook().handler({ commit: { calls: [{ method: 'deleteRecord' }] }, succeed })

        expect(succeed).not.toHaveBeenCalled()
        expect(open._componentData.close).not.toHaveBeenCalled()
    })

    test('a dropdown without a close method is skipped without throwing', async () => {
        await load()
        dropdown({})
        const succeed = vi.fn((cb) => cb())

        expect(() => registeredHook().handler({ commit: { calls: [{ method: 'applyTableFilters' }] }, succeed })).not.toThrow()
    })
})
