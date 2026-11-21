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

const dispatchSpy = vi.fn(window.dispatchEvent.bind(window))

let playerMixin

beforeEach(async () => {
    vi.useFakeTimers()
    localStorage.clear()
    FakeAudio.instances = []
    global.Audio = FakeAudio
    global.BroadcastChannel = undefined
    window.dispatchEvent = dispatchSpy
    dispatchSpy.mockClear()
    vi.resetModules()
    ;({ default: playerMixin } = await import('../../mixins/player.js'))
})

afterEach(() => {
    vi.useRealTimers()
    vi.restoreAllMocks()
})

const makePlayer = () => {
    const p = playerMixin()
    p.progressUrl = '/media/progress'
    p._csrf = 'tok'
    p.loadLibrary(library())
    return p
}

const sharedElement = () => FakeAudio.instances[FakeAudio.instances.length - 1]

describe('playerMixin — library, item and episode lookup', () => {
    test('loadLibrary stores the payload and flags ready', () => {
        const p = playerMixin()
        expect(p.ready).toBe(false)
        p.loadLibrary(library())
        expect(p.ready).toBe(true)
        expect(p.library.items).toHaveLength(2)
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

    test('progressOf sums episode positions over total duration', () => {
        const p = makePlayer()
        p.library.progress['12'] = { position: 1000, last_position: 1000, completed: false }
        expect(p.progressOf(1)).toBeCloseTo(1400 / 3500)
        expect(p.progressOf(2)).toBe(0)
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

    test('resumeAt takes local first when it is higher', () => {
        const p = makePlayer()
        p._saveState('media_progress', { '12': { position: 900, updatedAt: Date.now() } })
        expect(p.resumeAt(1, 1)).toBe(900)
    })
})

describe('playerMixin — playEpisode', () => {
    test('plays an unlocked episode on the shared audio element and emits state', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const el = sharedElement()
        expect(el.src).toBe('/storage/media/files/a1.mp3')
        expect(p.cur).toEqual({ sid: 1, idx: 0 })
        p._trigger('playing')
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
        p._trigger('playing')
        expect(p.playing).toBe(true)
        p.playEpisode(1, 0)
        expect(sharedElement().paused).toBe(true)
    })

    test('switching episodes flushes the previous item first', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        p._trigger('playing')
        p.t = 120
        p.playEpisode(1, 1)
        const pending = JSON.parse(localStorage.getItem('media_progress_pending'))
        expect(pending).toHaveLength(1)
        expect(pending[0].id).toBe(11)
    })

    test('video kind creates a video element, audio keeps Audio', () => {
        const p = makePlayer()
        p.playEpisode(2, 0)
        expect(sharedElement().tagName).toBeUndefined()
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
        p._trigger('playing')
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
    test('setSpeed cycles the house speed ladder', () => {
        const p = makePlayer()
        expect(p.speed).toBe(1)
        p.setSpeed()
        expect(p.speed).toBe(1.25)
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
})

describe('playerMixin — local-first progress and flush', () => {
    test('flush sends a beacon entry with token and queues to pending', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        p._trigger('playing')
        p.t = 300
        p.flush('pause')
        const pending = JSON.parse(localStorage.getItem('media_progress_pending'))
        expect(pending[0]).toMatchObject({ type: 'media', id: 11, position: 400, last_position: 300, completed: false })
        const stored = JSON.parse(localStorage.getItem('media_progress'))
        expect(stored['11'].position).toBe(400)
    })

    test('flush clamps position to duration + tolerance and marks completed at 95%', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        p._trigger('playing')
        p.duration = 1000
        p.t = 999
        p.flush('safety')
        const pending = JSON.parse(localStorage.getItem('media_progress_pending'))
        expect(pending[0].completed).toBe(true)
        expect(pending[0].position).toBeLessThanOrEqual(1005)
    })

    test('flush is a no-op when the position did not advance', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        p._trigger('playing')
        p.t = 400
        p.flush('pause')
        localStorage.removeItem('media_progress_pending')
        p.flush('pause')
        expect(localStorage.getItem('media_progress_pending')).toBeNull()
    })

    test('a flush that never regresses: local store keeps the higher position', () => {
        const p = makePlayer()
        p.playEpisode(1, 1)
        p._trigger('playing')
        p.t = 500
        p.flush('pause')
        p.t = 100
        p.flush('pause')
        const stored = JSON.parse(localStorage.getItem('media_progress'))
        expect(stored['12'].position).toBe(500)
    })
})

describe('playerMixin — audio-pause-request coordination', () => {
    test('playing dispatches audio-pause-request before starting a new episode', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        const events = dispatchSpy.mock.calls.map(([e]) => e.type)
        expect(events).toContain('audio-pause-request')
    })

    test('an incoming audio-pause-request pauses the shared element', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        p._trigger('playing')
        expect(sharedElement().paused).toBe(false)
        window.dispatchEvent(new CustomEvent('audio-pause-request'))
        expect(sharedElement().paused).toBe(true)
    })

    test('media:state carries playing/sid/idx/t/duration; audio-play-state is never emitted', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        p._trigger('playing')
        const types = dispatchSpy.mock.calls.map(([e]) => e.type)
        expect(types).toContain('media:state')
        expect(types).not.toContain('audio-play-state')
        const state = dispatchSpy.mock.calls.filter(([e]) => e.type === 'media:state').pop()[0]
        expect(state.detail).toMatchObject({ playing: true, sid: 1, idx: 0 })
    })
})

describe('playerMixin — destroyPlayer', () => {
    test('destroyPlayer flushes, pauses and clears the shared ui', () => {
        const p = makePlayer()
        p.playEpisode(1, 0)
        p._trigger('playing')
        p.t = 150
        p.destroyPlayer()
        expect(sharedElement().paused).toBe(true)
        expect(JSON.parse(localStorage.getItem('media_progress_pending'))).toHaveLength(1)
    })
})
