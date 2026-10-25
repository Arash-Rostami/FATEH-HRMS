import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import stopwatch from '../stopwatch.js'

const STORAGE_KEY = 'stopwatch_state'
const DISMISSED_KEY = 'stopwatch_dismissed_reminder_id'

function fakeModal() {
    return { classList: { add: vi.fn(), remove: vi.fn(), contains: vi.fn(() => false) } }
}

function readState() {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : null
}

beforeEach(() => {
    localStorage.clear()
    vi.useFakeTimers()
})

afterEach(() => {
    vi.useRealTimers()
})

describe('stopwatch.formatSeconds', () => {
    test('formats under a minute, exact minutes, and hour+minute+second durations', () => {
        const s = stopwatch('a.mp3')
        expect(s.formatSeconds(0)).toBe('00:00')
        expect(s.formatSeconds(59)).toBe('00:59')
        expect(s.formatSeconds(65)).toBe('01:05')
        expect(s.formatSeconds(3661)).toBe('1:01:01')
    })

    test('treats a falsy/undefined input as 0', () => {
        const s = stopwatch('a.mp3')
        expect(s.formatSeconds(undefined)).toBe('00:00')
    })
})

describe('stopwatch.armReminder', () => {
    test('is a no-op while the timer is already running', () => {
        const s = stopwatch('a.mp3')
        s.timer.running = true
        s.timer.seconds = 10
        s.armReminder({ id: 1, eventAtIso: '2026-01-01T10:00:00.000Z', title: 'x' })
        expect(s.timer.seconds).toBe(10)
        expect(s.armedReminderId).toBeNull()
    })

    test('is a no-op for an unparseable eventAtIso', () => {
        const s = stopwatch('a.mp3')
        s.armReminder({ id: 1, eventAtIso: 'not-a-date', title: 'x' })
        expect(s.timer.running).toBe(false)
        expect(s.armedReminderId).toBeNull()
    })

    test('a target already in the past (0 seconds) fires the alarm immediately instead of arming', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        const s = stopwatch('a.mp3')
        s.modal = fakeModal()
        s.startAlarmLoop = vi.fn()

        s.armReminder({ id: 7, eventAtIso: '2026-01-01T09:00:00.000Z', title: 'past event' })

        expect(s.timer.seconds).toBe(0)
        expect(s.timer.running).toBe(false)
        expect(s.armedUntil).toBeNull()
        expect(s.open).toBe(true)
        expect(s.minimized).toBe(false)
        expect(s.armedReminderId).toBe(7)
        expect(s.startAlarmLoop).toHaveBeenCalledTimes(1)
        expect(readState().seconds).toBe(0)
    })

    test('a future target arms the countdown with armedUntil, closes the modal, and shows only the pill', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        const s = stopwatch('a.mp3')
        s.open = true
        s.startAlarmLoop = vi.fn()

        s.armReminder({ id: 3, eventAtIso: '2026-01-01T10:05:00.000Z', title: 'soon' })

        expect(s.timer.seconds).toBe(300)
        expect(s.timer.running).toBe(true)
        expect(s.armedUntil).toBe(Date.now() + 300000)
        expect(s.minimized).toBe(true)
        expect(s.open).toBe(false)
        expect(s.reminderTitle).toBe('soon')
        expect(s.startAlarmLoop).not.toHaveBeenCalled()
        expect(readState().running).toBe(true)
    })

    test('an id-less reminder stores armedReminderId as null', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        const s = stopwatch('a.mp3')
        s.armReminder({ eventAtIso: '2026-01-01T10:05:00.000Z', title: 'x' })
        expect(s.armedReminderId).toBeNull()
    })
})

describe('stopwatch.syncEventReminder (page load, modal open and the live event-reminder-armed signal)', () => {
    afterEach(() => {
        delete window.__eventReminder
    })

    test('arms once: a second sync while the countdown runs never re-arms or shifts the deadline', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        window.__eventReminder = { id: 5, eventAtIso: '2026-01-01T10:10:00.000Z', title: 'once' }
        const s = stopwatch('a.mp3')

        s.syncEventReminder()
        const armedUntil = s.armedUntil
        vi.advanceTimersByTime(120000)
        s.syncEventReminder()

        expect(s.timer.running).toBe(true)
        expect(s.armedUntil).toBe(armedUntil)
    })

    test('a reminder that already rang is dismissed and a later live signal never re-arms or re-rings it', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        window.__eventReminder = { id: 5, eventAtIso: '2026-01-01T10:00:05.000Z', title: 'rang' }
        const s = stopwatch('a.mp3')
        s.startAlarmLoop = vi.fn()
        s.restore = vi.fn()

        s.syncEventReminder()
        vi.advanceTimersByTime(6000)
        s.tick()
        s.syncEventReminder()

        expect(s.startAlarmLoop).toHaveBeenCalledTimes(1)
        expect(s.timer.running).toBe(false)
        expect(JSON.parse(localStorage.getItem(DISMISSED_KEY))).toBe(5)
    })

    test('init listens for event-reminder-armed to arm live, and destroy removes the listener', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        const s = stopwatch('a.mp3')
        s.$refs = {}
        s.init()
        expect(s.timer.running).toBe(false)

        window.__eventReminder = { id: 8, eventAtIso: '2026-01-01T10:30:00.000Z', title: 'live' }
        window.dispatchEvent(new Event('event-reminder-armed'))
        expect(s.timer.running).toBe(true)
        expect(s.armedReminderId).toBe(8)

        s.destroy()
        s.timer.running = false
        s.armedUntil = null
        window.__eventReminder = { id: 9, eventAtIso: '2026-01-01T11:00:00.000Z', title: 'ignored' }
        window.dispatchEvent(new Event('event-reminder-armed'))
        expect(s.timer.running).toBe(false)
    })
})

describe('stopwatch.restoreState', () => {
    test('does nothing when no persisted state exists', () => {
        const s = stopwatch('a.mp3')
        s.restoreState()
        expect(s.timer.seconds).toBe(300)
        expect(s.timer.running).toBe(false)
    })

    test('a running timer whose armedUntil is still in the future resumes the countdown', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            running: true, armedUntil: Date.now() + 120000, minimized: true, reminderTitle: 'rem',
        }))
        const s = stopwatch('a.mp3')

        s.restoreState()

        expect(s.timer.running).toBe(true)
        expect(s.timer.seconds).toBe(120)
        expect(s.minimized).toBe(true)
        expect(s.reminderTitle).toBe('rem')
    })

    test('a running timer whose armedUntil missed by seconds (inside the grace window) still fires the alarm on restore', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            running: true, armedUntil: Date.now() - 5000, minimized: true,
        }))
        const s = stopwatch('a.mp3')
        s.modal = fakeModal()
        s.startAlarmLoop = vi.fn()

        s.restoreState()

        expect(s.timer.running).toBe(false)
        expect(s.timer.seconds).toBe(0)
        expect(s.open).toBe(true)
        expect(s.minimized).toBe(false)
        expect(s.startAlarmLoop).toHaveBeenCalledTimes(1)
    })

    test('a long-past armedUntil is a MISSED alarm: it never rings, opens the tool with the one-line notice, and persists it once', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            running: true, armedUntil: Date.now() - 120000, minimized: true,
        }))
        const s = stopwatch('a.mp3')
        s.modal = fakeModal()
        s.startAlarmLoop = vi.fn()

        s.restoreState()

        expect(s.startAlarmLoop).not.toHaveBeenCalled()
        expect(s.timer.running).toBe(false)
        expect(s.timer.seconds).toBe(0)
        expect(s.open).toBe(true)
        expect(s.minimized).toBe(false)
        expect(s.missedNotice).toBe(true)
        expect(readState().missedNotice).toBe(true)
        expect(readState().running).toBe(false)

        const reloaded = stopwatch('a.mp3')
        reloaded.startAlarmLoop = vi.fn()
        reloaded.restoreState()
        expect(reloaded.startAlarmLoop).not.toHaveBeenCalled()
        expect(reloaded.missedNotice).toBe(true)
    })

    test('a non-running persisted state restores only the plain seconds value', () => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ running: false, seconds: 42, minimized: false }))
        const s = stopwatch('a.mp3')

        s.restoreState()

        expect(s.timer.seconds).toBe(42)
        expect(s.timer.running).toBe(false)
    })
})

describe('stopwatch.toggleTimer / resetTimer / setTimerPreset', () => {
    test('toggleTimer turning on arms armedUntil from the current seconds and stops any alarm', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        const s = stopwatch('a.mp3')
        s.timer.seconds = 90
        s.stopAlarm = vi.fn()

        s.toggleTimer()

        expect(s.timer.running).toBe(true)
        expect(s.armedUntil).toBe(Date.now() + 90000)
        expect(s.stopAlarm).toHaveBeenCalledTimes(1)
    })

    test('toggleTimer turning off clears armedUntil without stopping the alarm', () => {
        const s = stopwatch('a.mp3')
        s.timer.running = true
        s.armedUntil = 12345
        s.stopAlarm = vi.fn()

        s.toggleTimer()

        expect(s.timer.running).toBe(false)
        expect(s.armedUntil).toBeNull()
        expect(s.stopAlarm).not.toHaveBeenCalled()
    })

    test('resetTimer restores the 300s default and clears reminder/alarm state', () => {
        const s = stopwatch('a.mp3')
        s.stopAlarm = vi.fn()
        s.timer.running = true
        s.timer.seconds = 10
        s.reminderTitle = 'x'

        s.resetTimer()

        expect(s.timer.running).toBe(false)
        expect(s.timer.seconds).toBe(300)
        expect(s.reminderTitle).toBeNull()
        expect(s.stopAlarm).toHaveBeenCalledTimes(1)
    })

    test('setTimerPreset coerces its input to a number and falls back to 0', () => {
        const s = stopwatch('a.mp3')
        s.stopAlarm = vi.fn()

        s.setTimerPreset('600')
        expect(s.timer.seconds).toBe(600)

        s.setTimerPreset('not-a-number')
        expect(s.timer.seconds).toBe(0)
    })
})

describe('stopwatch.closeModal', () => {
    test('persists the armed reminder id as dismissed, resets state, and tears the modal down', () => {
        const s = stopwatch('a.mp3')
        s.modal = fakeModal()
        s.stopAlarm = vi.fn()
        s.armedReminderId = 9
        s.timer.running = true
        s.timer.seconds = 5

        s.closeModal()

        expect(localStorage.getItem(DISMISSED_KEY)).toBe('9')
        expect(s.timer.running).toBe(false)
        expect(s.timer.seconds).toBe(300)
        expect(s.armedReminderId).toBeNull()
        expect(s.open).toBe(false)
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull()
        expect(s.modal.classList.add).toHaveBeenCalledWith('hidden')
    })

    test('does not touch the dismissed-reminder key when no reminder was armed', () => {
        const s = stopwatch('a.mp3')
        s.modal = fakeModal()
        s.stopAlarm = vi.fn()

        s.closeModal()

        expect(localStorage.getItem(DISMISSED_KEY)).toBeNull()
    })
})

describe('stopwatch.stopAlarm', () => {
    test('clears the alarm interval and stops/nulls the audio instance', () => {
        const s = stopwatch('a.mp3')
        const pause = vi.fn()
        s.alarmInterval = setInterval(() => {}, 1000)
        s.alarmAudioInstance = { pause, currentTime: 5 }

        s.stopAlarm()

        expect(s.alarmInterval).toBeNull()
        expect(pause).toHaveBeenCalledTimes(1)
        expect(s.alarmAudioInstance).toBeNull()
    })

    test('is a no-op when nothing is active', () => {
        const s = stopwatch('a.mp3')
        expect(() => s.stopAlarm()).not.toThrow()
    })
})

describe('stopwatch.notifyAlarm', () => {
    afterEach(() => { delete global.Notification })

    test('shows a notification with the reminder title when permission is already granted', () => {
        const created = []
        global.Notification = class {
            constructor(title, opts) { created.push({ title, opts }) }
            static permission = 'granted'
        }
        const s = stopwatch('a.mp3')
        s.reminderTitle = 'جلسه'

        s.notifyAlarm()

        expect(created).toHaveLength(1)
        expect(created[0].opts.body).toContain('جلسه')
    })

    test('uses a generic body when there is no reminder title', () => {
        const created = []
        global.Notification = class {
            constructor(title, opts) { created.push({ title, opts }) }
            static permission = 'granted'
        }
        const s = stopwatch('a.mp3')

        s.notifyAlarm()

        expect(created[0].opts.body).toBe('زمان‌سنج شما به پایان رسید')
    })

    test('does not request permission at fire time when permission is still default (request happens on tool open)', () => {
        const requestPermission = vi.fn()
        global.Notification = class {
            constructor() { throw new Error('should not construct without permission') }
            static permission = 'default'
            static requestPermission = requestPermission
        }
        const s = stopwatch('a.mp3')

        expect(() => s.notifyAlarm()).not.toThrow()
        expect(requestPermission).not.toHaveBeenCalled()
    })

    test('mounted requests permission once when permission is still default', () => {
        const requestPermission = vi.fn(() => Promise.resolve('granted'))
        global.Notification = class {
            static permission = 'default'
            static requestPermission = requestPermission
        }
        const s = stopwatch('a.mp3')
        s.modal = fakeModal()
        s.syncEventReminder = vi.fn()

        s.mounted()

        expect(requestPermission).toHaveBeenCalledTimes(1)
    })

    test('does nothing when permission was denied', () => {
        global.Notification = class {
            constructor() { throw new Error('should not construct when denied') }
            static permission = 'denied'
            static requestPermission = vi.fn()
        }
        const s = stopwatch('a.mp3')

        expect(() => s.notifyAlarm()).not.toThrow()
    })

    test('is a no-op when the Notification API is unavailable', () => {
        const s = stopwatch('a.mp3')
        expect(() => s.notifyAlarm()).not.toThrow()
    })
})

describe('stopwatch.snooze', () => {
    test('stops any ringing alarm and arms a new countdown for the given minutes', () => {
        const s = stopwatch('a.mp3')
        const pause = vi.fn()
        s.alarmInterval = setInterval(() => {}, 1000)
        s.alarmAudioInstance = { pause, currentTime: 0 }
        s.missedNotice = true

        s.snooze(5)

        expect(pause).toHaveBeenCalledTimes(1)
        expect(s.alarmInterval).toBeNull()
        expect(s.missedNotice).toBe(false)
        expect(s.timer.running).toBe(true)
        expect(s.timer.seconds).toBe(300)
    })

    test('defaults to 5 minutes when called with no argument', () => {
        const s = stopwatch('a.mp3')
        s.snooze()
        expect(s.timer.seconds).toBe(300)
    })

    test('a snoozed reminder rings once more at expiry and is never re-armed by syncEventReminder', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        const s = stopwatch('a.mp3')
        s.modal = fakeModal()
        s.startAlarmLoop = vi.fn()
        s.armReminder({ id: 7, eventAtIso: '2026-01-01T10:00:00.000Z', title: 'meeting' })
        expect(localStorage.getItem(DISMISSED_KEY)).toBe('7')

        s.snooze(5)
        expect(s.timer.running).toBe(true)

        s.armedUntil = Date.now() - 1000
        s.tick()
        expect(s.timer.running).toBe(false)
        expect(s.startAlarmLoop).toHaveBeenCalledTimes(2)

        window.__eventReminder = { id: 7, eventAtIso: '2026-01-01T10:05:00.000Z', title: 'meeting' }
        s.syncEventReminder()
        expect(s.timer.running).toBe(false)
        delete window.__eventReminder
    })
})

describe('stopwatch.minimize / restore', () => {
    test('minimize hides the modal and flips open/minimized', () => {
        const s = stopwatch('a.mp3')
        s.modal = fakeModal()
        s.open = true

        s.minimize()

        expect(s.minimized).toBe(true)
        expect(s.open).toBe(false)
        expect(s.modal.classList.add).toHaveBeenCalledWith('hidden')
    })

    test('restore shows the modal and flips open/minimized back', () => {
        const s = stopwatch('a.mp3')
        s.modal = fakeModal()
        s.minimized = true

        s.restore()

        expect(s.minimized).toBe(false)
        expect(s.open).toBe(true)
        expect(s.modal.classList.add).toHaveBeenCalledWith('flex')
    })
})

describe('stopwatch.tick', () => {
    test('derives the remaining time from armedUntil so a throttled background tab stays accurate', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        const s = stopwatch('a.mp3')
        s.timer.running = true
        s.armedUntil = Date.now() + 65000

        vi.setSystemTime(new Date('2026-01-01T10:00:05.000Z'))
        s.tick()

        expect(s.timer.seconds).toBe(60)
    })

    test('fires the alarm through restore() when armedUntil has passed, showing the modal over the pill', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        const s = stopwatch('a.mp3')
        s.modal = fakeModal()
        s.startAlarmLoop = vi.fn()
        s.timer.running = true
        s.armedUntil = Date.now() + 5000
        s.minimized = true

        vi.setSystemTime(new Date('2026-01-01T10:00:15.000Z'))
        s.tick()

        expect(s.timer.seconds).toBe(0)
        expect(s.timer.running).toBe(false)
        expect(s.startAlarmLoop).toHaveBeenCalledTimes(1)
        expect(s.open).toBe(true)
        expect(s.minimized).toBe(false)
    })

    test('is a no-op when the timer is not running', () => {
        const s = stopwatch('a.mp3')
        s.timer.seconds = 42

        s.tick()

        expect(s.timer.seconds).toBe(42)
    })
})

describe('stopwatch.setCustomMinutes', () => {
    test('never clobbers a running or ringing timer', () => {
        const s = stopwatch('a.mp3')
        s.timer.running = true
        s.timer.seconds = 10
        s.customMins = 5

        s.setCustomMinutes()

        expect(s.timer.seconds).toBe(10)

        const r = stopwatch('a.mp3')
        r.alarmInterval = 123
        r.timer.seconds = 10
        r.customMins = 5

        r.setCustomMinutes()

        expect(r.timer.seconds).toBe(10)
    })

    test('ignores an empty input instead of wedging the timer at zero', () => {
        const s = stopwatch('a.mp3')
        s.timer.seconds = 300
        s.customMins = null

        s.setCustomMinutes()

        expect(s.timer.seconds).toBe(300)
    })

    test('rounds and clamps the input to 1–1440 minutes', () => {
        const s = stopwatch('a.mp3')

        s.customMins = 0
        s.setCustomMinutes()
        expect(s.timer.seconds).toBe(60)

        s.customMins = 3.7
        s.setCustomMinutes()
        expect(s.timer.seconds).toBe(240)

        s.customMins = 99999
        s.setCustomMinutes()
        expect(s.timer.seconds).toBe(86400)
    })
})

describe('stopwatch.init / destroy', () => {
    test('init arms a pending un-dismissed event reminder', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        window.__eventReminder = { id: 5, eventAtIso: '2026-01-01T10:05:00.000Z', title: 'جلسه' }
        const s = stopwatch('a.mp3')
        s.$refs = { stopwatchModal: fakeModal() }

        s.init()

        expect(s.timer.running).toBe(true)
        expect(s.reminderTitle).toBe('جلسه')

        s.destroy()
        delete window.__eventReminder
    })

    test('a dismissed reminder is not armed', () => {
        window.__eventReminder = { id: 5, eventAtIso: '2026-01-01T10:05:00.000Z', title: 'x' }
        localStorage.setItem(DISMISSED_KEY, JSON.stringify(5))
        const s = stopwatch('a.mp3')
        s.$refs = { stopwatchModal: fakeModal() }

        s.init()

        expect(s.timer.running).toBe(false)

        s.destroy()
        delete window.__eventReminder
    })

    test('mounted re-checks window.__eventReminder so a reminder refreshed on SPA navigation still arms', () => {
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'))
        window.__eventReminder = { id: 9, eventAtIso: '2026-01-01T10:05:00.000Z', title: 'y' }
        const s = stopwatch('a.mp3')
        s.modal = fakeModal()

        s.mounted()

        expect(s.timer.running).toBe(true)

        s.destroy()
        delete window.__eventReminder
    })

    test('destroy clears the tick interval and removes the window listeners', () => {
        const s = stopwatch('a.mp3')
        s.$refs = { stopwatchModal: fakeModal() }

        s.init()
        expect(vi.getTimerCount()).toBe(1)

        s.destroy()

        expect(vi.getTimerCount()).toBe(0)
        window.dispatchEvent(new Event('stopwatch'))
        expect(s.open).toBe(false)
    })
})
