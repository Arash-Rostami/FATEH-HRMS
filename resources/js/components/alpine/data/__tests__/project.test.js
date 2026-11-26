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

describe('project.closeProject (desktop back arrow)', () => {
    afterEach(() => {
        delete document.documentElement.dataset.uid
        localStorage.removeItem('last:project:9')
    })

    test('closes the workspace server-side, refreshes the sidebar highlight, and forgets the stored project so a reload does not reopen it', async () => {
        document.documentElement.dataset.uid = '9'
        localStorage.setItem('last:project:9', '5')
        const c = project()
        c.$wire = createWireMock()
        c.mobileShowChat = true
        c._lastVersion = { v: 1 }
        c.$wire._islands.workspace = { closeProject: vi.fn(() => Promise.resolve()) }

        c.closeProject()
        for (let i = 0; i < 4; i++) await Promise.resolve()

        expect(c.mobileShowChat).toBe(false)
        expect(c._lastVersion).toBeNull()
        expect(localStorage.getItem('last:project:9')).toBeNull()
        expect(c.$wire._islands.workspace.closeProject).toHaveBeenCalledOnce()
        expect(c.$wire._islands.sidebar.refreshSidebar).toHaveBeenCalledOnce()
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

    test('init survives blocked chat-settings storage and still finishes starting up', () => {
        const real = Storage.prototype.getItem
        const spy = vi.spyOn(Storage.prototype, 'getItem').mockImplementation(function (key) {
            if (key === 'chat-settings:project') throw new Error('blocked')
            return real.call(this, key)
        })
        const c = project()
        c.$el = document.createElement('div')
        c.$watch = vi.fn()
        c.$wire = createWireMock({ activeProjectId: 3, __instance: { id: 'x' } })
        c.initMaximize = vi.fn()

        expect(() => c.init()).not.toThrow()
        expect(c.initMaximize).toHaveBeenCalledOnce()
        c.destroy()
        spy.mockRestore()
    })

    test('destroy cancels a pending header-declutter save so it cannot fire after leaving', () => {
        vi.useFakeTimers()
        const c = project()
        c.$el = document.createElement('div')
        c.$watch = vi.fn()
        c.$wire = createWireMock({ activeProjectId: 3, __instance: { id: 'x' } })
        c.initMaximize = vi.fn()
        c.init()
        const persist = vi.spyOn(c, '_persistChatSettings')

        c.toggleHeaderDeclutter()
        c.destroy()
        vi.advanceTimersByTime(500)

        expect(persist).not.toHaveBeenCalled()
        vi.useRealTimers()
    })
})

describe('project.scrollToMessage (added 2026-10-09)', () => {
    afterEach(() => { document.body.innerHTML = '' })

    test('flashes and scrolls the matching chat row and clears a previous flash', () => {
        document.body.innerHTML = '<div data-rf="project-message-1" class="record-focus-flash"></div><div data-rf="project-message-2"></div>'
        const target = document.querySelector('[data-rf="project-message-2"]')
        target.scrollIntoView = vi.fn()

        project().scrollToMessage(2)

        expect(target.scrollIntoView).toHaveBeenCalledWith({ behavior: 'smooth', block: 'center' })
        expect(target.classList.contains('record-focus-flash')).toBe(true)
        expect(document.querySelector('[data-rf="project-message-1"]').classList.contains('record-focus-flash')).toBe(false)
    })

    test('ignores a missing id or a row that is not rendered', () => {
        expect(() => project().scrollToMessage(0)).not.toThrow()
        expect(() => project().scrollToMessage(99)).not.toThrow()
    })
})

describe('project._persistChatSettings merges into the stored blob', () => {
    beforeEach(() => localStorage.clear())

    test('keeps fields stored by Settings or other code and overwrites only its own four', () => {
        localStorage.setItem('chat-settings:project', JSON.stringify({ messageFont: 'vazirmatn', fontSteps: { 4: 1 }, headerDeclutter: true, isHighlighted: false }))
        const c = project()
        c.isHighlighted = true
        c.backgroundPattern = 'on'
        c.backgroundPatternType = 'twill'

        c._persistChatSettings()

        expect(JSON.parse(localStorage.getItem('chat-settings:project'))).toEqual({
            messageFont: 'vazirmatn',
            fontSteps: { 4: 1 },
            isHighlighted: true,
            backgroundPattern: 'on',
            backgroundPatternType: 'twill',
            headerDeclutter: false
        })
    })

    test('a corrupt stored blob is replaced instead of throwing', () => {
        localStorage.setItem('chat-settings:project', '{broken')
        const c = project()

        expect(() => c._persistChatSettings()).not.toThrow()
        expect(JSON.parse(localStorage.getItem('chat-settings:project')).backgroundPatternType).toBe('mesh')
    })
})

describe('project pulse project-id guard (added 2026-11-02)', () => {
    afterEach(() => vi.unstubAllGlobals())

    test('a numeric project id is used, anything else (null, a stringified function, text) is treated as no project', () => {
        const c = project()
        c.$wire = createWireMock()

        c.$wire.activeProjectId = 12
        expect(c._projectId()).toBe(12)

        for (const bad of [null, undefined, '12', () => {}]) {
            c.$wire.activeProjectId = bad
            expect(c._projectId()).toBeNull()
        }
    })

    test('the silent version sync never requests the pulse endpoint without a real project id', async () => {
        const fetchMock = vi.fn()
        vi.stubGlobal('fetch', fetchMock)
        const c = project()
        c.$wire = createWireMock()
        c.$wire.activeProjectId = () => {}

        await c._syncVersionSilently()

        expect(fetchMock).not.toHaveBeenCalled()
    })
})

describe('project team chat draft backup (added 2026-11-26)', () => {
    const KEY = 'chat-draft::project:12'
    const withBox = (value = '') => {
        const el = { value, style: {}, scrollHeight: 300 }
        vi.stubGlobal('document', { documentElement: { dataset: {} }, getElementById: vi.fn(() => el) })
        return el
    }
    const withStorage = (seed = {}) => {
        const store = { ...seed }
        vi.stubGlobal('localStorage', {
            getItem: vi.fn((k) => (k in store ? store[k] : null)),
            setItem: vi.fn((k, v) => { store[k] = v }),
            removeItem: vi.fn((k) => { delete store[k] }),
        })
        return store
    }
    const makeChat = () => {
        const c = project()
        c.$wire = createWireMock()
        c.$wire.activeProjectId = 12
        c.$nextTick = (cb) => cb?.()
        c.scrollToBottom = vi.fn()
        return c
    }
    afterEach(() => vi.unstubAllGlobals())

    test('typing saves the draft per project and clearing the box drops it', () => {
        withBox()
        const store = withStorage()
        const c = makeChat()

        c.saveChatDraft('hello team')
        expect(JSON.parse(store[KEY]).body).toBe('hello team')

        c.saveChatDraft('   ')
        expect(store[KEY]).toBeUndefined()
    })

    test('no project id means nothing is saved', () => {
        withBox()
        const store = withStorage()
        const c = makeChat()
        c.$wire.activeProjectId = null

        c.saveChatDraft('hello')

        expect(Object.keys(store)).toHaveLength(0)
    })

    test('an empty box is refilled from a fresh stored draft and the composer state is updated without a request', () => {
        const el = withBox('')
        withStorage({ [KEY]: JSON.stringify({ body: 'unsent text', at: Date.now() }) })
        const c = makeChat()

        c.restoreChatDraft(el)

        expect(el.value).toBe('unsent text')
        expect(c.$wire.set).toHaveBeenCalledWith('chatComposer.body', 'unsent text', false)
        expect(el.style.height).toBe('120px')
    })

    test('restore never overwrites existing text and ignores and deletes an expired draft', () => {
        const typed = withBox('already typing')
        const store = withStorage({ [KEY]: JSON.stringify({ body: 'old', at: Date.now() }) })
        const c = makeChat()

        c.restoreChatDraft(typed)
        expect(typed.value).toBe('already typing')

        const empty = withBox('')
        store[KEY] = JSON.stringify({ body: 'stale', at: Date.now() - 25 * 60 * 60 * 1000 })
        c.restoreChatDraft(empty)

        expect(empty.value).toBe('')
        expect(store[KEY]).toBeUndefined()
    })

    test('a confirmed send (box empty after the call) drops the draft, an unsent box keeps it', async () => {
        const el = withBox('message')
        const store = withStorage({ [KEY]: JSON.stringify({ body: 'message', rev: 'r1', at: Date.now() }) })
        const c = makeChat()
        c.$wire.sendChatMessage = vi.fn(async () => { el.value = '' })

        await c.sendMessage()
        expect(store[KEY]).toBeUndefined()

        const kept = withBox('message')
        store[KEY] = JSON.stringify({ body: 'message', at: Date.now() })
        const d = makeChat()
        d.$wire.sendChatMessage = vi.fn(async () => {})
        d.sending = false

        await d.sendMessage()
        expect(store[KEY]).toBeDefined()
        expect(kept.value).toBe('message')
    })

    test('newer typing during a pending send is never deleted: only the submitted draft is dropped and the newer text is put back', async () => {
        const el = withBox('first message')
        const store = withStorage({ [KEY]: JSON.stringify({ body: 'first message', at: Date.now() }) })
        const c = makeChat()
        c.$wire.sendChatMessage = vi.fn(async () => {
            c.saveChatDraft('second message')
            el.value = ''
        })

        await c.sendMessage()

        expect(JSON.parse(store[KEY]).body).toBe('second message')
        expect(el.value).toBe('second message')
    })

    test('newer text that merely starts with the sent text is restored whole, never cut short', async () => {
        const el = withBox('OK')
        const store = withStorage({ [KEY]: JSON.stringify({ body: 'OK', rev: 'r1', at: Date.now() }) })
        const c = makeChat()
        c.$wire.sendChatMessage = vi.fn(async () => {
            c.saveChatDraft('')
            c.saveChatDraft('OK for Friday')
            el.value = ''
        })

        await c.sendMessage()

        expect(el.value).toBe('OK for Friday')
        expect(JSON.parse(store[KEY]).body).toBe('OK for Friday')
    })

    test('a stored draft from an older version without a revision gets one when restored, so a later send clears it', async () => {
        const el = withBox('')
        const store = withStorage({ [KEY]: JSON.stringify({ body: 'old draft', at: Date.now() }) })
        const c = makeChat()
        c.$wire.sendChatMessage = vi.fn(async () => { el.value = '' })

        c.restoreChatDraft(el)
        expect(typeof JSON.parse(store[KEY]).rev).toBe('string')

        el.value = 'old draft'
        await c.sendMessage()

        expect(store[KEY]).toBeUndefined()
    })

    test('identical text typed again after clearing the box during a pending send is kept, not mistaken for the sent draft', async () => {
        const el = withBox('OK')
        const store = withStorage({ [KEY]: JSON.stringify({ body: 'OK', rev: 'r1', at: Date.now() }) })
        const c = makeChat()
        c.$wire.sendChatMessage = vi.fn(async () => {
            c.saveChatDraft('')
            c.saveChatDraft('OK')
            el.value = ''
        })

        await c.sendMessage()

        expect(el.value).toBe('OK')
        expect(JSON.parse(store[KEY]).body).toBe('OK')
        expect(JSON.parse(store[KEY]).rev).not.toBe('r1')
    })

    test('a send that leaves the box untouched keeps the draft', async () => {
        const el = withBox('same text')
        const store = withStorage({ [KEY]: JSON.stringify({ body: 'same text', at: Date.now() }) })
        const c = makeChat()
        c.$wire.sendChatMessage = vi.fn(async () => {})

        await c.sendMessage()

        expect(store[KEY]).toBeDefined()
        expect(el.value).toBe('same text')
    })

    test('a connection error explains how to check the send and keeps the draft', async () => {
        const el = withBox('same text')
        const store = withStorage({ [KEY]: JSON.stringify({ body: 'same text', rev: 'r1', at: Date.now() }) })
        const c = makeChat()
        c.toast = vi.fn()
        c.$wire.sendChatMessage = vi.fn().mockRejectedValue(new Error('Connection lost'))

        await c.sendMessage()

        expect(c.toast).toHaveBeenCalledWith('وضعیت ارسال پیام مشخص نیست. پیش از ارسال دوباره، اتصال اینترنت را بررسی کنید. اگر VPN روشن است، آن را خاموش کنید.', 'error')
        expect(el.value).toBe('same text')
        expect(JSON.parse(store[KEY]).body).toBe('same text')
    })

    test('switching project during a pending send drops the sent project draft and never touches the other project', async () => {
        const el = withBox('sent text')
        const other = 'chat-draft::project:13'
        const store = withStorage({
            [KEY]: JSON.stringify({ body: 'sent text', rev: 'r1', at: Date.now() }),
            [other]: JSON.stringify({ body: 'other draft', rev: 'r9', at: Date.now() }),
        })
        const c = makeChat()
        c.$wire.sendChatMessage = vi.fn(async () => {
            c.$wire.activeProjectId = 13
            el.value = ''
        })

        await c.sendMessage()

        expect(store[KEY]).toBeUndefined()
        expect(JSON.parse(store[other]).body).toBe('other draft')
        expect(el.value).toBe('')
    })

    test('Enter during IME composition does not send', () => {
        withBox('x')
        withStorage()
        const c = makeChat()
        c.sendMessage = vi.fn()
        const event = { key: 'Enter', shiftKey: false, isComposing: true, preventDefault: vi.fn() }

        c.onComposerKeydown(event)

        expect(c.sendMessage).not.toHaveBeenCalled()
        expect(event.preventDefault).not.toHaveBeenCalled()
    })
})
