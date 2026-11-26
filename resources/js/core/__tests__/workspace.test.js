import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'

let ws

const mk = () => document.body.appendChild(document.createElement('div'))

const withMoveBefore = () => {
    Element.prototype.moveBefore = function (el, ref) {
        this.insertBefore(el, ref)
    }
}

beforeEach(async () => {
    withMoveBefore()
    document.body.innerHTML = ''
    document.querySelectorAll('#frame-park').forEach((el) => el.remove())
    document.documentElement.dataset.uid = '7'
    sessionStorage.clear()
    vi.resetModules()
    ws = await import('../workspace.js')
})

afterEach(() => {
    delete document.hidden
    delete document.visibilityState
    delete Element.prototype.moveBefore
    vi.useRealTimers()
    delete document.documentElement.dataset.uid
    delete window.Livewire
})

describe('workspace — frame pool', () => {
    test('mountFrame creates one iframe per pane and reuses it on re-mount', () => {
        const a = mk()
        const b = mk()
        ws.mountFrame('p', a, { src: '/m' })
        const first = a.querySelector('iframe')
        expect(first.getAttribute('src')).toBe('/m')
        ws.mountFrame('p', b, { src: '/m' })
        expect(b.querySelector('iframe')).toBe(first)
        expect(a.querySelector('iframe')).toBeNull()
    })

    test('a fresh iframe is appended, a connected one is moved with moveBefore, and without moveBefore appendChild is used', () => {
        const first = mk()
        first.moveBefore = vi.fn((el) => first.appendChild(el))
        ws.mountFrame('p', first, { src: '/m' })
        expect(first.moveBefore).not.toHaveBeenCalled()
        expect(first.querySelector('iframe')).not.toBeNull()

        const second = mk()
        second.moveBefore = vi.fn((el) => second.appendChild(el))
        ws.mountFrame('p', second, { src: '/m' })
        expect(second.moveBefore).toHaveBeenCalledWith(expect.any(HTMLIFrameElement), null)

        const plain = mk()
        ws.mountFrame('p', plain, { src: '/m' })
        expect(plain.querySelector('iframe')).not.toBeNull()
    })

    test('a pooled iframe that left the document is replaced, not reused', () => {
        const a = mk()
        ws.mountFrame('p', a, { src: '/m' })
        const dead = a.querySelector('iframe')
        document.body.innerHTML = ''
        const b = mk()
        ws.mountFrame('p', b, { src: '/m' })
        expect(b.querySelector('iframe')).not.toBe(dead)
    })

    test('onLoad fires on load and immediately for an already loaded frame', () => {
        const slot = mk()
        const cb = vi.fn()
        ws.mountFrame('p', slot, { src: '/m' }, cb)
        slot.querySelector('iframe').dispatchEvent(new Event('load'))
        expect(cb).toHaveBeenCalledTimes(1)
        ws.mountFrame('p', mk(), { src: '/m' }, cb)
        expect(cb).toHaveBeenCalledTimes(2)
    })

    test('park moves frames into #frame-park and the TTL empties the pool', () => {
        vi.useFakeTimers()
        const slot = mk()
        ws.mountFrame('p', slot, { src: '/m' })
        const f = slot.querySelector('iframe')
        ws.park()
        expect(document.getElementById('frame-park').contains(f)).toBe(true)
        vi.advanceTimersByTime(10 * 60 * 1000)
        expect(f.isConnected).toBe(false)
        const again = mk()
        ws.mountFrame('p', again, { src: '/m' })
        expect(again.querySelector('iframe')).not.toBe(f)
    })

    test('without moveBefore park drops the frames instead of reloading them twice', () => {
        delete Element.prototype.moveBefore
        const slot = mk()
        ws.mountFrame('p', slot, { src: '/m' })
        const f = slot.querySelector('iframe')
        ws.park()
        expect(f.isConnected).toBe(false)
        expect(document.getElementById('frame-park')).toBeNull()
        const again = mk()
        ws.mountFrame('p', again, { src: '/m' })
        expect(again.querySelector('iframe')).not.toBe(f)
    })

    test('binds the morph.removing hook at import when Livewire already booted, and parks when the removed tree holds a frame', async () => {
        let removing
        window.Livewire = { hook: vi.fn((event, cb) => { if (event === 'morph.removing') removing = cb }) }
        vi.resetModules()
        const fresh = await import('../workspace.js')
        expect(window.Livewire.hook).toHaveBeenCalledWith('morph.removing', expect.any(Function))

        const slot = mk()
        fresh.mountFrame('p', slot, { src: '/m' })
        const f = slot.querySelector('iframe')
        removing({ el: slot })

        expect(document.getElementById('frame-park').contains(f)).toBe(true)
        removing({ el: document.createElement('div') })
        expect(document.getElementById('frame-park').contains(f)).toBe(true)
    })

    test('parks on livewire:navigating, right before the swap, not on the earlier navigate request', () => {
        const slot = mk()
        ws.mountFrame('p', slot, { src: '/m' })
        const f = slot.querySelector('iframe')
        document.dispatchEvent(new Event('livewire:navigate'))
        expect(slot.contains(f)).toBe(true)
        document.dispatchEvent(new Event('livewire:navigating'))
        expect(f.parentNode.id).toBe('frame-park')
    })

    test('parking an already parked frame is a no-op, it is not moved again', () => {
        const slot = mk()
        ws.mountFrame('p', slot, { src: '/m' })
        const f = slot.querySelector('iframe')
        ws.park()
        const root = f.parentNode
        const moved = vi.spyOn(root, 'moveBefore')
        const appended = vi.spyOn(root, 'appendChild')
        ws.park()
        expect(f.parentNode).toBe(root)
        expect(moved).not.toHaveBeenCalled()
        expect(appended).not.toHaveBeenCalled()
    })

    test('park tells the frame it is parked and re-entry tells it it is active again', () => {
        const slot = mk()
        ws.mountFrame('p', slot, { src: '/m' })
        const f = slot.querySelector('iframe')
        f._ready = true
        const postMessage = vi.fn()
        Object.defineProperty(f, 'contentWindow', { value: { postMessage } })

        ws.park()
        expect(postMessage).toHaveBeenLastCalledWith({ ws: 'parked' }, location.origin)

        ws.mountFrame('p', mk(), { src: '/m' })
        expect(postMessage).toHaveBeenLastCalledWith({ ws: 'active' }, location.origin)
    })

    test('re-entry cancels the TTL', () => {
        vi.useFakeTimers()
        const slot = mk()
        ws.mountFrame('p', slot, { src: '/m' })
        const f = slot.querySelector('iframe')
        ws.park()
        ws.mountFrame('p', slot, { src: '/m' })
        vi.advanceTimersByTime(10 * 60 * 1000)
        expect(slot.contains(f)).toBe(true)
    })

    test('re-entering one pane re-arms the TTL so the other parked frames are still emptied', () => {
        vi.useFakeTimers()
        const a = mk()
        const b = mk()
        ws.mountFrame('a', a, { src: '/a' })
        ws.mountFrame('b', b, { src: '/b' })
        const fb = b.querySelector('iframe')
        ws.park()
        const visible = mk()
        ws.mountFrame('a', visible, { src: '/a' })
        vi.advanceTimersByTime(10 * 60 * 1000)
        expect(fb.isConnected).toBe(false)
        expect(visible.querySelector('iframe').isConnected).toBe(true)
    })

    test('drop removes the pooled frame', () => {
        const slot = mk()
        ws.mountFrame('p', slot, { src: '/m' })
        ws.drop('p')
        expect(slot.querySelector('iframe')).toBeNull()
    })
})

describe('workspace — parked frames count as hidden', () => {
    const send = (ws) => window.dispatchEvent(new MessageEvent('message', { data: { ws }, origin: location.origin, source: window }))

    test('a parked message makes document.hidden true, fires visibilitychange, and an active message restores the real value', () => {
        const real = Object.getOwnPropertyDescriptor(Document.prototype, 'hidden').get.call(document)
        ws.initParkedHidden()
        const changed = vi.fn()
        document.addEventListener('visibilitychange', changed)

        send('parked')
        expect(document.hidden).toBe(true)
        expect(document.visibilityState).toBe('hidden')

        send('active')
        expect(document.hidden).toBe(real)
        expect(changed).toHaveBeenCalledTimes(2)
        document.removeEventListener('visibilitychange', changed)
    })

    test('ignores messages from another origin or an unknown state', () => {
        ws.initParkedHidden()
        const real = document.hidden
        window.dispatchEvent(new MessageEvent('message', { data: { ws: 'parked' }, origin: 'https://evil.test', source: window }))
        send('bogus')
        expect(document.hidden).toBe(real)
    })
})

describe('workspace — field keep', () => {
    const key = () => `keep:7:${location.pathname + location.search}`

    test('saves non-empty fields keyed by wire:model, x-model, name, id and skips excluded ones', () => {
        document.body.innerHTML = `
            <textarea wire:model.live="body"></textarea>
            <input x-model="draft">
            <input name="title">
            <input id="note">
            <input type="password" name="pw">
            <input type="hidden" name="h">
            <input type="text" name="password">
            <input name="skip" data-no-keep>
            <input name="empty">`
        const fields = document.querySelectorAll('textarea, input')
        fields.forEach((f) => { if (f.name !== 'empty') f.value = 'v' })
        ws.saveFields()
        expect(JSON.parse(sessionStorage.getItem(key()))).toEqual({ 'm:body': 'v', 'f:draft': 'v', 'f:title': 'v', 'f:note': 'v' })
    })

    test('an empty field that never held text keeps the stashed draft, but a field that was typed in and cleared drops it', () => {
        sessionStorage.setItem(key(), JSON.stringify({ 'm:body': 'draft' }))
        document.body.innerHTML = '<textarea wire:model="body"></textarea>'
        ws.saveFields()
        expect(JSON.parse(sessionStorage.getItem(key()))).toEqual({ 'm:body': 'draft' })

        const t = document.querySelector('textarea')
        t.value = 'typed'
        ws.saveFields()
        t.value = ''
        ws.saveFields()
        expect(sessionStorage.getItem(key())).toBeNull()
    })

    test('a restored draft that is then cleared drops out of the stash', () => {
        sessionStorage.setItem(key(), JSON.stringify({ 'm:body': 'draft' }))
        document.body.innerHTML = '<textarea wire:model="body"></textarea>'
        ws.restoreFields()
        const t = document.querySelector('textarea')
        t.value = ''
        ws.saveFields()
        expect(sessionStorage.getItem(key())).toBeNull()
    })

    test('a field typed in on one URL does not delete the draft stored for another URL', () => {
        history.pushState({}, '', '/a')
        document.body.innerHTML = '<textarea wire:model="body">typed</textarea>'
        ws.saveFields()
        history.pushState({}, '', '/b')
        sessionStorage.setItem(`keep:7:/b`, JSON.stringify({ 'm:body': 'other' }))
        document.querySelector('textarea').value = ''
        ws.saveFields()
        expect(JSON.parse(sessionStorage.getItem('keep:7:/b'))).toEqual({ 'm:body': 'other' })
    })

    test('a field that is not mounted yet keeps its stashed draft', () => {
        sessionStorage.setItem(key(), JSON.stringify({ 'm:body': 'draft' }))
        document.body.innerHTML = '<textarea wire:model="other"></textarea>'
        ws.saveFields()
        expect(JSON.parse(sessionStorage.getItem(key()))).toEqual({ 'm:body': 'draft' })
    })

    test('a cleared field drops out and an empty map removes the entry', () => {
        document.body.innerHTML = '<textarea wire:model="body"></textarea>'
        const t = document.querySelector('textarea')
        t.value = 'x'
        ws.saveFields()
        t.value = ''
        ws.saveFields()
        expect(sessionStorage.getItem(key())).toBeNull()
    })

    test('restores only into empty, unfocused fields and dispatches a bubbling input', () => {
        sessionStorage.setItem(key(), JSON.stringify({ 'm:body': 'draft', 'm:other': 'keep' }))
        document.body.innerHTML = '<textarea wire:model="body"></textarea><textarea wire:model="other">typed</textarea>'
        const spy = vi.fn()
        document.addEventListener('input', spy)
        ws.restoreFields()
        const [a, b] = document.querySelectorAll('textarea')
        expect(a.value).toBe('draft')
        expect(b.value).toBe('typed')
        expect(spy).toHaveBeenCalledTimes(1)
        document.removeEventListener('input', spy)
    })

    test('does not restore into a focused field', () => {
        sessionStorage.setItem(key(), JSON.stringify({ 'm:body': 'draft' }))
        document.body.innerHTML = '<textarea wire:model="body"></textarea>'
        const t = document.querySelector('textarea')
        t.focus()
        ws.restoreFields()
        expect(t.value).toBe('')
    })

    test('clicking the logout button sweeps the stash and opens the keep-off window', () => {
        sessionStorage.setItem(key(), JSON.stringify({ 'm:body': 'draft' }))
        document.body.innerHTML = '<button wire:click="logout">خروج</button>'
        document.querySelector('button').click()
        expect(sessionStorage.getItem(key())).toBeNull()
        expect(sessionStorage.getItem('keepoff')).not.toBeNull()
    })

    test('clicking the logout button also clears this user chat drafts and leaves other keys alone', () => {
        document.documentElement.dataset.uid = '3'
        localStorage.setItem('chat-draft:3:contact:12', '{"body":"a"}')
        localStorage.setItem('chat-draft:3:channel:4', '{"body":"b"}')
        localStorage.setItem('chat-draft:8:contact:12', '{"body":"c"}')
        localStorage.setItem('chat-settings:contact', '{}')
        document.body.innerHTML = '<button wire:click="logout">خروج</button>'
        document.querySelector('button').click()
        expect(localStorage.getItem('chat-draft:3:contact:12')).toBeNull()
        expect(localStorage.getItem('chat-draft:3:channel:4')).toBeNull()
        expect(localStorage.getItem('chat-draft:8:contact:12')).not.toBeNull()
        expect(localStorage.getItem('chat-settings:contact')).not.toBeNull()
        localStorage.clear()
    })

    test('a composer inside [data-no-keep] is never saved by the generic keep-fields saver', () => {
        sessionStorage.removeItem('keepoff')
        document.body.innerHTML = '<footer data-room="12" data-no-keep><textarea wire:model="composer.body"></textarea></footer>'
        document.querySelector('textarea').value = 'draft'
        ws.saveFields()
        expect(sessionStorage.getItem(key())).toBeNull()
    })

    test('the keep-off window opened by a sweep suppresses saving for ten seconds', () => {
        document.body.innerHTML = '<textarea wire:model="body"></textarea>'
        document.querySelector('textarea').value = 'draft'
        sessionStorage.setItem('keepoff', String(Date.now()))
        ws.saveFields()
        expect(sessionStorage.getItem(key())).toBeNull()

        sessionStorage.setItem('keepoff', String(Date.now() - 11000))
        ws.saveFields()
        expect(JSON.parse(sessionStorage.getItem(key()))).toEqual({ 'm:body': 'draft' })
    })
})

describe('workspace — refreshCsrf', () => {
    afterEach(() => vi.unstubAllGlobals())

    test('writes the fresh token into the live data-csrf element', async () => {
        document.body.innerHTML = '<script data-csrf="old"></script>'
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, redirected: false, text: async () => '<html data-uid="7"><body><script data-csrf="new"></script></body></html>' }))
        expect(await ws.refreshCsrf()).toBe(true)
        expect(document.querySelector('[data-csrf]').getAttribute('data-csrf')).toBe('new')
    })

    test('a second 419 right after a successful refresh reuses it instead of fetching again', async () => {
        document.body.innerHTML = '<script data-csrf="old"></script>'
        const fetchMock = vi.fn().mockResolvedValue({ ok: true, redirected: false, text: async () => '<html data-uid="7"><body><script data-csrf="new"></script></body></html>' })
        vi.stubGlobal('fetch', fetchMock)
        expect(await ws.refreshCsrf()).toBe(true)
        expect(await ws.refreshCsrf()).toBe(true)
        expect(fetchMock).toHaveBeenCalledTimes(1)
    })

    test('updates the csrf meta tag too, because Livewire reads it first', async () => {
        document.head.innerHTML = '<meta name="csrf-token" content="old">'
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, redirected: false, text: async () => '<html data-uid="7"><head><meta name="csrf-token" content="new"></head><body></body></html>' }))
        expect(await ws.refreshCsrf()).toBe(true)
        expect(document.querySelector('meta[name="csrf-token"]').getAttribute('content')).toBe('new')
        document.head.innerHTML = ''
    })

    test('returns false on a login redirect, a failed response or a network error', async () => {
        document.body.innerHTML = '<script data-csrf="old"></script>'
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, redirected: true, url: 'http://x/login' }))
        expect(await ws.refreshCsrf()).toBe(false)
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, redirected: false, text: async () => '<html data-uid="7"><body><script data-csrf="old"></script></body></html>' }))
        expect(await ws.refreshCsrf()).toBe(false)
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false }))
        expect(await ws.refreshCsrf()).toBe(false)
        vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('net')))
        expect(await ws.refreshCsrf()).toBe(false)
        expect(document.querySelector('[data-csrf]').getAttribute('data-csrf')).toBe('old')
    })
})
