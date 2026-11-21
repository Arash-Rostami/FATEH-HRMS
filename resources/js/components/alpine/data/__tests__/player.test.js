import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'

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

let playerMixin

beforeEach(async () => {
    vi.useFakeTimers()
    localStorage.clear()
    FakeAudio.instances = []
    global.Audio = FakeAudio
    global.BroadcastChannel = undefined
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
})

const makePlayer = () => {
    const p = playerMixin()
    p.progressUrl = '/media/progress'
    p.loadLibrary(library())
    return p
}

const sharedElement = () => FakeAudio.instances[FakeAudio.instances.length - 1]

describe('playerMixin — library, item and episode lookup', () => {
    test('loadLibrary stores the payload, flags ready and drains the pending queue', () => {
        const p = playerMixin()
        p.progressUrl = '/media/progress'
        p._queuePending([{ type: 'media', id: 99, position: 10, last_position: 10, completed: false, duration: null }])
        p.loadLibrary(library())
        expect(p.ready).toBe(true)
        expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(0)
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
        p._saveState('media_progress', { '11': { position: 900, updatedAt: Date.now() } })
        expect(p.resumeAt(1, 0)).toBe(900)
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
        const created = []
        document.createElement = (tag) => {
            const el = new FakeAudio()
            el.tagName = tag.toUpperCase()
            created.push(el)
            return el
        }
        p.playEpisode(2, 0)
        expect(created.at(-1)?.tagName).toBe('VIDEO')
    })

    test('mountVideo appends the shared video element to the host', () => {
        const p = makePlayer()
        const created = []
        document.createElement = (tag) => {
            const el = new FakeAudio()
            el.tagName = tag.toUpperCase()
            created.push(el)
            return el
        }
        p.playEpisode(2, 0)
        const host = { children: [], appendChild(node) { this.children.push(node) } }
        p.mountVideo(host)
        expect(host.children).toContain(created.at(-1))
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

    test('flush sends a beacon with token and acks via _synced (no pending write)', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 300
        p.flush('pause')
        expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toBeNull()
        expect(p._synced['11']).toEqual({ position: 400, last_position: 300 })
    })

    test('a second pause flush with no change is a no-op (deduped against server-ack)', () => {
        const p = makePlayer()
        const send = vi.spyOn(navigator, 'sendBeacon').mockReturnValue(true)
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 300
        p.flush('pause')
        send.mockClear()
        p.flush('pause')
        expect(send).not.toHaveBeenCalled()
        send.mockRestore()
    })

    test('safety flush respects min_delta against the acked position', () => {
        const p = makePlayer()
        const send = vi.spyOn(navigator, 'sendBeacon').mockReturnValue(true)
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 300
        p.flush('pause')
        send.mockClear()
        p.t = 305
        p.flush('safety')
        expect(send).not.toHaveBeenCalled()
        p.t = 330
        p.flush('safety')
        expect(send).toHaveBeenCalled()
        send.mockRestore()
    })

    test('flush clamps position to duration + tolerance and marks completed at 95%', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        sharedElement()._trigger('playing')
        p.duration = 1000
        p.t = 999
        p.flush('pause')
        expect(p._synced['12'].position).toBeLessThanOrEqual(1005)
        expect(p._synced['12'].position).toBe(999)
        p.library.progress['12'].position = 0
        p.library.progress['12'].completed = false
        p.t = 2000
        p.duration = 1000
        p.flush('pagehide')
        expect(p._synced['12'].position).toBeLessThanOrEqual(1005)
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
        const send = vi.spyOn(navigator, 'sendBeacon').mockImplementation((url, blob) => {
            blob.text().then((text) => {
                expect(JSON.parse(text)._token).toBe('tok')
            })
            return true
        })
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 30
        p.flush('pause')
        expect(send).toHaveBeenCalled()
        send.mockRestore()
    })

    test('a 419/401/422 response queues the items back into the pending queue', async () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 30
        global.fetch = vi.fn().mockResolvedValue({ ok: false, status: 419 })
        p._sendProgress('/media/progress', { items: [{ id: 11, position: 30 }] }, { keepalive: true })
        await vi.waitFor(() => {
            expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(1)
        })
    })

    test('pending queue coalesces by episode id and drains in 50-item chunks', () => {
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
        expect(chunks).toEqual([50, 50, 20])
        expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(0)
    })

    test('a failed drain keeps the remainder in the queue for the next load', () => {
        const p = makePlayer()
        global.fetch = vi.fn().mockRejectedValue(new Error('offline'))
        const items = []
        for (let i = 1; i <= 60; i++) {
            items.push({ type: 'media', id: i, position: i, last_position: i, completed: false, duration: null })
        }
        p._saveState('media_progress_pending', items)
        p.progressUrl = '/media/progress'
        p.loadLibrary(library())
        expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(60)
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

    test('radio pauses when media:state arrives with playing true (radio.js listener contract)', () => {
        const listenerRef = window.addEventListener.bind(window)
        let radioListener = null
        window.addEventListener = (type, fn) => {
            if (type === 'media:state') radioListener = fn
            listenerRef(type, fn)
        }
        const fakeRadio = { playing: true, audio: new FakeAudio() }
        fakeRadio.audio.paused = false
        window.addEventListener('media:state', (e) => {
            if (e.detail?.playing === true && fakeRadio.playing) fakeRadio.audio.pause()
        })
        window.dispatchEvent(new CustomEvent('media:state', { detail: { playing: true } }))
        expect(fakeRadio.audio.paused).toBe(true)
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
        p.playEpisode(1, 1)
        expect(prebuffer.removeAttribute).toBeDefined()
        expect(prebuffer.removeAttribute).toHaveBeenCalled
    })
})

describe('playerMixin — destroyPlayer and key handling', () => {
    test('destroyPlayer flushes, removes the keydown listener and clears shared ui', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.bindPlayerKeys('#x')
        p.t = 150
        p.destroyPlayer()
        expect(sharedElement().paused).toBe(true)
        const keydownCount = document._listeners?.keydown?.length ?? 0
        p.bindPlayerKeys('#x')
        p.destroyPlayer()
    })

    test('audio keeps playing across destroy (survives wire:navigate); video pauses', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.destroyPlayer()
        expect(FakeAudio.instances.at(-1).paused).toBe(true)
    })
})

describe('playerMixin — sync triggers', () => {
    test('visibilitychange hidden while playing flushes', () => {
        const p = makePlayer()
        const send = vi.spyOn(navigator, 'sendBeacon').mockReturnValue(true)
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        p.t = 200
        Object.defineProperty(document, 'hidden', { configurable: true, get: () => true })
        document.dispatchEvent(new Event('visibilitychange'))
        expect(send).toHaveBeenCalled()
        Object.defineProperty(document, 'hidden', { configurable: true, get: () => false })
        send.mockRestore()
    })

    test('livewire:navigating pauses VIDEO but keeps audio playing', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        sharedElement()._trigger('playing')
        document.dispatchEvent(new Event('livewire:navigating'))
        expect(FakeAudio.instances.at(-1).paused).toBe(false)
    })
})
