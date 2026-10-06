import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import project from '../project.js'
import { createWireMock } from '../../mixins/__tests__/_wireMock.js'

describe('project.selectProject — race-condition guard (fixed 2026-10-05)', () => {
    test('a stale response no longer refreshes the sidebar once a newer selection has superseded it — switching projects quickly no longer lets an earlier, slower response land after the later one', async () => {
        const c = project()
        c.$wire = createWireMock()

        const flush = async () => { for (let i = 0; i < 6; i++) await Promise.resolve() }

        let resolveStale
        c.$wire._islands.workspace = {
            selectProject: vi.fn((id) => id === 1 ? new Promise((r) => { resolveStale = r }) : Promise.resolve()),
        }

        c.selectProject(1)
        c.selectProject(2)
        await flush()

        const refreshCallsAfterFreshSelection = c.$wire._islands.sidebar.refreshSidebar.mock.calls.length
        expect(refreshCallsAfterFreshSelection).toBeGreaterThan(0)

        resolveStale()
        await flush()

        expect(c.$wire._islands.sidebar.refreshSidebar).toHaveBeenCalledTimes(refreshCallsAfterFreshSelection)
    })
})

describe('project() last-open-project persistence + maximize sequencing (added 2026-10-05)', () => {
    const KEY = 'last:project:9'
    const MAX_KEY = 'max:project:9'

    beforeEach(() => {
        document.documentElement.dataset.uid = '9'
        global.Livewire = { hook: vi.fn(() => vi.fn()) }
    })

    afterEach(() => {
        delete document.documentElement.dataset.uid
        delete global.Livewire
        try { localStorage.removeItem(KEY) } catch {}
        try { localStorage.removeItem(MAX_KEY) } catch {}
    })

    test('selectProject persists the selected id under last:project:<uid>', () => {
        const c = project()
        c.$wire = createWireMock()

        c.selectProject(5)

        expect(localStorage.getItem(KEY)).toBe('5')
    })

    test('init restores a stored id via selectProject when no project is active server-side', () => {
        localStorage.setItem(KEY, '5')
        const c = project()
        c.$el = document.createElement('div')
        c.$watch = vi.fn()
        c.$wire = createWireMock({ activeProjectId: null, __instance: { id: 'x' } })
        c.initMaximize = vi.fn()
        c.selectProject = vi.fn(() => Promise.resolve())

        c.init()

        expect(c.selectProject).toHaveBeenCalledWith(5)
        c.destroy()
    })

    test('init skips the stored id once a project is already active server-side', () => {
        localStorage.setItem(KEY, '5')
        const c = project()
        c.$el = document.createElement('div')
        c.$watch = vi.fn()
        c.$wire = createWireMock({ activeProjectId: 3, __instance: { id: 'x' } })
        c.initMaximize = vi.fn()
        c.selectProject = vi.fn(() => Promise.resolve())

        c.init()

        expect(c.selectProject).not.toHaveBeenCalled()
        expect(c.initMaximize).toHaveBeenCalledOnce()
        c.destroy()
    })

    test('when both a stored maximize key and a stored last-project id exist, the project restore resolves before initMaximize runs — avoids hiding chrome around an empty pane', async () => {
        localStorage.setItem(MAX_KEY, '1')
        localStorage.setItem(KEY, '5')

        const c = project()
        c.$el = document.createElement('div')
        c.$watch = vi.fn()
        c.$wire = createWireMock({ activeProjectId: null, __instance: { id: 'x' } })

        const order = []
        let resolveSelect
        c.selectProject = vi.fn(() => new Promise((r) => { resolveSelect = r }).then(() => order.push('select')))
        c.initMaximize = vi.fn(() => order.push('maximize'))

        c.init()
        expect(c.initMaximize).not.toHaveBeenCalled()

        resolveSelect()
        await Promise.resolve(); await Promise.resolve(); await Promise.resolve()

        expect(order).toEqual(['select', 'maximize'])
        c.destroy()
    })
})
