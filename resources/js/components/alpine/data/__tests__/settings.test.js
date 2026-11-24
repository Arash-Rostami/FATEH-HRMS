import { describe, expect, test, vi, beforeEach } from 'vitest'
import settings from '../settings.js'

function make({ compact = false } = {}) {
    const s = settings()
    s.$store = {
        colVisibility: { maps: { dms: ['a', 'b'], ths: ['c'], reservation: [] }, reset: vi.fn() },
        density: { compact, toggle: vi.fn() }
    }
    return s
}

function makeApp({ app = {}, push = {}, permission = 'default' } = {}) {
    const calls = []
    const enabled = { contact: false, channel: false }
    const s = settings()
    globalThis.Notification = { permission }
    navigator.storage = { persist: vi.fn() }
    s.$store = {
        app: {
            installed: false, installable: true, ios: false, persisted: false, badgeSupported: true,
            install: vi.fn(async () => { calls.push('install'); return 'accepted' }),
            persist: vi.fn(async () => { calls.push('persist'); return true }),
            ...app
        },
        push: {
            supported: true,
            isEnabled: (scope) => enabled[scope],
            toggle: vi.fn(async (scope) => { calls.push(`toggle:${scope}`); enabled[scope] = true }),
            ...push
        }
    }
    return { s, calls, enabled }
}

const rowStatus = (s, id) => s.appRows().find(r => r.id === id).status

beforeEach(() => {
    delete globalThis.Notification
    delete navigator.storage
    localStorage.clear()
    document.documentElement.dataset.uid = '7'
    document.documentElement.classList.remove('declutter')
})

describe('settings table panel', () => {
    test('resetTableModule clears only that module keys and column mask', () => {
        localStorage.setItem('table-tools:dms', 'true')
        localStorage.setItem('max:dms:7', 'true')
        localStorage.setItem('table-tools:ths', 'true')
        localStorage.setItem('max:ths:7', 'true')
        const s = make()
        s.resetTableModule('dms')
        expect(localStorage.getItem('table-tools:dms')).toBeNull()
        expect(localStorage.getItem('max:dms:7')).toBeNull()
        expect(localStorage.getItem('table-tools:ths')).toBe('true')
        expect(localStorage.getItem('max:ths:7')).toBe('true')
        expect(s.$store.colVisibility.reset).toHaveBeenCalledTimes(1)
        expect(s.$store.colVisibility.reset).toHaveBeenCalledWith('dms')
    })

    test('taskboard module reset clears its card-field mask', () => {
        const s = make()
        s.resetTableModule('taskboard')
        expect(s.$store.colVisibility.reset).toHaveBeenCalledWith('taskboard')
    })

    test('taskboard counts hidden card fields and labels them as card sections', () => {
        const s = make()
        s.$store.colVisibility.maps.taskboard = ['labels', 'people', 'deadline']
        s.refreshTableState()
        expect(s.tableState.modules.taskboard.hiddenCols).toBe(3)
        expect(s.tableModules.find(m => m.id === 'taskboard').colsLabel).toBe('بخش کارت')
    })

    test('refreshTableState reports hidden columns, collapsed toolbar and maximized flags', () => {
        localStorage.setItem('table-tools:ths', 'true')
        localStorage.setItem('max:taskboard:7', 'true')
        localStorage.setItem('menu_declutter', 'true')
        const s = make({ compact: true })
        s.refreshTableState()
        expect(s.tableState.modules.dms.hiddenCols).toBe(2)
        expect(s.tableState.modules.ths.toolbarCollapsed).toBe(true)
        expect(s.tableState.modules.dms.toolbarCollapsed).toBe(false)
        expect(s.tableState.modules.taskboard.maximized).toBe(true)
        expect(s.tableState.compact).toBe(true)
        expect(s.tableState.menuDeclutter).toBe(true)
    })

    test('resetDeclutter writes false, removes html class and dispatches the event', () => {
        localStorage.setItem('menu_declutter', 'true')
        document.documentElement.classList.add('declutter')
        const listener = vi.fn()
        window.addEventListener('declutter', listener)
        const s = make()
        s.resetDeclutter()
        window.removeEventListener('declutter', listener)
        expect(localStorage.getItem('menu_declutter')).toBe('false')
        expect(document.documentElement.classList.contains('declutter')).toBe(false)
        expect(listener.mock.calls[0][0].detail.on).toBe(false)
        expect(s.tableState.menuDeclutter).toBe(false)
    })

    test('resetDensity toggles only when compact', () => {
        const off = make()
        off.resetDensity()
        expect(off.$store.density.toggle).not.toHaveBeenCalled()
        const on = make({ compact: true })
        on.resetDensity()
        expect(on.$store.density.toggle).toHaveBeenCalledTimes(1)
    })

    test('resetTables resets every module, density and declutter', () => {
        ;['dms', 'ths', 'reservation', 'taskboard', 'ai-report', 'correspondence', 'launchpad', 'media'].forEach(id => {
            localStorage.setItem(`table-tools:${id}`, 'true')
            localStorage.setItem(`max:${id}:7`, 'true')
        })
        localStorage.setItem('menu_declutter', 'true')
        const s = make({ compact: true })
        s.resetTables()
        expect(localStorage.length).toBe(1)
        expect(localStorage.getItem('menu_declutter')).toBe('false')
        expect(s.$store.colVisibility.reset.mock.calls.map(c => c[0])).toEqual(['dms', 'ths', 'reservation', 'taskboard', 'ai-report', 'correspondence', 'launchpad', 'media'])
        expect(s.$store.density.toggle).toHaveBeenCalledTimes(1)
    })

    test('ai-report is a registered table module that counts hidden columns and resets without a maximize key', () => {
        const s = make()
        s.$store.colVisibility.maps['ai-report'] = ['owner', 'state']
        localStorage.setItem('table-tools:ai-report', 'true')
        s.refreshTableState()
        expect(s.tableModules.map(m => m.id)).toEqual(['dms', 'ths', 'reservation', 'taskboard', 'ai-report', 'correspondence', 'launchpad', 'media'])
        expect(s.tableState.modules['ai-report']).toEqual({ hiddenCols: 2, toolbarCollapsed: true, maximized: false })

        s.resetTableModule('ai-report')
        expect(s.$store.colVisibility.reset).toHaveBeenCalledWith('ai-report')
        expect(localStorage.getItem('table-tools:ai-report')).toBeNull()
    })

    test('correspondence reset clears only its own toolbar and column keys', () => {
        localStorage.setItem('table-tools:correspondence', 'true')
        localStorage.setItem('max:correspondence:7', 'true')
        localStorage.setItem('table-tools:ths', 'true')
        const s = make()

        s.resetTableModule('correspondence')

        expect(localStorage.getItem('table-tools:correspondence')).toBeNull()
        expect(localStorage.getItem('max:correspondence:7')).toBeNull()
        expect(localStorage.getItem('table-tools:ths')).toBe('true')
        expect(s.$store.colVisibility.reset).toHaveBeenCalledWith('correspondence')
    })

    test('export includes the ai-report column mask and toolbar keys', async () => {
        localStorage.setItem('ai-report-col-hidden', '["owner"]')
        localStorage.setItem('table-tools:ai-report', 'true')
        const s = make()
        let blob
        URL.createObjectURL = vi.fn((b) => { blob = b; return 'blob:x' })
        URL.revokeObjectURL = vi.fn()
        s.exportSettings()
        const data = JSON.parse(await blob.text())
        expect(data).toEqual({ 'ai-report-col-hidden': '["owner"]', 'table-tools:ai-report': 'true' })
    })
})

describe('settings chat panel', () => {
    test('saveChatSetting merges one field and keeps fontSteps, headerDeclutter and unknown fields', () => {
        localStorage.setItem('chat-settings:contact', JSON.stringify({ fontSteps: { 5: 1 }, headerDeclutter: true, future: 'x', messageFont: 'old' }))
        const s = make()
        s.refreshChatState()

        s.saveChatSetting('contact', 'messageFont', 'vazirmatn')

        expect(JSON.parse(localStorage.getItem('chat-settings:contact'))).toEqual({ fontSteps: { 5: 1 }, headerDeclutter: true, future: 'x', messageFont: 'vazirmatn' })
        expect(s.chat.contact.messageFont).toBe('vazirmatn')
    })

    test('toggleChatLook flips highlight and background pattern together and keeps other fields', () => {
        localStorage.setItem('chat-settings:contact', JSON.stringify({ fontSteps: { 5: 1 }, backgroundPatternType: 'twill' }))
        const s = make()
        s.refreshChatState()

        s.toggleChatLook('contact')
        expect(JSON.parse(localStorage.getItem('chat-settings:contact'))).toEqual({ fontSteps: { 5: 1 }, backgroundPatternType: 'twill', isHighlighted: true, backgroundPattern: 'on' })
        expect(s.chat.contact.isHighlighted).toBe(true)
        expect(s.chat.contact.backgroundPattern).toBe('on')

        s.toggleChatLook('contact')
        expect(JSON.parse(localStorage.getItem('chat-settings:contact'))).toMatchObject({ isHighlighted: false, backgroundPattern: 'off', fontSteps: { 5: 1 } })
    })

    test('chat state falls back to defaults and corrupt JSON or blocked storage never throw', () => {
        localStorage.setItem('chat-settings:channel', '{not json')
        const s = make()
        s.refreshChatState()

        expect(s.chat.channel).toEqual({ messageFont: 'default', isHighlighted: false, backgroundPattern: 'off', backgroundPatternType: 'mesh', mineOpacity: 100 })
        expect(s.chatSettings('channel')).toEqual({})
        s.saveChatSetting('channel', 'isHighlighted', true)
        expect(JSON.parse(localStorage.getItem('chat-settings:channel'))).toEqual({ isHighlighted: true })

        const get = vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('blocked') })
        const set = vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('blocked') })
        expect(() => { s.saveChatSetting('project', 'messageFont', 'x'); s.toggleSidebarDefault('media'); s.refreshChatState() }).not.toThrow()
        get.mockRestore()
        set.mockRestore()
    })

    test('chat scale steps clamp between 0 and 4, persist, and announce the change', () => {
        const listener = vi.fn()
        window.addEventListener('chat-scale-changed', listener)
        const s = make()

        s.stepScale(-9)
        expect(s.scaleIndex).toBe(0)
        expect(localStorage.getItem('chat-ui-scale')).toBe('0')
        expect(listener.mock.calls[0][0].detail).toBe(0)

        s.stepScale(9)
        expect(s.scaleIndex).toBe(4)
        expect(localStorage.getItem('chat-ui-scale')).toBe('4')
        expect(document.documentElement.style.getPropertyValue('--chat-scale')).toBe('1.2')
        window.removeEventListener('chat-scale-changed', listener)
    })

    test('my-bubble opacity steps per module, clamps at 40 and 100, and ignores out-of-list stored values', () => {
        localStorage.setItem('chat-settings:channel', JSON.stringify({ mineOpacity: 33 }))
        const s = make()
        s.refreshChatState()
        expect(s.chat.channel.mineOpacity).toBe(100)

        s.stepMine('contact', 1)
        expect(s.chat.contact.mineOpacity).toBe(100)
        expect(localStorage.getItem('chat-settings:contact')).toBeNull()

        s.stepMine('contact', -1)
        s.stepMine('contact', -1)
        expect(s.chat.contact.mineOpacity).toBe(70)
        expect(JSON.parse(localStorage.getItem('chat-settings:contact')).mineOpacity).toBe(70)
        expect(s.chat.project.mineOpacity).toBe(100)

        s.stepMine('contact', -9)
        expect(s.chat.contact.mineOpacity).toBe(70)
        s.stepMine('contact', -1)
        s.stepMine('contact', -1)
        s.stepMine('contact', -1)
        expect(s.chat.contact.mineOpacity).toBe(40)
        expect(s.mineLabel('contact').endsWith('٪')).toBe(true)
    })

    test('sidebar defaults write 1 and 0 and read back through refreshChatState', () => {
        const s = make()
        s.refreshChatState()
        s.toggleSidebarDefault('project')
        expect(localStorage.getItem('sb-collapsed:project')).toBe('1')
        expect(s.sidebar.project).toBe(true)

        s.toggleSidebarDefault('project')
        expect(localStorage.getItem('sb-collapsed:project')).toBe('0')

        localStorage.setItem('sb-collapsed:media', '1')
        s.refreshChatState()
        expect(s.sidebar).toEqual({ contact: false, channel: false, project: false, media: true })
    })

    test('resetChat removes only the five display fields, the scale and the sidebar keys', () => {
        localStorage.setItem('chat-settings:contact', JSON.stringify({ messageFont: 'vazirmatn', isHighlighted: true, backgroundPattern: 'on', backgroundPatternType: 'twill', mineOpacity: 55, fontSteps: { 3: 2 }, headerDeclutter: true }))
        localStorage.setItem('chat-settings:channel', JSON.stringify({ messageFont: 'vazirmatn' }))
        localStorage.setItem('chat-ui-scale', '4')
        document.documentElement.style.setProperty('--chat-scale', '1.2')
        ;['contact', 'channel', 'project', 'media'].forEach(id => localStorage.setItem(`sb-collapsed:${id}`, '1'))
        const s = make()

        s.resetChat()

        expect(JSON.parse(localStorage.getItem('chat-settings:contact'))).toEqual({ fontSteps: { 3: 2 }, headerDeclutter: true })
        expect(localStorage.getItem('chat-settings:channel')).toBeNull()
        expect(localStorage.getItem('chat-ui-scale')).toBeNull()
        expect(document.documentElement.style.getPropertyValue('--chat-scale')).toBe('')
        expect(s.scaleIndex).toBe(2)
        ;['contact', 'channel', 'project', 'media'].forEach(id => expect(localStorage.getItem(`sb-collapsed:${id}`)).toBeNull())
    })

    test('export covers the chat scale, per-module chat settings and sidebar keys', async () => {
        const keys = ['chat-ui-scale', 'chat-settings:contact', 'chat-settings:channel', 'chat-settings:project', 'sb-collapsed:contact', 'sb-collapsed:channel', 'sb-collapsed:project', 'sb-collapsed:media']
        keys.forEach(key => localStorage.setItem(key, '1'))
        let blob
        URL.createObjectURL = vi.fn((b) => { blob = b; return 'blob:x' })
        URL.revokeObjectURL = vi.fn()

        make().exportSettings()

        expect(Object.keys(JSON.parse(await blob.text())).sort()).toEqual([...keys].sort())
    })
})

describe('settings layout card', () => {
    test('setDeclutter writes true or false, toggles the html class and dispatches the event', () => {
        const listener = vi.fn()
        window.addEventListener('declutter', listener)
        const s = make()

        s.setDeclutter(true)
        expect(localStorage.getItem('menu_declutter')).toBe('true')
        expect(document.documentElement.classList.contains('declutter')).toBe(true)
        expect(listener.mock.calls[0][0].detail.on).toBe(true)
        expect(s.layout.declutter).toBe(true)

        s.setDeclutter(false)
        window.removeEventListener('declutter', listener)
        expect(localStorage.getItem('menu_declutter')).toBe('false')
        expect(document.documentElement.classList.contains('declutter')).toBe(false)
        expect(listener.mock.calls[1][0].detail.on).toBe(false)
        expect(s.layout.declutter).toBe(false)
    })

    test('_resetDeclutter goes through setDeclutter(false)', () => {
        const s = make()
        const spy = vi.spyOn(s, 'setDeclutter')
        s._resetDeclutter()
        expect(spy).toHaveBeenCalledWith(false)
    })

    test('each setter writes the exact format its owner reads', () => {
        const s = make()
        s.refreshLayoutState()

        s.setMenuViewMode('list')
        expect(localStorage.getItem('menu_view_mode')).toBe('"list"')
        expect(s.layout.viewMode).toBe('list')

        s.toggleMenuSortAlpha()
        expect(localStorage.getItem('menu_sort_alpha')).toBe('true')
        expect(s.layout.sortAlpha).toBe(true)
        s.toggleMenuSortAlpha()
        expect(localStorage.getItem('menu_sort_alpha')).toBe('false')

        s.setBottomLabels('icons')
        expect(localStorage.getItem('btm-labels')).toBe('icons')
        expect(s.layout.labels).toBe('icons')

        s.setClockMode('en')
        expect(localStorage.getItem('timer-mode')).toBe('en')
        expect(s.layout.clock).toBe('en')
    })

    test('refreshLayoutState reads the stored values and falls back on unknown ones', () => {
        localStorage.setItem('menu_view_mode', '"grouped"')
        localStorage.setItem('menu_sort_alpha', 'true')
        localStorage.setItem('menu_declutter', 'true')
        localStorage.setItem('btm-labels', 'smart')
        localStorage.setItem('timer-mode', 'en')
        const s = make()
        s.refreshLayoutState()
        expect(s.layout).toEqual({ viewMode: 'grouped', sortAlpha: true, declutter: true, labels: 'smart', clock: 'en' })

        localStorage.setItem('menu_view_mode', '{bad')
        localStorage.setItem('btm-labels', 'weird')
        localStorage.removeItem('timer-mode')
        s.refreshLayoutState()
        expect(s.layout).toMatchObject({ viewMode: 'grid', labels: 'all', clock: 'fa' })
    })

    test('layoutChoices expose the three choice lists with their setters and fields', () => {
        const s = make()

        expect(s.layoutChoices.map(c => [c.setter, c.field, c.options.map(o => o.value)])).toEqual([
            ['setMenuViewMode', 'viewMode', ['grid', 'list', 'grouped']],
            ['setBottomLabels', 'labels', ['all', 'icons', 'smart']],
            ['setClockMode', 'clock', ['fa', 'en']]
        ])
        expect(s.layoutChoices[2].options.map(o => o.label)).toEqual(['فارسی', 'انگلیسی'])
        s.layoutChoices.forEach(c => c.options.forEach(o => expect(o.label).toBeTruthy()))
    })

    test('unknown modes and presets are ignored', () => {
        const s = make()
        s.refreshLayoutState()
        s.setMenuViewMode('cards')
        s.setBottomLabels('tiny')
        s.setClockMode('ar')
        expect(localStorage.getItem('menu_view_mode')).toBeNull()
        expect(localStorage.getItem('btm-labels')).toBeNull()
        expect(localStorage.getItem('timer-mode')).toBeNull()
        expect(s.layout).toMatchObject({ viewMode: 'grid', labels: 'all', clock: 'fa' })
    })

    test('resetLayout removes the four keys and turns declutter off', () => {
        localStorage.setItem('menu_view_mode', '"list"')
        localStorage.setItem('menu_sort_alpha', 'true')
        localStorage.setItem('btm-labels', 'icons')
        localStorage.setItem('timer-mode', 'en')
        localStorage.setItem('menu_declutter', 'true')
        document.documentElement.classList.add('declutter')
        const s = make()

        s.resetLayout()

        ;['menu_view_mode', 'menu_sort_alpha', 'btm-labels', 'timer-mode'].forEach(key => expect(localStorage.getItem(key)).toBeNull())
        expect(localStorage.getItem('menu_declutter')).toBe('false')
        expect(document.documentElement.classList.contains('declutter')).toBe(false)
        expect(s.layout).toEqual({ viewMode: 'grid', sortAlpha: false, declutter: false, labels: 'all', clock: 'fa' })
    })

    test('blocked storage never throws', () => {
        const get = vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('blocked') })
        const set = vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('blocked') })
        const remove = vi.spyOn(Storage.prototype, 'removeItem').mockImplementation(() => { throw new Error('blocked') })
        const s = make()
        expect(() => {
            s.refreshLayoutState()
            s.setMenuViewMode('list')
            s.toggleMenuSortAlpha()
            s.setDeclutter(true)
            s.setBottomLabels('smart')
            s.setClockMode('en')
            s.resetLayout()
        }).not.toThrow()
        get.mockRestore()
        set.mockRestore()
        remove.mockRestore()
    })

    test('export covers the menu, bottom-nav and clock keys', async () => {
        const keys = ['menu_view_mode', 'menu_sort_alpha', 'btm-labels', 'timer-mode']
        keys.forEach(key => localStorage.setItem(key, '1'))
        let blob
        URL.createObjectURL = vi.fn((b) => { blob = b; return 'blob:x' })
        URL.revokeObjectURL = vi.fn()

        make().exportSettings()

        expect(Object.keys(JSON.parse(await blob.text())).sort()).toEqual([...keys].sort())
    })
})

describe('settings app card', () => {
    test('rows cover install, notifications, storage and badge with todo statuses on a fresh desktop', () => {
        const { s } = makeApp()

        expect(s.appRows().map(r => [r.id, r.status])).toEqual([
            ['install', 'todo'], ['notifications', 'todo'], ['persist', 'todo'], ['badge', 'todo']
        ])
    })

    test('installed app with both scopes on and persisted storage reads ok everywhere', () => {
        const { s, enabled } = makeApp({ app: { installed: true, persisted: true } })
        enabled.contact = true
        enabled.channel = true

        expect(s.appRows().map(r => r.status)).toEqual(['ok', 'ok', 'ok', 'ok'])
    })

    test('iPhone that is not installed keeps install and notifications at todo with the reason', () => {
        const { s } = makeApp({ app: { ios: true, installable: false } })
        const notifications = s.appRows().find(r => r.id === 'notifications')

        expect(rowStatus(s, 'install')).toBe('todo')
        expect(notifications.status).toBe('todo')
        expect(notifications.hint).toContain('صفحهٔ اصلی')
    })

    test('a denied permission marks notifications blocked and points to the re-enable path', () => {
        const { s } = makeApp({ permission: 'denied' })
        const notifications = s.appRows().find(r => r.id === 'notifications')

        expect(notifications.status).toBe('blocked')
        expect(notifications.hint).toBe(s.permissionHelp())
        expect(s.permissionHelp()).toContain('قفل')
    })

    test('appRows reads the live permission so a denial made elsewhere shows blocked', () => {
        const { s } = makeApp()
        expect(rowStatus(s, 'notifications')).toBe('todo')

        globalThis.Notification.permission = 'denied'

        expect(rowStatus(s, 'notifications')).toBe('blocked')
    })

    test('the notification row exposes granted from the browser permission, not from the row status', () => {
        const { s, enabled } = makeApp({ permission: 'granted' })
        enabled.contact = true

        const row = s.appRows().find(r => r.id === 'notifications')

        expect(row.status).toBe('todo')
        expect(row.granted).toBe(true)
        globalThis.Notification.permission = 'default'
        expect(s.appRows().find(r => r.id === 'notifications').granted).toBe(false)
    })

    test('permissionHelp names the iPhone settings path on iOS', () => {
        const { s } = makeApp({ app: { ios: true } })

        expect(s.permissionHelp()).toContain('تنظیمات')
        expect(s.permissionHelp()).not.toContain('قفل')
    })

    test('unsupported browsers are reported as unsupported', () => {
        const { s } = makeApp({ app: { installable: false, badgeSupported: false }, push: { supported: false } })

        expect(rowStatus(s, 'install')).toBe('unsupported')
        expect(rowStatus(s, 'notifications')).toBe('unsupported')
        expect(rowStatus(s, 'badge')).toBe('unsupported')
    })

    test('enableEverything installs, enables contact then channel, then persists', async () => {
        const { s, calls } = makeApp()

        await s.enableEverything()

        expect(calls).toEqual(['install', 'toggle:contact', 'toggle:channel', 'persist'])
        expect(s.appBusy).toBe(false)
    })

    test('enableEverything stops when the install prompt is dismissed', async () => {
        const { s, calls } = makeApp({ app: { install: vi.fn(async () => 'dismissed') } })

        await s.enableEverything()

        expect(calls).toEqual([])
        expect(s.$store.push.toggle).not.toHaveBeenCalled()
        expect(s.$store.app.persist).not.toHaveBeenCalled()
    })

    test('enableEverything still persists when notification permission is denied', async () => {
        const { s, calls } = makeApp({ push: { toggle: vi.fn(async (scope) => { calls.push(`toggle:${scope}`) }) } })

        await s.enableEverything()

        expect(calls).toEqual(['install', 'toggle:contact', 'persist'])
        expect(s.appBusy).toBe(false)
    })

    test('enableEverything still persists when notifications are unsupported', async () => {
        const { s, calls } = makeApp({ push: { supported: false } })

        await s.enableEverything()

        expect(calls).toEqual(['install', 'persist'])
    })

    test('enableEverything skips install and notifications on an uninstalled iPhone but still persists', async () => {
        const { s, calls } = makeApp({ app: { ios: true } })

        await s.enableEverything()

        expect(calls).toEqual(['persist'])
    })

    test('enableNotifications enables contact then channel through the push store and clears appBusy', async () => {
        const { s, calls } = makeApp()

        await s.enableNotifications()

        expect(calls).toEqual(['toggle:contact', 'toggle:channel'])
        expect(s.appBusy).toBe(false)
    })

    test('enableNotifications stops on denial and still clears appBusy', async () => {
        const { s, calls } = makeApp({ push: { toggle: vi.fn(async (scope) => { calls.push(`toggle:${scope}`) }) } })

        await s.enableNotifications()

        expect(calls).toEqual(['toggle:contact'])
        expect(s.appBusy).toBe(false)
    })

    test('enableNotifications does nothing while another action is busy', async () => {
        const { s, calls } = makeApp()
        s.appBusy = true

        await s.enableNotifications()

        expect(calls).toEqual([])
        expect(s.appBusy).toBe(true)
    })

    test('sendLocalTest shows a local Persian notification through the service worker without a server call', async () => {
        const showNotification = vi.fn().mockResolvedValue(undefined)
        Object.defineProperty(navigator, 'serviceWorker', { configurable: true, value: { ready: Promise.resolve({ showNotification }) } })
        const { s } = makeApp({ permission: 'granted' })

        expect(await s.sendLocalTest()).toBe(true)
        delete navigator.serviceWorker

        const [title, options] = showNotification.mock.calls[0]
        expect(title).toBe('اعلان آزمایشی')
        expect(options).toMatchObject({ dir: 'rtl', lang: 'fa', tag: 'app-test' })
    })

    test('sendLocalTest does nothing without granted permission', async () => {
        const { s } = makeApp()

        expect(await s.sendLocalTest()).toBe(false)
    })

    test('windowsShortcutCommand quotes the chrome path and ends the url with a single slash', () => {
        const { s } = makeApp()

        expect(s.windowsShortcutCommand('https://team.example.test///')).toBe('"C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe" --app=https://team.example.test/')
        expect(s.windowsShortcutCommand('https://team.example.test')).toContain('--app=https://team.example.test/')
    })

    test('windowsShortcutCommand falls back to the page origin when no url is given', () => {
        const { s } = makeApp()

        expect(s.windowsShortcutCommand('')).toBe(`"C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe" --app=${window.location.origin}/`)
    })

    test('the shared copy mixin replaces the old shortcut copier', () => {
        const { s } = makeApp()

        expect(typeof s.copyText).toBe('function')
        expect(s.copyShortcut).toBeUndefined()
        expect(s.shortcutCopied).toBeUndefined()
    })
})
