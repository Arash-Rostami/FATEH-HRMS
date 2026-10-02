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
        this._listeners = {}
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

let audioPlayer

beforeEach(async () => {
    vi.useFakeTimers()
    localStorage.clear()
    FakeAudio.instances = []
    global.Audio = FakeAudio
    global.BroadcastChannel = undefined
    vi.resetModules()
    ;({ default: audioPlayer } = await import('../audioPlayer.js'))
})

afterEach(() => {
    vi.useRealTimers()
})

describe('audioPlayer getters — timeLabel / hoverTimeLabel / progress', () => {
    test('timeLabel formats current/duration as m:ss, without leading zero on minutes', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.currentTime = 75
        a.duration = 185
        expect(a.timeLabel).toBe('1:15 / 3:05')
    })

    test('a zero/undefined duration yields 0:00 labels and a 0 progress (no division by zero)', () => {
        const a = audioPlayer('d.mp3', 'D')
        expect(a.timeLabel).toBe('0:00 / 0:00')
        expect(a.progress).toBe(0)
    })

    test('progress is the currentTime/duration ratio once duration is known', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.currentTime = 50
        a.duration = 200
        expect(a.progress).toBe(0.25)
    })

    test('hoverTimeLabel previews duration * hoverProgress, defaulting hoverProgress to 0', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.duration = 200
        a.hoverProgress = 0.25
        expect(a.hoverTimeLabel).toBe('0:50')
        a.hoverProgress = null
        expect(a.hoverTimeLabel).toBe('0:00')
    })
})

describe('audioPlayer._getFraction', () => {
    test('clamps the pointer position within the given rect to [0,1]', () => {
        const a = audioPlayer('d.mp3', 'D')
        const rect = { left: 0, width: 100 }
        expect(a._getFraction({ clientX: 50 }, rect)).toBe(0.5)
        expect(a._getFraction({ clientX: -20 }, rect)).toBe(0)
        expect(a._getFraction({ clientX: 150 }, rect)).toBe(1)
    })
})

describe('audioPlayer.setVolume', () => {
    test('clamps a negative value to 0 and a value above 1 to 1', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.setVolume(-5)
        expect(a.volume).toBe(0)
        a.setVolume(5)
        expect(a.volume).toBe(1)
    })

    test('a non-numeric value falls back to 0', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.setVolume('abc')
        expect(a.volume).toBe(0)
    })

    test('setting a positive volume clears a previously-muted state', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.muted = true
        a.setVolume(0.5)
        expect(a.muted).toBe(false)
    })

    test('setting volume to exactly 0 leaves the muted flag untouched', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.muted = true
        a.setVolume(0)
        expect(a.muted).toBe(true)
    })

    test('persists the new volume after a 400ms debounce, and only once for rapid repeated calls', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.setVolume(0.3)
        a.setVolume(0.6)
        a.setVolume(0.9)

        vi.advanceTimersByTime(399)
        expect(localStorage.getItem('audio_player_state')).toBeNull()

        vi.advanceTimersByTime(1)
        const saved = JSON.parse(localStorage.getItem('audio_player_state'))
        expect(saved.volume).toBe(0.9)
    })
})

describe('audioPlayer guard-only methods before any track is loaded', () => {
    test('toggle/reset/clearResume are no-ops when shared.audio has never been created', () => {
        const a = audioPlayer('d.mp3', 'D')
        expect(() => a.toggle()).not.toThrow()
        expect(() => a.reset()).not.toThrow()
        expect(() => a.clearResume()).not.toThrow()
    })
})

describe('audioPlayer.load', () => {
    test('reloading the SAME src preserves currentTime/duration', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.load('track.mp3', 'Track', false)
        a.currentTime = 42
        a.duration = 100

        a.load('track.mp3', 'Track', false)

        expect(a.currentTime).toBe(42)
        expect(a.duration).toBe(100)
    })

    test('loading a DIFFERENT src resets currentTime/duration back to 0', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.load('track-1.mp3', 'One', false)
        a.currentTime = 42
        a.duration = 100

        a.load('track-2.mp3', 'Two', false)

        expect(a.currentTime).toBe(0)
        expect(a.duration).toBe(0)
        expect(a.trackName).toBe('Two')
        expect(a.currentSrc).toBe('track-2.mp3')
    })

    test('persist=false skips writing to localStorage', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.load('track.mp3', 'Track', false, false)
        expect(localStorage.getItem('audio_player_state')).toBeNull()
    })

    test('persist defaults to true and writes the snapshot', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.load('track.mp3', 'Track', false)
        expect(JSON.parse(localStorage.getItem('audio_player_state')).src).toBe('track.mp3')
    })
})

describe('audioPlayer.persist — custom vs library track branching', () => {
    test('a non-custom (library) load stores a full snapshot including src/name/time', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.load('track.mp3', 'Track', false)
        a.currentTime = 12
        a.playing = true

        a.persist()

        const saved = JSON.parse(localStorage.getItem('audio_player_state'))
        expect(saved).toMatchObject({ src: 'track.mp3', name: 'Track', custom: false, time: 12, playing: true })
    })

    test('a custom (uploaded) track only updates muted/volume, preserving the rest of the stored snapshot', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.load('track.mp3', 'Track', false)
        a.currentTime = 30
        a.persist()

        a.load('blob:custom', 'Uploaded', true)
        a.muted = true
        a.volume = 0.4

        a.persist()

        const saved = JSON.parse(localStorage.getItem('audio_player_state'))
        expect(saved.time).toBe(30)
        expect(saved.src).toBe('track.mp3')
        expect(saved.muted).toBe(true)
        expect(saved.volume).toBe(0.4)
    })
})

describe('audioPlayer.toggleMute', () => {
    test('flips the muted flag and persists it', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.toggleMute()
        expect(a.muted).toBe(true)
        expect(JSON.parse(localStorage.getItem('audio_player_state')).muted).toBe(true)

        a.toggleMute()
        expect(a.muted).toBe(false)
    })

    test('also mutes the shared audio element once one has been loaded', () => {
        const a = audioPlayer('d.mp3', 'D')
        a.load('track.mp3', 'Track', false)
        const sharedAudio = FakeAudio.instances[FakeAudio.instances.length - 1]

        a.toggleMute()

        expect(sharedAudio.muted).toBe(true)
    })
})

describe('audioPlayer.init — fresh load vs shared-instance hydration', () => {
    test('the first instance in a module with no prior shared.audio loads the default track', () => {
        const a = audioPlayer('default.mp3', 'Default')
        a.init()
        expect(a.currentSrc).toBe('default.mp3')
        expect(a.trackName).toBe('Default')
    })

    test('a second instance hydrates its UI state from the already-shared audio instead of reloading', () => {
        const a = audioPlayer('default.mp3', 'Default')
        a.init()
        const sharedAudio = FakeAudio.instances[FakeAudio.instances.length - 1]
        sharedAudio.currentTime = 33
        sharedAudio.duration = 120

        const b = audioPlayer('default.mp3', 'Default')
        b.init()

        expect(b.currentTime).toBe(33)
        expect(b.duration).toBe(120)
        expect(b.playing).toBe(false)
    })

    test('destroy() clears shared.ui so the destroyed instance stops receiving further audio-event updates', () => {
        const a = audioPlayer('default.mp3', 'Default')
        a.init()
        const b = audioPlayer('default.mp3', 'Default')
        b.init()
        const sharedAudio = FakeAudio.instances[FakeAudio.instances.length - 1]

        sharedAudio._trigger('playing')
        expect(b.playing).toBe(true)

        b.destroy()
        sharedAudio._trigger('pause')
        expect(b.playing).toBe(true)
    })

    test('destroy() is a no-op for an instance that never owned "ui"', () => {
        const a = audioPlayer('default.mp3', 'Default')
        a.init()
        const b = audioPlayer('default.mp3', 'Default')
        b.init()

        expect(() => a.destroy()).not.toThrow()
    })
})
