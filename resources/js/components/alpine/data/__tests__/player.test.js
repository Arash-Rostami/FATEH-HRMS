import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import radio from '../radio.js'

class FakeAudio {
    constructor() {
        FakeAudio.instances.push(this)
        this.paused = true
        this.ended = false
        this.currentTime = 0
        this.duration = 0
        this.volume = 1
        this.muted = false
        this.src = ''
        this.preload = ''
        this.playbackRate = 1
        this.readyState = 0
        this._listeners = {}
        this.querySelectorAll = () => ({ forEach: () => {} })
        this.setAttribute = () => {}
        this.removeAttribute = () => {}
        this.appendChild = () => {}
        this.parentNode = null
    }

    addEventListener(type, fn) {
        (this._listeners[type] ||= []).push(fn)
    }

    removeEventListener(type, fn) {
        const list = this._listeners[type]
        if (!list) return
        const i = list.indexOf(fn)
        if (i !== -1) list.splice(i, 1)
    }

    _trigger(type) {
        (this._listeners[type] || []).slice().forEach((fn) => fn())
    }

    load() {}

    play() {
        this.paused = false
        this.ended = false
        return Promise.resolve()
    }

    pause() {
        this.paused = true
    }
}

FakeAudio.instances = []

const library = () => ({
    serverNow: 1795251000,
    items: [
        {
            id: 1, kind: 'audio', type: 'series', title: 'سری آزمون', icon: 'psychology',
            episodes: [
                { id: 11, title: 'جلسه یک', duration: 1000, file_url: '/storage/media/files/a1.mp3', mime: 'audio/mpeg', unlock_at: null },
                { id: 12, title: 'جلسه دو', duration: 2000, file_url: '/storage/media/files/a2.mp3', mime: 'audio/mpeg', unlock_at: null },
                { id: 13, title: 'قفل', duration: 500, file_url: null, mime: 'audio/mpeg', unlock_at: 1893456000 },
            ],
        },
        {
            id: 2, kind: 'video', type: 'single', title: 'تک ویدیو', icon: 'smart_display',
            episodes: [{ id: 21, title: 'تک', duration: 480, file_url: '/storage/media/files/v1.mp4', mime: 'video/mp4', unlock_at: null }],
        },
    ],
    progress: { '11': { position: 400, last_position: 350, completed: false } },
    notes: {},
})

const dispatchSpy = vi.fn()
const realDispatch = window.dispatchEvent.bind(window)
const realCreateElement = document.createElement.bind(document)
const realQuerySelector = document.querySelector.bind(document)
const realAudio = global.Audio
const settle = async () => {
    for (let i = 0; i < 8; i++) await Promise.resolve()
}
const okFetch = () => vi.fn().mockResolvedValue({ ok: true, status: 204 })
const videoFactory = () => {
    document.createElement = (tag) => {
        const el = new FakeAudio()
        el.tagName = tag.toUpperCase()
        return el
    }
}
const makeHost = () => {
    const host = {
        children: [],
        appendChild(node) {
            node.parentNode = host
            host.children.push(node)
        },
        removeChild(node) {
            host.children = host.children.filter((n) => n !== node)
            node.parentNode = null
        },
    }
    return host
}

let playerMixin

beforeEach(async () => {
    vi.useFakeTimers()
    localStorage.clear()
    FakeAudio.instances = []
    global.Audio = FakeAudio
    global.BroadcastChannel = undefined
    global.fetch = okFetch()
    navigator.sendBeacon = vi.fn(() => true)
    window.dispatchEvent = dispatchSpy
    dispatchSpy.mockImplementation(realDispatch)
    dispatchSpy.mockClear()
    document.querySelector = () => ({ content: 'tok' })
    vi.resetModules()
    ;({ default: playerMixin } = await import('../../mixins/player.js'))
})

afterEach(() => {
    vi.useRealTimers()
    window.dispatchEvent = realDispatch
    document.createElement = realCreateElement
    document.querySelector = realQuerySelector
    global.Audio = realAudio
    delete global.fetch
    delete navigator.sendBeacon
    Object.defineProperty(document, 'hidden', { configurable: true, get: () => false })
})

const makePlayer = () => {
    const p = playerMixin()
    p.progressUrl = '/media/progress'
    p.loadLibrary(library())
    return p
}

const sharedElement = () => FakeAudio.instances[FakeAudio.instances.length - 1]

describe('playerMixin — library, item and episode lookup', () => {
    test('loadLibrary stores the payload, flags ready and drains the pending queue', async () => {
        const p = playerMixin()
        p.progressUrl = '/media/progress'
        p._queuePending([{ type: 'media', id: 99, position: 10, last_position: 10, completed: false, duration: null }])
        p.loadLibrary(library())
        expect(p.ready).toBe(true)
        await vi.waitFor(() => {
            expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(0)
        })
    })

    test('item()/episode() resolve by id and by cur defaults', () => {
        const p = makePlayer()
        p.cur = { sid: 1, idx: 1 }
        expect(p.item().title).toBe('سری آزمون')
        expect(p.episode().id).toBe(12)
        expect(p.episode(2, 0).id).toBe(21)
        expect(p.episode(99, 0)).toBeNull()
    })
})

describe('playerMixin — episodeState / progressOf / resumeAt', () => {
    test('episodeState returns locked/done/current/new per contract', () => {
        const p = makePlayer()
        p.cur = { sid: 1, idx: 0 }
        p.library.progress['11'].completed = true
        expect(p.episodeState(1, 0)).toBe('done')
        p.library.progress['11'].completed = false
        expect(p.episodeState(1, 0)).toBe('current')
        expect(p.episodeState(1, 1)).toBe('new')
        expect(p.episodeState(1, 2)).toBe('locked')
    })

    test('progressOf sums episode positions over total duration and updates after flush', () => {
        const p = makePlayer()
        p.library.progress['12'] = { position: 1000, last_position: 1000, completed: false }
        expect(p.progressOf(1)).toBeCloseTo(1400 / 3500)
        expect(p.progressOf(2)).toBe(0)

        p.playEpisode(1, 1)
        sharedElement()._trigger('playing')
        p.t = 1500
        p.flush('pause')
        expect(p.library.progress['12'].position).toBe(1500)
    })

    test('resumeAt prefers server last_position when local is lower', () => {
        const p = makePlayer()
        p._saveState('media_progress', { '11': { position: 100, updatedAt: Date.now() } })
        expect(p.resumeAt(1, 0)).toBe(350)
    })

    test('resumeAt ignores sub-5s positions (fresh start)', () => {
        const p = makePlayer()
        expect(p.resumeAt(1, 1)).toBe(0)
    })

    test('resumeAt takes the higher local position when server last_position is lower', () => {
        const p = makePlayer()
        vi.advanceTimersByTime(10)
        p._saveState('media_progress', { '11': { position: 900, updatedAt: Date.now() } })
        expect(p.resumeAt(1, 0)).toBe(900)
    })

    test('resumeAt resumes from a newer lower local stop while the furthest stays', () => {
        const p = makePlayer()
        vi.advanceTimersByTime(10)
        p._saveState('media_progress', { '11': { position: 900, last_position: 120, updatedAt: Date.now() } })
        expect(p.resumeAt(1, 0)).toBe(120)
        expect(p.library.progress['11'].position).toBe(400)
    })

    test('resumeAt prefers an older offline local stop over a stale server row only when the server updated_at is older', () => {
        const p = makePlayer()
        vi.advanceTimersByTime(10)
        const local = Date.now() - 86400000
        p.library.progress['11'] = { position: 400, last_position: 350, completed: false, updated_at: Math.floor((local - 3600000) / 1000) }
        p._saveState('media_progress', { '11': { position: 400, last_position: 120, updatedAt: local } })
        expect(p.resumeAt(1, 0)).toBe(120)
        p.library.progress['11'].updated_at = Math.floor((local + 3600000) / 1000)
        expect(p.resumeAt(1, 0)).toBe(350)
    })

    test('resumeAt clamps the stop to the episode duration', () => {
        const p = makePlayer()
        vi.advanceTimersByTime(10)
        p._saveState('media_progress', { '11': { position: 900, last_position: 5000, updatedAt: Date.now() } })
        expect(p.resumeAt(1, 0)).toBe(1000)
    })
})

describe('playerMixin — playEpisode', () => {
    test('plays an unlocked episode on the shared audio element and emits state', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const el = sharedElement()
        expect(el.src).toBe('/storage/media/files/a1.mp3')
        expect(p.cur).toEqual({ sid: 1, idx: 0 })
        el._trigger('playing')
        expect(p.playing).toBe(true)
    })

    test('a locked episode never starts playback', () => {
        const p = makePlayer()
        p.playEpisode(1, 2)
        expect(p.cur.sid).toBeNull()
        expect(FakeAudio.instances).toHaveLength(0)
    })

    test('toggling the same playing episode pauses instead of restarting', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        expect(p.playing).toBe(true)
        p.playEpisode(1, 0)
        expect(sharedElement().paused).toBe(true)
    })

    test('switching episodes flushes the previous item and cancels the pending resume seek', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 120
        p.playEpisode(1, 1)
        const el = sharedElement()
        expect(el.src).toBe('/storage/media/files/a2.mp3')
        expect(p.t).toBe(0)
        el._trigger('loadedmetadata')
        expect(el.currentTime).toBe(0)
    })

    test('video kind creates a video element through document.createElement', () => {
        const p = makePlayer()
        videoFactory()
        p.playEpisode(2, 0)
        expect(sharedElement().tagName).toBe('VIDEO')
    })

    test('mountVideo appends the shared video element to the host', () => {
        const p = makePlayer()
        videoFactory()
        p.playEpisode(2, 0)
        const host = makeHost()
        p.mountVideo(host)
        expect(host.children).toContain(sharedElement())
    })

    test('a video still parented to a removed host moves into the new host and the old host is unregistered', () => {
        const p = makePlayer()
        videoFactory()
        p.playEpisode(2, 0)
        const oldHost = makeHost()
        p.mountVideo(oldHost)
        const next = playerMixin()
        next.progressUrl = '/media/progress'
        next.loadLibrary(library())
        const newHost = makeHost()
        next.mountVideo(newHost)
        expect(newHost.children).toContain(sharedElement())
        expect(sharedElement().parentNode).toBe(newHost)
        next.destroyPlayer()
        expect(next._host).toBeNull()
    })

    test('switching from audio to video re-binds a new video element and flushes the audio', async () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const audio = sharedElement()
        audio._trigger('playing')
        p.t = 120
        videoFactory()
        p.playEpisode(2, 0)
        const video = sharedElement()
        expect(video).not.toBe(audio)
        expect(video.tagName).toBe('VIDEO')
        expect(audio.paused).toBe(true)
        video._trigger('playing')
        expect(p.playing).toBe(true)
        await settle()
        expect(p._synced['11']).toEqual({ position: 400, last_position: 120 })
    })
})

describe('playerMixin — element kind follows the episode mime', () => {
    test('a video episode inside an audio series plays in a video element and flags the stage', () => {
        const p = makePlayer()
        videoFactory()
        p.library.items[0].episodes[1].mime = 'video/mp4'
        p.playEpisode(1, 1)
        expect(sharedElement().tagName).toBe('VIDEO')
        expect(p.isVideo).toBe(true)
    })

    test('an audio episode inside a video-kind item plays in an audio element', () => {
        const p = makePlayer()
        videoFactory()
        p.library.items[1].episodes[0].mime = 'audio/mpeg'
        p.playEpisode(2, 0)
        expect(sharedElement().tagName).toBeUndefined()
        expect(p.isVideo).toBe(false)
    })

    test('a null mime falls back to the item kind', () => {
        const p = makePlayer()
        videoFactory()
        p.library.items[1].episodes[0].mime = null
        p.playEpisode(2, 0)
        expect(sharedElement().tagName).toBe('VIDEO')
    })
})

describe('playerMixin — seekFromEvent', () => {
    const track = (direction) => {
        const el = realCreateElement('div')
        el.getBoundingClientRect = () => ({ left: 100, width: 200 })
        el.style.direction = direction
        document.body.appendChild(el)
        return el
    }

    test('LTR click maps the x offset to the time ratio', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        p.duration = 1000
        const el = track('ltr')
        p.seekFromEvent({ clientX: 150 }, el)
        expect(p.t).toBe(250)
        el.remove()
    })

    test('RTL click mirrors the ratio', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        p.duration = 1000
        const el = track('rtl')
        p.seekFromEvent({ clientX: 150 }, el)
        expect(p.t).toBe(750)
        el.remove()
    })

    test('dragging follows pointermove until pointerup and then stops seeking', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        p.duration = 1000
        const el = track('ltr')
        p.seekFromEvent({ clientX: 100 }, el)
        expect(p.seeking).toBe(true)
        window.dispatchEvent(Object.assign(new Event('pointermove'), { clientX: 300 }))
        expect(p.t).toBe(1000)
        window.dispatchEvent(new Event('pointerup'))
        expect(p.seeking).toBe(false)
        window.dispatchEvent(Object.assign(new Event('pointermove'), { clientX: 100 }))
        expect(p.t).toBe(1000)
        el.remove()
    })
})

describe('playerMixin — seek/skip/next/prev navigation', () => {
    test('seek clamps within [0, duration]', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        p.duration = 200
        p.seek(500)
        expect(p.t).toBe(200)
        p.seek(-10)
        expect(p.t).toBe(0)
        p.seek(150)
        expect(p.t).toBe(150)
    })

    test('skip adds the delta (RTL: ArrowLeft = +10s)', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        p.duration = 500
        p.t = 100
        p.skip(10)
        expect(p.t).toBe(110)
        p.skip(-10)
        expect(p.t).toBe(100)
    })

    test('next skips locked episodes and stops at the end', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        sharedElement()._trigger('playing')
        p.next(true)
        expect(p.cur.idx).toBe(0)
        p.cur = { sid: 1, idx: 1 }
        p.library.items[0].episodes[0].unlock_at = 1893456000
        p.next(true)
        expect(p.playing).toBe(false)
    })

    test('prev restarts when past 5s, otherwise goes to the previous playable episode', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        p.duration = 200
        p.t = 40
        p.prev()
        expect(p.t).toBe(0)
        p.prev()
        expect(p.cur.idx).toBe(0)
    })
})

describe('playerMixin — speed, volume, mute', () => {
    test('setSpeed cycles the house speed ladder and applies playbackRate', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        expect(p.speed).toBe(1)
        p.setSpeed()
        expect(p.speed).toBe(1.25)
        expect(sharedElement().playbackRate).toBe(1.25)
        p.setSpeed(2)
        expect(p.speed).toBe(2)
    })

    test('setVolume clamps and unmutes', () => {
        const p = makePlayer()
        p.muted = true
        p.setVolume(2)
        expect(p.vol).toBe(1)
        expect(p.muted).toBe(false)
        p.setVolume(-1)
        expect(p.vol).toBe(0)
    })

    test('toggleMute mirrors on the element', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        p.toggleMute()
        expect(p.muted).toBe(true)
        expect(sharedElement().muted).toBe(true)
    })

    test('fa() renders Persian digits for the speed label', () => {
        const p = makePlayer()
        expect(p.fa(1)).toBe('۱')
        expect(p.fa(1.5)).toBe('۱.۵')
        expect(p.faTime(125)).toBe('۲:۰۵')
    })
})

describe('playerMixin — local-first progress and flush', () => {
    test('timeupdate persists the local {position, updatedAt} per episode id', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const el = sharedElement()
        el.currentTime = 40
        el._trigger('timeupdate')
        el.currentTime = 60
        el._trigger('timeupdate')
        const stored = JSON.parse(localStorage.getItem('media_progress'))
        expect(stored['11'].position).toBe(60)
        expect(stored['11'].updatedAt).toBeGreaterThan(0)
    })

    test('a pause flush goes through fetch and acks via _synced only after the response', async () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 300
        p.flush('pause')
        expect(navigator.sendBeacon).not.toHaveBeenCalled()
        expect(p._synced['11']).toBeUndefined()
        await settle()
        expect(p._synced['11']).toEqual({ position: 400, last_position: 300 })
        expect(JSON.parse(localStorage.getItem('media_progress_pending') ?? '[]')).toHaveLength(0)
    })

    test('a second pause flush with no change is a no-op (deduped against server-ack)', async () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 300
        p.flush('pause')
        await settle()
        global.fetch.mockClear()
        p.flush('pause')
        expect(global.fetch).not.toHaveBeenCalled()
    })

    test('safety flush respects min_delta against the acked position', async () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 300
        p.flush('pause')
        await settle()
        global.fetch.mockClear()
        p.t = 305
        p.flush('safety')
        expect(global.fetch).not.toHaveBeenCalled()
        p.t = 330
        p.flush('safety')
        expect(global.fetch).toHaveBeenCalled()
    })

    test('flush clamps position to duration + tolerance and marks completed at 95%', async () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        sharedElement()._trigger('playing')
        p.duration = 1000
        p.t = 999
        p.flush('pause')
        await settle()
        expect(p._synced['12'].position).toBe(999)
        p.library.progress['12'].position = 0
        p.library.progress['12'].completed = false
        p.t = 2000
        p.duration = 1000
        p.flush('pagehide')
        const queued = JSON.parse(localStorage.getItem('media_progress_pending'))
        expect(queued.find((i) => i.id === 12).position).toBeLessThanOrEqual(1005)
    })

    test('ended flushes completion so library.progress and the server update without a pause event', async () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const el = sharedElement()
        el._trigger('playing')
        el.duration = 1000
        el.currentTime = 1000
        el._trigger('ended')
        expect(p.library.progress['11'].completed).toBe(true)
        expect(p.library.progress['11'].position).toBe(1000)
        await settle()
        expect(JSON.parse(global.fetch.mock.calls[0][1].body).items[0]).toMatchObject({ id: 11, completed: true })
    })

    test('adoption keeps playing across kinds: a new component takes over a playing video', () => {
        const p = makePlayer()
        videoFactory()
        p.playEpisode(2, 0)
        sharedElement()._trigger('playing')
        const next = playerMixin()
        next.progressUrl = '/media/progress'
        next.loadLibrary(library())
        expect(next.cur).toEqual({ sid: 2, idx: 0 })
        expect(next.playing).toBe(true)
    })

    test('a newer LOWER stop is stored while the furthest stays monotonic', () => {
        const p = makePlayer()
        p._storeLocal(11, 500, 500)
        vi.advanceTimersByTime(10)
        p._storeLocal(11, 100, 100)
        const stored = JSON.parse(localStorage.getItem('media_progress'))['11']
        expect(stored.position).toBe(500)
        expect(stored.last_position).toBe(100)
    })

    test('completed is flagged at >= 95% of the episode duration', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        sharedElement()._trigger('playing')
        p.duration = 2000
        p.t = 1950
        p.flush('pause')
        expect(p.library.progress['12'].completed).toBe(true)
    })

    test('flush is a no-op for a never-played item (no element src or t=0)', () => {
        const p = makePlayer()
        const send = vi.spyOn(navigator, 'sendBeacon').mockReturnValue(true)
        p.selectItem(1)
        p.flush('pagehide')
        expect(send).not.toHaveBeenCalled()
        p.t = 0
        p.flush('pagehide')
        expect(send).not.toHaveBeenCalled()
        send.mockRestore()
    })

    test('local store keeps the higher position (no regression on rewind)', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        sharedElement()._trigger('playing')
        p.t = 500
        p.flush('pause')
        p.t = 100
        p._synced = {}
        p.flush('pause')
        const stored = JSON.parse(localStorage.getItem('media_progress'))
        expect(stored['12'].position).toBe(500)
        expect(p.library.progress['12'].position).toBe(500)
    })
})

describe('playerMixin — pending queue, CSRF and HTTP statuses', () => {
    test('_csrf is read from meta[name=csrf-token] at send time', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 30
        p.flush('pause')
        expect(JSON.parse(global.fetch.mock.calls[0][1].body)._token).toBe('tok')
    })

    test('pagehide uses the beacon and keeps the entry pending until an acknowledged fetch drains it', async () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 30
        p.flush('pagehide')
        expect(navigator.sendBeacon).toHaveBeenCalled()
        expect(p._synced['11']).toBeUndefined()
        expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(1)
        p.loadLibrary(library())
        await vi.waitFor(() => {
            expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(0)
        })
    })

    test('an acknowledged fetch also removes older pending revisions of the same episode', async () => {
        const p = makePlayer()
        p._queuePending([{ type: 'media', id: 11, position: 50, last_position: 50, completed: false, duration: null }])
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 30
        p.flush('pause')
        await settle()
        expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(0)
    })

    test('events from a replaced element are ignored', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const audio = sharedElement()
        audio._trigger('playing')
        videoFactory()
        p.playEpisode(2, 0)
        sharedElement()._trigger('playing')
        audio.currentTime = 300
        audio._trigger('timeupdate')
        expect(p.t).toBe(0)
    })

    test('a beacon returning false is treated as failure and falls back to fetch', async () => {
        const p = makePlayer()
        navigator.sendBeacon = vi.fn(() => false)
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 30
        p.flush('pagehide')
        expect(global.fetch).toHaveBeenCalled()
        await settle()
        expect(p._synced['11']).toBeDefined()
    })

    test('a beacon false-return with a failing fetch queues the entry', async () => {
        const p = makePlayer()
        navigator.sendBeacon = vi.fn(() => false)
        global.fetch = vi.fn().mockRejectedValue(new Error('offline'))
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 30
        p.flush('pagehide')
        await settle()
        expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(1)
        expect(p._synced['11']).toBeUndefined()
    })

    test('a 419/401 response queues the items back into the pending queue', async () => {
        const p = makePlayer()
        global.fetch = vi.fn().mockResolvedValue({ ok: false, status: 419 })
        p._sendProgress('/media/progress', { items: [{ id: 11, position: 30, last_position: 30 }] })
        await vi.waitFor(() => {
            expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(1)
        })
    })

    test('a 422 poison item is dropped from the queue instead of requeued forever', async () => {
        const p = makePlayer()
        const poison = { type: 'media', id: 77, position: 1, last_position: 1, completed: false, duration: null }
        p._saveState('media_progress_pending', [poison])
        global.fetch = vi.fn().mockResolvedValue({ ok: false, status: 422 })
        p.loadLibrary(library())
        await vi.waitFor(() => {
            expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(0)
        })
        expect(global.fetch).toHaveBeenCalledTimes(1)
    })

    test('drain removes only acknowledged revisions and keeps entries added meanwhile', async () => {
        const p = makePlayer()
        const a = { type: 'media', id: 1, position: 10, last_position: 10, completed: false, duration: null }
        const late = { type: 'media', id: 2, position: 20, last_position: 20, completed: false, duration: null }
        p._saveState('media_progress_pending', [a])
        let release
        global.fetch = vi.fn().mockImplementation(() => new Promise((resolve) => { release = () => resolve({ ok: true, status: 204 }) }))
        p.loadLibrary(library())
        p._queuePending([late])
        release()
        await settle()
        expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toEqual([late])
    })

    test('a newer revision of the same episode queued during the drain survives the ack of the old one', async () => {
        const p = makePlayer()
        const old = { type: 'media', id: 1, position: 10, last_position: 10, completed: false, duration: null }
        const fresh = { ...old, position: 50, last_position: 50 }
        p._saveState('media_progress_pending', [old])
        let release
        global.fetch = vi.fn().mockImplementation(() => new Promise((resolve) => { release = () => resolve({ ok: true, status: 204 }) }))
        p.loadLibrary(library())
        p._queuePending([fresh])
        release()
        await settle()
        expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toEqual([fresh])
    })

    test('pending queue coalesces by episode id and drains in 50-item chunks', async () => {
        const p = makePlayer()
        const chunks = []
        global.fetch = vi.fn().mockImplementation((url, opts) => {
            chunks.push(JSON.parse(opts.body).items.length)
            return Promise.resolve({ ok: true, status: 204 })
        })
        const items = []
        for (let i = 1; i <= 120; i++) {
            items.push({ type: 'media', id: i, position: i, last_position: i, completed: false, duration: null })
        }
        p._saveState('media_progress_pending', items)
        p.progressUrl = '/media/progress'
        p.loadLibrary(library())
        await vi.waitFor(() => {
            expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(0)
        })
        expect(chunks).toEqual([50, 50, 20])
    })

    test('a failed drain puts every chunk back into the queue for the next load', async () => {
        const p = makePlayer()
        global.fetch = vi.fn().mockRejectedValue(new Error('offline'))
        const items = []
        for (let i = 1; i <= 60; i++) {
            items.push({ type: 'media', id: i, position: i, last_position: i, completed: false, duration: null })
        }
        p._saveState('media_progress_pending', items)
        p.progressUrl = '/media/progress'
        p.loadLibrary(library())
        await vi.waitFor(() => {
            expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(60)
        })
    })
})

describe('playerMixin — audio-pause-request coordination and radio/ambient exclusion', () => {
    test('playEpisode self-origin guard: its own pause-request does not pause the new episode', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        expect(sharedElement().paused).toBe(false)
        p.playEpisode(1, 1)
        expect(sharedElement().paused).toBe(false)
    })

    test('an incoming audio-pause-request pauses the shared element', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        window.dispatchEvent(new CustomEvent('audio-pause-request'))
        expect(sharedElement().paused).toBe(true)
    })

    test('ambient audio-play-state true pauses media (listening only, never emitting)', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        window.dispatchEvent(new CustomEvent('audio-play-state', { detail: true }))
        expect(sharedElement().paused).toBe(true)
    })

    test('the real radio.js media:state listener pauses a playing radio and is removed on destroy', () => {
        const r = radio('r')
        r.$watch = vi.fn()
        r.init()
        const audio = new FakeAudio()
        r.playing = true
        r.audio = audio
        window.dispatchEvent(new CustomEvent('media:state', { detail: { playing: true } }))
        expect(audio.paused).toBe(true)
        audio.paused = false
        r.destroy()
        window.dispatchEvent(new CustomEvent('media:state', { detail: { playing: true } }))
        expect(audio.paused).toBe(false)
    })

    test('a stale native pause event from the previous episode does not stop or flush the new one', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const el = sharedElement()
        el._trigger('playing')
        p.t = 200
        p.playEpisode(1, 1)
        el._trigger('playing')
        global.fetch.mockClear()
        el._trigger('pause')
        expect(p.playing).toBe(true)
        expect(p.cur).toEqual({ sid: 1, idx: 1 })
        expect(global.fetch).not.toHaveBeenCalled()
    })

    test('a real pause event while the element is paused stops and flushes', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const el = sharedElement()
        el._trigger('playing')
        p.t = 200
        el.pause()
        el._trigger('pause')
        expect(p.playing).toBe(false)
        expect(global.fetch).toHaveBeenCalled()
    })

    test('switching saves the outgoing episode before the new one starts', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 220
        p.playEpisode(1, 1)
        expect(JSON.parse(global.fetch.mock.calls[0][1].body).items[0]).toMatchObject({ id: 11, last_position: 220 })
    })

    test('media:state carries playing/sid/idx/t/duration; audio-play-state is never emitted', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        const types = dispatchSpy.mock.calls.map((args) => args[0]?.type ?? args[0])
        expect(types).toContain('media:state')
        expect(types).not.toContain('audio-play-state')
    })
})

describe('playerMixin — prebuffer gating', () => {
    test('prebuffers the next episode only under the threshold and without saveData', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.duration = 500
        p.t = 100
        p.maybePrebuffer()
        expect(FakeAudio.instances.some((el) => el.src === '/storage/media/files/a2.mp3' && el !== sharedElement())).toBe(false)
        p.t = 400
        p.maybePrebuffer()
        expect(FakeAudio.instances.some((el) => el.src === '/storage/media/files/a2.mp3' && el !== sharedElement())).toBe(true)
    })

    test('saveData skips prebuffering entirely', () => {
        const p = makePlayer()
        navigator.connection = { saveData: true }
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.duration = 500
        p.t = 450
        p.maybePrebuffer()
        expect(FakeAudio.instances.some((el) => el !== sharedElement())).toBe(false)
        delete navigator.connection
    })

    test('prebuffer is released (removeAttribute + load) on the next playEpisode', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.duration = 500
        p.t = 450
        p.maybePrebuffer()
        const prebuffer = FakeAudio.instances.at(-1)
        prebuffer.removeAttribute = vi.fn()
        p.playEpisode(1, 1)
        expect(prebuffer.removeAttribute).toHaveBeenCalledWith('src')
    })
})

describe('playerMixin — destroyPlayer and key handling', () => {
    test('destroyPlayer flushes, removes the keydown listener and clears shared ui', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.bindPlayerKeys('#x')
        p.t = 150
        const remove = vi.spyOn(document, 'removeEventListener')
        p.destroyPlayer()
        expect(remove).toHaveBeenCalledWith('keydown', expect.any(Function))
        remove.mockRestore()
    })

    test('audio keeps playing across destroy (survives wire:navigate) and a new component re-adopts it', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 50
        p.destroyPlayer()
        expect(sharedElement().paused).toBe(false)
        const next = playerMixin()
        next.progressUrl = '/media/progress'
        next.loadLibrary(library())
        expect(next.cur).toEqual({ sid: 1, idx: 0 })
        expect(next.playing).toBe(true)
    })

    test('video pauses and releases the shared ui on destroy', () => {
        const p = makePlayer()
        document.createElement = () => new FakeAudio()
        p.playEpisode(2, 0)
        sharedElement()._trigger('playing')
        p.destroyPlayer()
        expect(sharedElement().paused).toBe(true)
    })
})

describe('playerMixin — sync triggers', () => {
    test('visibilitychange hidden while playing flushes', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 200
        Object.defineProperty(document, 'hidden', { configurable: true, get: () => true })
        document.dispatchEvent(new Event('visibilitychange'))
        expect(global.fetch).toHaveBeenCalled()
    })

    test('livewire:navigating pauses VIDEO but keeps audio playing', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        document.dispatchEvent(new Event('livewire:navigating'))
        expect(FakeAudio.instances.at(-1).paused).toBe(false)
    })
})

describe('playerMixin — per-user scoping, safety timer, rewind, last lesson, live progress', () => {
    const forUser = (id) => {
        const p = playerMixin()
        p.userId = id
        p.progressUrl = '/media/progress'
        p.loadLibrary(library())
        return p
    }
    const queued = { type: 'media', id: 11, position: 300, last_position: 300, completed: false, duration: null }

    test('two users in one browser never see each other\'s progress or queue', () => {
        const a = forUser('1')
        const b = forUser('2')
        a._storeLocal(11, 300, 300)
        a._queuePending([queued])
        expect(b._loadState(b._k('media_progress'), {})).toEqual({})
        expect(b._loadState(b._k('media_progress_pending'), [])).toEqual([])
        expect(localStorage.getItem('media_progress:1')).not.toBeNull()
        expect(localStorage.getItem('media_progress')).toBeNull()
        expect(localStorage.getItem('media_progress_pending')).toBeNull()
    })

    test('adoption across a user change is refused and the previous playback is released', () => {
        const a = forUser('1')
        a.playEpisode(1, 0)
        const el = sharedElement()
        el._trigger('playing')
        const b = forUser('2')
        expect(el.paused).toBe(true)
        expect(b.cur.sid).toBeNull()
        expect(b.playing).toBe(false)
        expect(vi.getTimerCount()).toBe(0)
        const c = forUser('2')
        expect(c.cur.sid).toBeNull()
    })

    test('the same user still adopts the playing audio', () => {
        const a = forUser('1')
        a.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        const b = forUser('1')
        expect(b.cur).toEqual({ sid: 1, idx: 0 })
        expect(b.playing).toBe(true)
    })

    test('the safety timer survives a destroy for audio and adoption never double-starts it', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        expect(vi.getTimerCount()).toBe(1)
        p.destroyPlayer()
        expect(vi.getTimerCount()).toBe(1)
        const next = makePlayer()
        expect(next.playing).toBe(true)
        expect(vi.getTimerCount()).toBe(1)
    })

    test('adopting a playing element restarts a stopped safety timer', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const el = sharedElement()
        el._trigger('playing')
        p.destroyPlayer()
        el.pause()
        el._trigger('pause')
        expect(vi.getTimerCount()).toBe(0)
        el.play()
        makePlayer()
        expect(vi.getTimerCount()).toBe(1)
    })

    test('the safety timer stops when a video is destroyed', () => {
        const p = makePlayer()
        videoFactory()
        p.playEpisode(2, 0)
        sharedElement()._trigger('playing')
        expect(vi.getTimerCount()).toBe(1)
        p.destroyPlayer()
        expect(vi.getTimerCount()).toBe(0)
    })

    test('a deliberate seek back to 0 after real progress is saved as the stop', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const el = sharedElement()
        el._trigger('playing')
        el.currentTime = 300
        el._trigger('timeupdate')
        p.seek(0)
        el._trigger('timeupdate')
        const stored = JSON.parse(localStorage.getItem('media_progress'))['11']
        expect(stored).toMatchObject({ position: 300, last_position: 0 })
        p.flush('pagehide')
        const entry = JSON.parse(localStorage.getItem('media_progress_pending')).find((i) => i.id === 11)
        expect(entry).toMatchObject({ position: 400, last_position: 0 })
    })

    test('a never-played selection still sends nothing at t=0', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        p.flush('pagehide')
        expect(navigator.sendBeacon).not.toHaveBeenCalled()
        expect(global.fetch).not.toHaveBeenCalled()
    })

    test('a manual next on the last lesson leaves playback untouched and toasts', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        sharedElement()._trigger('playing')
        const toast = vi.fn()
        p.toast = toast
        p.next()
        expect(toast).toHaveBeenCalledWith('آخرین جلسه است')
        expect(p.playing).toBe(true)
        expect(sharedElement().paused).toBe(false)
    })

    test('the auto-advance after ended keeps the stopped state without a toast', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        sharedElement()._trigger('playing')
        const toast = vi.fn()
        p.toast = toast
        p.next(true)
        expect(p.playing).toBe(false)
        expect(toast).not.toHaveBeenCalled()
    })

    test('library.progress follows playback at the local save step without a request', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const el = sharedElement()
        el._trigger('playing')
        el.currentTime = 700
        el._trigger('timeupdate')
        expect(p.library.progress['11']).toMatchObject({ position: 700, last_position: 700 })
        expect(p.progressOf(1)).toBeCloseTo(0.2)
        expect(global.fetch).not.toHaveBeenCalled()
    })
})

describe('playerMixin — mark a lesson done', () => {
    test('records completion at the duration locally and queues the normal progress item', async () => {
        const p = makePlayer()
        expect(p.canMarkDone(1, 1)).toBe(true)
        p.markDone(1, 1)
        expect(p.library.progress['12']).toMatchObject({ position: 2000, last_position: 2000, completed: true })
        expect(p.episodeState(1, 1)).toBe('done')
        expect(JSON.parse(localStorage.getItem('media_progress'))['12']).toMatchObject({ position: 2000, last_position: 2000 })
        await settle()
        const body = JSON.parse(global.fetch.mock.calls[0][1].body)
        expect(body.items).toEqual([{ type: 'media', id: 12, position: 2000, last_position: 2000, completed: true, duration: 2000 }])
        expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(0)
    })

    test('is refused for locked, finished or duration-less lessons and queues on a failed request', async () => {
        const p = makePlayer()
        expect(p.canMarkDone(1, 2)).toBe(false)
        p.markDone(1, 2)
        p.markDone(1, 1)
        expect(p.canMarkDone(1, 1)).toBe(false)
        const calls = global.fetch.mock.calls.length
        p.markDone(1, 1)
        expect(global.fetch.mock.calls.length).toBe(calls)
        p.library.items[0].episodes[0].duration = 0
        expect(p.canMarkDone(1, 0)).toBe(false)
        const failing = makePlayer()
        global.fetch = vi.fn().mockResolvedValue({ ok: false, status: 500 })
        failing.markDone(1, 0)
        await settle()
        expect(JSON.parse(localStorage.getItem('media_progress_pending')).some((i) => i.id === 11)).toBe(true)
    })
})
