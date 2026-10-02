import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import soundStore from '../sound.js'

class FakeAudio {
    constructor(src) {
        FakeAudio.instances.push(this)
        this.src = src
        this.volume = 1
        this.currentTime = 0
        this._playCalls = 0
    }

    play() {
        this._playCalls++
        return Promise.resolve()
    }
}
FakeAudio.instances = []

function freshStore() {
    let def = null
    soundStore({ store: (name, d) => { def = d }, raw: (x) => x })
    return def
}

function withHidden(hidden) {
    Object.defineProperty(document, 'hidden', { configurable: true, value: hidden })
}

beforeEach(() => {
    localStorage.clear()
    document.body.innerHTML = ''
    FakeAudio.instances = []
    global.Audio = FakeAudio
    withHidden(false)
})

describe('sound.init / _load', () => {
    test('restores each scope\'s muted list from localStorage, coercing to Number and dropping non-finite entries', () => {
        localStorage.setItem('chat-muted-channels', JSON.stringify([1, '2', 'x']))
        localStorage.setItem('chat-muted-contacts', JSON.stringify([5]))
        const s = freshStore()
        s.init()
        expect(s.mutedChannels).toEqual([1, 2])
        expect(s.mutedContacts).toEqual([5])
        expect(s.mutedProjects).toEqual([])
    })

    test('a literal null entry is dropped, not coerced to id 0 — fixed 2026-10-01', () => {
        localStorage.setItem('chat-muted-channels', JSON.stringify([null]))
        const s = freshStore()
        s.init()
        expect(s.mutedChannels).toEqual([])
    })

    test('a missing key yields [], corrupt JSON is caught and also yields []', () => {
        localStorage.setItem('chat-muted-projects', '{not json')
        const s = freshStore()
        expect(() => s.init()).not.toThrow()
        expect(s.mutedProjects).toEqual([])
        expect(s.mutedChannels).toEqual([])
    })

    test('a cross-tab storage event for a tracked key reloads that scope\'s list', () => {
        const s = freshStore()
        s.init()
        localStorage.setItem('chat-muted-channels', JSON.stringify([7]))

        window.dispatchEvent(new StorageEvent('storage', { key: 'chat-muted-channels' }))
        expect(s.mutedChannels).toEqual([7])
    })

    test('a storage event for an untracked key is ignored', () => {
        const s = freshStore()
        s.init()
        window.dispatchEvent(new StorageEvent('storage', { key: 'unrelated-key' }))
        expect(s.mutedChannels).toEqual([])
    })
})

describe('sound.isMuted / toggleMute', () => {
    test('toggling a channel id on then off round-trips isMuted', () => {
        const s = freshStore()
        s.init()

        s.toggleMute(3, 'channel')
        expect(s.isMuted(3, 'channel')).toBe(true)
        expect(s.isMuted('3', 'channel')).toBe(true)

        s.toggleMute(3, 'channel')
        expect(s.isMuted(3, 'channel')).toBe(false)
    })

    test('each scope (channel/contact/project) is tracked independently', () => {
        const s = freshStore()
        s.init()
        s.toggleMute(1, 'contact')
        expect(s.isMuted(1, 'channel')).toBe(false)
        expect(s.isMuted(1, 'project')).toBe(false)
        expect(s.isMuted(1, 'contact')).toBe(true)
    })

    test('an unrecognized scope falls back to the channel list for both read and write', () => {
        const s = freshStore()
        s.init()
        s.toggleMute(4, 'bogus')
        expect(s.isMuted(4, 'channel')).toBe(true)
    })

    test('toggleMute persists the updated list under the correct key', () => {
        const s = freshStore()
        s.init()
        s.toggleMute(8, 'project')
        expect(JSON.parse(localStorage.getItem('chat-muted-projects'))).toEqual([8])
    })
})

describe('sound.isAllMuted / toggleAll', () => {
    test('isAllMuted is false for a non-array or empty ids argument', () => {
        const s = freshStore()
        s.init()
        expect(s.isAllMuted(null, 'channel')).toBe(false)
        expect(s.isAllMuted([], 'channel')).toBe(false)
    })

    test('isAllMuted is true only when every given id is already muted', () => {
        const s = freshStore()
        s.init()
        s.toggleMute(1, 'channel')
        s.toggleMute(2, 'channel')

        expect(s.isAllMuted([1, 2], 'channel')).toBe(true)
        expect(s.isAllMuted([1, 2, 3], 'channel')).toBe(false)
    })

    test('toggleAll mutes every given id when not all are currently muted', () => {
        const s = freshStore()
        s.init()
        s.toggleMute(1, 'channel')

        s.toggleAll([1, 2, 3], 'channel')
        expect(s.isAllMuted([1, 2, 3], 'channel')).toBe(true)
    })

    test('toggleAll unmutes every given id when all are currently muted', () => {
        const s = freshStore()
        s.init()
        s.toggleAll([1, 2], 'channel')
        expect(s.isAllMuted([1, 2], 'channel')).toBe(true)

        s.toggleAll([1, 2], 'channel')
        expect(s.isMuted(1, 'channel')).toBe(false)
        expect(s.isMuted(2, 'channel')).toBe(false)
    })

    test('toggleAll is a no-op for a non-array or empty ids argument', () => {
        const s = freshStore()
        s.init()
        expect(() => s.toggleAll(null, 'channel')).not.toThrow()
        expect(() => s.toggleAll([], 'channel')).not.toThrow()
        expect(s.mutedChannels).toEqual([])
    })

    test('toggleAll persists the final list', () => {
        const s = freshStore()
        s.init()
        s.toggleAll([9, 10], 'contact')
        expect(JSON.parse(localStorage.getItem('chat-muted-contacts'))).toEqual([9, 10])
    })
})

describe('sound.clearAll', () => {
    test('empties both the in-memory list and the persisted value for a scope', () => {
        const s = freshStore()
        s.init()
        s.toggleMute(1, 'channel')
        s.toggleMute(2, 'channel')

        s.clearAll('channel')
        expect(s.mutedChannels).toEqual([])
        expect(JSON.parse(localStorage.getItem('chat-muted-channels'))).toEqual([])
    })
})

describe('sound.playOutgoing', () => {
    function withSoundEl(src) {
        document.body.innerHTML = `<div data-outgoing-sound="${src}"></div>`
    }

    test('does nothing when globalHush is set', () => {
        withSoundEl('/snd.mp3')
        const s = freshStore()
        s.globalHush = true
        s.playOutgoing()
        expect(FakeAudio.instances).toHaveLength(0)
    })

    test('does nothing when the given id is muted for the scope', () => {
        withSoundEl('/snd.mp3')
        const s = freshStore()
        s.init()
        s.toggleMute(5, 'channel')
        s.playOutgoing(5, 'channel')
        expect(FakeAudio.instances).toHaveLength(0)
    })

    test('does nothing while the document is hidden', () => {
        withSoundEl('/snd.mp3')
        withHidden(true)
        const s = freshStore()
        s.playOutgoing()
        expect(FakeAudio.instances).toHaveLength(0)
    })

    test('does nothing when no [data-outgoing-sound] element exists in the DOM', () => {
        document.body.innerHTML = ''
        const s = freshStore()
        s.playOutgoing()
        expect(FakeAudio.instances).toHaveLength(0)
    })

    test('lazily creates one Audio instance from the DOM element\'s src/volume, reused across calls, rewinding currentTime each play', () => {
        withSoundEl('/snd.mp3')
        const s = freshStore()

        s.playOutgoing()
        expect(FakeAudio.instances).toHaveLength(1)
        expect(FakeAudio.instances[0].src).toBe('/snd.mp3')
        expect(FakeAudio.instances[0].volume).toBe(0.35)
        expect(FakeAudio.instances[0]._playCalls).toBe(1)

        FakeAudio.instances[0].currentTime = 42
        s.playOutgoing()
        expect(FakeAudio.instances).toHaveLength(1)
        expect(FakeAudio.instances[0]._playCalls).toBe(2)
    })

    test('a rejected play() promise is swallowed, not propagated', async () => {
        document.body.innerHTML = '<div data-outgoing-sound="/snd.mp3"></div>'
        class RejectingAudio extends FakeAudio {
            play() { return Promise.reject(new Error('blocked')) }
        }
        global.Audio = RejectingAudio
        const s = freshStore()
        expect(() => s.playOutgoing()).not.toThrow()
        await new Promise((r) => setTimeout(r, 0))
    })
})
