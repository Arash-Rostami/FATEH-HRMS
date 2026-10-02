import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import radio from '../radio.js'

class MockAudio {
    constructor() {
        this.src = ''
        this.preload = ''
        this._listeners = {}
    }
    addEventListener(type, fn) {
        (this._listeners[type] ??= []).push(fn)
    }
    _fire(type) {
        (this._listeners[type] || []).forEach((fn) => fn())
    }
    play() { return Promise.resolve() }
    pause() {}
    load() {}
}

beforeEach(() => {
    localStorage.clear()
    global.Audio = MockAudio
})

afterEach(() => {
    vi.restoreAllMocks()
    delete global.fetch
})

function okResponse(data) {
    return Promise.resolve({ ok: true, json: () => Promise.resolve(data) })
}

describe('radio getters — statusClass / currentStationName / currentGenreLabel', () => {
    test('statusClass maps a known status string and falls back to the "آماده" class for an unknown one', () => {
        const c = radio('r')
        c.status = 'در حال پخش'
        expect(c.statusClass).toBe('bg-green-100 text-green-800')
        c.status = 'nonsense'
        expect(c.statusClass).toBe('bg-gray-100 text-gray-800')
    })

    test('currentStationName reports a placeholder with no selection, resolves the matching station, or "unknown" if not found', () => {
        const c = radio('r')
        expect(c.currentStationName).toBe('یک ایستگاه انتخاب کنید')

        c.stations = [{ name: 'Jazz24', url_resolved: 'u1' }]
        c.currentStationUrl = 'u1'
        expect(c.currentStationName).toBe('Jazz24')

        c.currentStationUrl = 'missing'
        expect(c.currentStationName).toBe('ایستگاه ناشناس')
    })

    test('currentGenreLabel resolves the Persian label or falls back to "جاز"', () => {
        const c = radio('r')
        expect(c.currentGenreLabel).toBe('جاز')
        c.currentGenre = 'lofi'
        expect(c.currentGenreLabel).toBe('لو-فای')
        c.currentGenre = 'unknown'
        expect(c.currentGenreLabel).toBe('جاز')
    })

    test('a restored station missing from the fetched list keeps showing its persisted name instead of "unknown"', () => {
        const c = radio('r')
        c.stations = [{ name: 'Other', url_resolved: 'u1' }]
        c.currentStationUrl = 'u-gone'
        c.stationName = 'Jazz24'
        expect(c.currentStationName).toBe('Jazz24')
    })
})

describe('radio station-name persistence', () => {
    test('selecting a station records its name and persists it alongside the url', () => {
        const c = radio('r')
        c.stations = [{ name: 'Jazz24', url_resolved: 'u1' }]
        c.setupAudio()

        c.handleStationChange('u1')

        expect(c.stationName).toBe('Jazz24')
        const saved = JSON.parse(localStorage.getItem('r_state'))
        expect(saved.stationName).toBe('Jazz24')
    })

    test('init restores the persisted name so the minimized pill shows the right station before the list loads', () => {
        localStorage.setItem('r_state', JSON.stringify({ genre: 'jazz', stationUrl: 'u1', stationName: 'Jazz24', minimized: true }))
        const c = radio('r')
        c.$watch = vi.fn()

        c.init()

        expect(c.minimized).toBe(true)
        expect(c.stationName).toBe('Jazz24')
        expect(c.currentStationName).toBe('Jazz24')
        expect(c.formattedDisplay).toBe('Jazz24')

        c.destroy()
    })
})

describe('radio.updateDisplay', () => {
    test('shows the current station name when one is selected, otherwise the raw status', () => {
        const c = radio('r')
        c.status = 'آماده'
        c.updateDisplay()
        expect(c.formattedDisplay).toBe('آماده')

        c.stations = [{ name: 'X', url_resolved: 'u' }]
        c.currentStationUrl = 'u'
        c.updateDisplay()
        expect(c.formattedDisplay).toBe('X')
    })
})

describe('radio.setVolume', () => {
    test('clamps to 0-1 and applies to the live audio element', () => {
        const c = radio('r')
        c.setupAudio()

        c.setVolume(0.4)
        expect(c.volume).toBe(0.4)
        expect(c.audio.volume).toBe(0.4)

        c.setVolume(5)
        expect(c.volume).toBe(1)

        c.setVolume(-2)
        expect(c.volume).toBe(0)
    })

    test('persists the volume so init restores it later', () => {
        const a = radio('r')
        a.setVolume(0.3)

        const b = radio('r')
        b.$watch = vi.fn()
        b.init()

        expect(b.volume).toBe(0.3)
        b.destroy()
    })

    test('setupAudio applies the already-restored volume to a freshly created audio element', () => {
        const c = radio('r')
        c.volume = 0.6
        c.setupAudio()
        expect(c.audio.volume).toBe(0.6)
    })
})

describe('radio.toggleMute / setVolumeFromY', () => {
    test('toggleMute remembers the previous volume and restores it on unmute', () => {
        const c = radio('r')
        c.setVolume(0.7)

        c.toggleMute()
        expect(c.volume).toBe(0)

        c.toggleMute()
        expect(c.volume).toBe(0.7)

        c.toggleMute()
        c.toggleMute()
        expect(c.volume).toBe(0.7)
    })

    test('unmuting with no remembered volume falls back to full volume', () => {
        const c = radio('r')
        c.volume = 0
        c.toggleMute()
        expect(c.volume).toBe(1)
    })

    test('setVolumeFromY maps the pointer Y to volume 1 at the track top and 0 at the bottom', () => {
        const c = radio('r')
        c.$refs = { volTrack: { getBoundingClientRect: () => ({ top: 100, bottom: 200, height: 100 }) } }

        c.setVolumeFromY({ clientY: 100 })
        expect(c.volume).toBe(1)

        c.setVolumeFromY({ clientY: 200 })
        expect(c.volume).toBe(0)

        c.setVolumeFromY({ clientY: 150 })
        expect(c.volume).toBeCloseTo(0.5)

        c.setVolumeFromY({ clientY: 40 })
        expect(c.volume).toBe(1)

        c.setVolumeFromY({ clientY: 260 })
        expect(c.volume).toBe(0)
    })

    test('setVolumeFromY is a no-op when the track ref is absent', () => {
        const c = radio('r')
        c.$refs = {}
        expect(() => c.setVolumeFromY({ clientY: 0 })).not.toThrow()
        expect(c.volume).toBe(1)
    })

    test('faPercent renders the volume percentage in Persian digits', () => {
        const c = radio('r')
        c.setVolume(0.65)
        expect(c.faPercent).toBe('۶۵')

        c.setVolume(1)
        expect(c.faPercent).toBe('۱۰۰')

        c.setVolume(0)
        expect(c.faPercent).toBe('۰')
    })
})

describe('radio.autoSkipOnFailure', () => {
    test('advances to the next station in the list and plays it', () => {
        const c = radio('r')
        c.stations = [
            { name: 'A', url_resolved: 'u1' },
            { name: 'B', url_resolved: 'u2' },
        ]
        c.currentStationUrl = 'u1'
        c.setupAudio()

        c.autoSkipOnFailure()

        expect(c.currentStationUrl).toBe('u2')
        expect(c.stationName).toBe('B')
    })

    test('wraps around to the first station after the last one', () => {
        const c = radio('r')
        c.stations = [
            { name: 'A', url_resolved: 'u1' },
            { name: 'B', url_resolved: 'u2' },
        ]
        c.currentStationUrl = 'u2'
        c.setupAudio()

        c.autoSkipOnFailure()

        expect(c.currentStationUrl).toBe('u1')
    })

    test('a failing station cascades once per station via the error event, then gives up as آفلاین', async () => {
        const c = radio('r')
        c.stations = [
            { name: 'A', url_resolved: 'u1' },
            { name: 'B', url_resolved: 'u2' },
            { name: 'C', url_resolved: 'u3' },
        ]
        c.currentStationUrl = 'u1'
        c.setupAudio()
        c.audio.play = () => Promise.reject(Object.assign(new Error('interrupted'), { name: 'AbortError' }))

        c.autoSkipOnFailure()
        expect(c.currentStationUrl).toBe('u2')

        c.audio._fire('error')
        expect(c.currentStationUrl).toBe('u3')

        c.audio._fire('error')
        expect(c.currentStationUrl).toBe('u1')

        c.audio._fire('error')
        expect(c.status).toBe('آفلاین')
        expect(c._autoSkipAttempts).toBe(0)
    })

    test('a play rejection alone no longer advances the skip chain (single failure funnel)', async () => {
        const c = radio('r')
        c.stations = [
            { name: 'A', url_resolved: 'u1' },
            { name: 'B', url_resolved: 'u2' },
        ]
        c.currentStationUrl = 'u1'
        c.setupAudio()
        c.audio.play = () => Promise.reject(new Error('unreachable'))

        c.autoSkipOnFailure()
        expect(c.currentStationUrl).toBe('u2')

        await Promise.resolve(); await Promise.resolve()

        expect(c.currentStationUrl).toBe('u2')
        expect(c._autoSkipAttempts).toBe(1)
    })

    test('a latent error while paused does not start the next station', () => {
        const c = radio('r')
        c.stations = [
            { name: 'A', url_resolved: 'u1' },
            { name: 'B', url_resolved: 'u2' },
        ]
        c.currentStationUrl = 'u1'
        c.setupAudio()
        c._playIntent = false

        c.audio._fire('error')

        expect(c.currentStationUrl).toBe('u1')
        expect(c.status).toBe('خطا')
    })

    test('a successful play on a skipped station resets the attempt counter', () => {
        const c = radio('r')
        c.stations = [
            { name: 'A', url_resolved: 'u1' },
            { name: 'B', url_resolved: 'u2' },
        ]
        c.currentStationUrl = 'u1'
        c.setupAudio()
        c._autoSkipAttempts = 1

        c.audio._fire('playing')

        expect(c._autoSkipAttempts).toBe(0)
    })

    test('the audio element "error" event triggers the skip cascade when playback was intended', () => {
        const c = radio('r')
        c.stations = [
            { name: 'A', url_resolved: 'u1' },
            { name: 'B', url_resolved: 'u2' },
        ]
        c.currentStationUrl = 'u1'
        c.setupAudio()
        c._playIntent = true

        c.audio._fire('error')

        expect(c.currentStationUrl).toBe('u2')
    })

    test('is a no-op with an empty station list', () => {
        const c = radio('r')
        expect(() => c.autoSkipOnFailure()).not.toThrow()
        expect(c.currentStationUrl).toBe('')
    })
})

describe('radio.fetchStations — filtering, dedup, truncation', () => {
    test('filters out non-mp3/low-bitrate/failed-check/url-less stations, dedups case-insensitively (last wins), and truncates long names', async () => {
        const longName = 'A'.repeat(45)
        global.fetch = vi.fn(() => okResponse([
            { name: 'Alpha', url_resolved: 'u1', codec: 'MP3', lastcheckok: 1, bitrate: 128 },
            { name: 'alpha', url_resolved: 'u1b', codec: 'mp3', lastcheckok: 1, bitrate: 192 },
            { name: 'Quiet', url_resolved: 'u2', codec: 'mp3', lastcheckok: 1, bitrate: 32 },
            { name: 'BadCodec', url_resolved: 'u3', codec: 'aac', lastcheckok: 1, bitrate: 128 },
            { name: 'Failed', url_resolved: 'u4', codec: 'mp3', lastcheckok: 0, bitrate: 128 },
            { name: 'NoUrl', url_resolved: '', codec: 'mp3', lastcheckok: 1, bitrate: 128 },
            { name: longName, url_resolved: 'u5', codec: 'mp3', lastcheckok: 1, bitrate: 128 },
        ]))

        const c = radio('r')
        c.$nextTick = (fn) => fn()

        await c.fetchStations()

        const names = c.stations.map(s => s.name)
        expect(names).toContain('alpha')
        expect(names).not.toContain('Alpha')
        expect(names).not.toContain('Quiet')
        expect(names).not.toContain('BadCodec')
        expect(names).not.toContain('Failed')
        expect(names).not.toContain('NoUrl')
        expect(names).toContain('A'.repeat(40) + '...')
        expect(c.status).toBe('آماده')
    })

    test('auto-selects the first station and sets up audio when nothing was previously selected', async () => {
        global.fetch = vi.fn(() => okResponse([
            { name: 'First', url_resolved: 'u1', codec: 'mp3', lastcheckok: 1, bitrate: 128 },
        ]))
        const c = radio('r')
        c.$nextTick = (fn) => fn()

        await c.fetchStations()

        expect(c.currentStationUrl).toBe('u1')
        expect(c.audio).toBeInstanceOf(MockAudio)
    })

    test('when every endpoint/server attempt fails, falls back to the hardcoded list for the current genre (every catalog genre has a fallback, so status still reads "آماده")', async () => {
        global.fetch = vi.fn(() => Promise.resolve({ ok: false }))
        const c = radio('r')
        c.$nextTick = (fn) => fn()

        await c.fetchStations()

        expect(c.stations.map(s => s.stationuuid)).toEqual(['fb-jazz'])
        expect(c.status).toBe('آماده')
    })

    test('skips fetching entirely when stations are already loaded and no forced refetch is requested', async () => {
        global.fetch = vi.fn()
        const c = radio('r')
        c.stations = [{ name: 'Cached', url_resolved: 'u' }]

        await c.fetchStations(false)

        expect(global.fetch).not.toHaveBeenCalled()
    })

    test('a forced refetch while playing swaps to the first new station and resumes playback (genre switch keeps music going)', async () => {
        global.fetch = vi.fn(() => okResponse([{ name: 'New', url_resolved: 'u-new', codec: 'mp3', lastcheckok: 1, bitrate: 128 }]))
        const c = radio('r')
        c.$nextTick = (fn) => fn()
        c.stations = [{ name: 'Old', url_resolved: 'u-old' }]
        c.currentStationUrl = 'u-old'
        c.playing = true
        c.setupAudio()
        const playSpy = vi.spyOn(c.audio, 'play')

        await c.fetchStations(true)

        expect(c.stations.map(s => s.name)).toEqual(['New'])
        expect(c.currentStationUrl).toBe('u-new')
        expect(c.audio.src).toBe('u-new')
        expect(playSpy).toHaveBeenCalled()
        expect(c.playing).toBe(true)
    })

    test('a fetch started while another is in flight loses to the newer one (latest wins)', async () => {
        let resolveFirst
        global.fetch = vi.fn()
            .mockImplementationOnce(() => new Promise((resolve) => { resolveFirst = resolve }))
            .mockImplementation(() => okResponse([{ name: 'New', url_resolved: 'u2', codec: 'mp3', lastcheckok: 1, bitrate: 128 }]))
        const c = radio('r')
        c.$nextTick = (fn) => fn()

        const first = c.fetchStations(true)
        const second = c.fetchStations(true)
        await second

        resolveFirst({ ok: true, json: () => Promise.resolve([{ name: 'Old', url_resolved: 'u1', codec: 'mp3', lastcheckok: 1, bitrate: 128 }]) })
        await first

        expect(c.stations.map(s => s.name)).toEqual(['New'])
        expect(c.currentStationUrl).toBe('u2')
    })
})

describe('radio.setupAudio — event wiring updates status/display/persistence', () => {
    test('playing/pause/ended/waiting/error events drive status, and only persist when a station is selected', () => {
        const c = radio('r')
        c.saveState = vi.fn()
        c.setupAudio()

        c.audio._fire('playing')
        expect(c.playing).toBe(true)
        expect(c.status).toBe('در حال پخش')
        expect(c.saveState).not.toHaveBeenCalled()

        c.currentStationUrl = 'u1'
        c.audio._fire('pause')
        expect(c.playing).toBe(false)
        expect(c.status).toBe('متوقف')
        expect(c.saveState).toHaveBeenCalled()

        c.audio._fire('waiting')
        expect(c.status).toBe('در حال بافر...')

        c.audio._fire('error')
        expect(c.status).toBe('خطا')
        expect(c.playing).toBe(false)
    })

    test('calling setupAudio twice reuses the same audio instance', () => {
        const c = radio('r')
        c.setupAudio()
        const first = c.audio
        c.setupAudio()
        expect(c.audio).toBe(first)
    })
})

describe('radio.handleStationChange', () => {
    test('is a no-op for an empty url', () => {
        const c = radio('r')
        c.handleStationChange('')
        expect(c.audio).toBeNull()
    })

    test('when the audio element already points at the same url, skips reconfiguring but still persists', () => {
        const c = radio('r')
        c.saveState = vi.fn()
        c.setupAudio()
        c.audio.src = 'u1'
        c.handleStationChange('u1')
        expect(c.saveState).toHaveBeenCalled()
    })

    test('switching while playing pauses, reloads, and resumes playback on the new url', async () => {
        const c = radio('r')
        c.playing = true
        c.setupAudio()
        const pauseSpy = vi.spyOn(c.audio, 'pause')
        const loadSpy = vi.spyOn(c.audio, 'load')
        const playSpy = vi.spyOn(c.audio, 'play')

        c.handleStationChange('u2')
        await Promise.resolve()

        expect(pauseSpy).toHaveBeenCalled()
        expect(c.audio.src).toBe('u2')
        expect(loadSpy).toHaveBeenCalled()
        expect(playSpy).toHaveBeenCalled()
    })

    test('a rejected play() sets an error status and stops playback', async () => {
        const c = radio('r')
        c.playing = true
        c.setupAudio()
        c.audio.play = vi.fn(() => Promise.reject(new Error('x')))

        c.handleStationChange('u3')
        await new Promise((resolve) => setTimeout(resolve, 0))

        expect(c.status).toBe('خطا')
        expect(c.playing).toBe(false)
    })
})

describe('radio.nextStation', () => {
    test('is a no-op with no stations', () => {
        const c = radio('r')
        c.nextStation()
        expect(c.currentStationUrl).toBe('')
    })

    test('advances to the next station and wraps around at the end', () => {
        const c = radio('r')
        c.stations = [{ url_resolved: 'u1' }, { url_resolved: 'u2' }]
        c.currentStationUrl = 'u1'
        c.nextStation()
        expect(c.currentStationUrl).toBe('u2')
        c.nextStation()
        expect(c.currentStationUrl).toBe('u1')
    })

    test('starts at the first station when nothing was previously selected', () => {
        const c = radio('r')
        c.stations = [{ url_resolved: 'u1' }, { url_resolved: 'u2' }]
        c.nextStation()
        expect(c.currentStationUrl).toBe('u1')
    })
})

describe('radio.prevStation', () => {
    test('is a no-op with no stations', () => {
        const c = radio('r')
        c.prevStation()
        expect(c.currentStationUrl).toBe('')
    })

    test('goes back one station and wraps around at the start', () => {
        const c = radio('r')
        c.stations = [{ url_resolved: 'u1' }, { url_resolved: 'u2' }]
        c.currentStationUrl = 'u2'
        c.prevStation()
        expect(c.currentStationUrl).toBe('u1')
        c.prevStation()
        expect(c.currentStationUrl).toBe('u2')
    })

    test('starts at the first station when nothing was previously selected', () => {
        const c = radio('r')
        c.stations = [{ url_resolved: 'u1' }, { url_resolved: 'u2' }]
        c.prevStation()
        expect(c.currentStationUrl).toBe('u1')
    })
})

describe('radio.togglePlay', () => {
    test('is a no-op with no audio element or no selected station', () => {
        const c = radio('r')
        expect(() => c.togglePlay()).not.toThrow()
    })

    test('pauses when already playing', () => {
        const c = radio('r')
        c.currentStationUrl = 'u1'
        c.setupAudio()
        const pauseSpy = vi.spyOn(c.audio, 'pause')
        c.playing = true
        c.togglePlay()
        expect(pauseSpy).toHaveBeenCalled()
    })

    test('loads the current station into the audio element if not already set, then plays', () => {
        const c = radio('r')
        c.currentStationUrl = 'u1'
        c.setupAudio()
        const loadSpy = vi.spyOn(c.audio, 'load')
        const playSpy = vi.spyOn(c.audio, 'play')

        c.togglePlay()

        expect(c.audio.src).toBe('u1')
        expect(loadSpy).toHaveBeenCalled()
        expect(playSpy).toHaveBeenCalled()
    })

    test('a rejected play() sets an error status', async () => {
        const c = radio('r')
        c.currentStationUrl = 'u1'
        c.setupAudio()
        c.audio.play = vi.fn(() => Promise.reject(new Error('x')))

        c.togglePlay()
        await new Promise((resolve) => setTimeout(resolve, 0))

        expect(c.status).toBe('خطا')
        expect(c.playing).toBe(false)
    })
})

describe('radio.copyCurrentStation', () => {
    test('copies the station url when selected, falling back to the formatted display, and is a no-op with neither', () => {
        const c = radio('r')
        c.copyText = vi.fn()
        c.copyCurrentStation()
        expect(c.copyText).not.toHaveBeenCalled()

        c.formattedDisplay = 'آماده'
        c.copyCurrentStation()
        expect(c.copyText).toHaveBeenCalledWith('آماده', 'با موفقیت کپی شد.')

        c.currentStationUrl = 'u1'
        c.copyCurrentStation()
        expect(c.copyText).toHaveBeenCalledWith('u1', 'با موفقیت کپی شد.')
    })
})

describe('radio.minimize / restore / closeModal', () => {
    test('minimize/restore flip open+minimized and persist', () => {
        const c = radio('r')
        c.saveState = vi.fn()
        c.minimize()
        expect(c.minimized).toBe(true)
        expect(c.open).toBe(false)
        c.restore()
        expect(c.minimized).toBe(false)
        expect(c.open).toBe(true)
        expect(c.saveState).toHaveBeenCalledTimes(2)
    })

    test('closeModal pauses playback if playing, resets selection, and clears persisted state', () => {
        const c = radio('r')
        c.setupAudio()
        const pauseSpy = vi.spyOn(c.audio, 'pause')
        c.playing = true
        c.currentStationUrl = 'u1'
        localStorage.setItem('r_state', JSON.stringify({ stationUrl: 'u1' }))

        c.closeModal()

        expect(pauseSpy).toHaveBeenCalled()
        expect(c.currentStationUrl).toBe('')
        expect(c.open).toBe(false)
        expect(localStorage.getItem('r_state')).toBeNull()
    })
})

describe('radio.init / destroy', () => {
    test('restores genre/stationUrl/minimized from persisted state and reacts to a global "radio" open event', async () => {
        localStorage.setItem('r_state', JSON.stringify({ genre: 'lofi', stationUrl: 'u1', minimized: true }))
        global.fetch = vi.fn(() => okResponse([]))

        const c = radio('r')
        c.$watch = vi.fn()
        c.init()

        expect(c.currentGenre).toBe('lofi')
        expect(c.currentStationUrl).toBe('u1')
        expect(c.minimized).toBe(true)

        window.dispatchEvent(new Event('radio'))
        expect(c.open).toBe(true)
        expect(c.minimized).toBe(false)

        c.destroy()
    })

    test('Escape closes the panel only while it is open', () => {
        const c = radio('r')
        c.$watch = vi.fn()
        c.init()
        c.minimize = vi.fn()

        c.open = false
        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
        expect(c.minimize).not.toHaveBeenCalled()

        c.open = true
        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
        expect(c.minimize).toHaveBeenCalled()

        c.destroy()
    })

    test('destroy removes window listeners and tears down the audio element', () => {
        const c = radio('r')
        c.$watch = vi.fn()
        c.init()
        c.setupAudio()
        const pauseSpy = vi.spyOn(c.audio, 'pause')

        c.destroy()

        expect(pauseSpy).toHaveBeenCalled()
        expect(c.audio).toBeNull()

        window.dispatchEvent(new Event('radio'))
        expect(c.open).toBe(false)
    })
})
