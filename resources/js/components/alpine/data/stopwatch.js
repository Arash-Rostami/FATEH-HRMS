import persistentStateMixin from "../mixins/persistentState.js";

const STORAGE_KEY = 'stopwatch_state';
const DISMISSED_KEY = 'stopwatch_dismissed_reminder_id';
const MISSED_GRACE_MS = 60000;
const MAX_CUSTOM_MINS = 1440;

export default function stopwatch(mp3) {
    return {
        ...persistentStateMixin(),
        timer: {running: false, seconds: 300},
        armedUntil: null,
        armedReminderId: null,
        reminderTitle: null,
        missedNotice: false,
        customMins: null,
        alarm: mp3,
        alarmInterval: null,
        alarmAudioInstance: null,
        open: false,
        minimized: false,

        init() {
            this.modal = this.$refs.stopwatchModal;
            if (this.modal) {
                this._windowStopwatchListener = () => this.mounted();
                this._windowKeydownListener = (event) => {
                    if (event.key === "Escape" && this.modal.classList.contains('flex')) this.minimize();
                };
                window.addEventListener('stopwatch', this._windowStopwatchListener);
                window.addEventListener('keydown', this._windowKeydownListener);
            }
            this.restoreState();
            this.syncEventReminder();
            this._eventReminderListener = () => this.syncEventReminder();
            window.addEventListener('event-reminder-armed', this._eventReminderListener);
            this._tickInterval = setInterval(() => this.tick(), 1000);
        },

        tick() {
            if (!this.timer.running) return;
            const remaining = Math.max(0, Math.round((this.armedUntil - Date.now()) / 1000));
            this.timer.seconds = remaining;
            if (remaining === 0) {
                this.timer.running = false;
                this.armedUntil = null;
                if (this.armedReminderId !== null) this._saveState(DISMISSED_KEY, this.armedReminderId);
                this.restore();
                this.startAlarmLoop();
                this.saveState();
            }
        },

        restoreState() {
            const raw = this._loadState(STORAGE_KEY);
            if (!raw) return;

            this.minimized = !!raw.minimized;
            this.armedReminderId = raw.armedReminderId ?? null;
            this.reminderTitle = raw.reminderTitle || null;
            this.missedNotice = !!raw.missedNotice;

            if (raw.running && Number.isFinite(raw.armedUntil)) {
                const remaining = Math.round((raw.armedUntil - Date.now()) / 1000);
                if (remaining <= 0) {
                    this.timer.seconds = 0;
                    this.timer.running = false;
                    this.armedUntil = null;
                    this.armedReminderId = raw.armedReminderId ?? null;
                    if (this.armedReminderId !== null) this._saveState(DISMISSED_KEY, this.armedReminderId);
                    this.restore();
                    if (Date.now() - raw.armedUntil <= MISSED_GRACE_MS) {
                        this.startAlarmLoop();
                    } else {
                        this.missedNotice = true;
                    }
                    this.saveState();
                } else {
                    this.timer.seconds = remaining;
                    this.timer.running = true;
                    this.armedUntil = raw.armedUntil;
                }
            } else if (typeof raw.seconds === 'number') {
                this.timer.seconds = raw.seconds;
            }
        },

        saveState() {
            this._saveState(STORAGE_KEY, {
                running: this.timer.running,
                seconds: this.timer.seconds,
                armedUntil: this.armedUntil,
                armedReminderId: this.armedReminderId,
                minimized: this.minimized,
                reminderTitle: this.reminderTitle,
                missedNotice: this.missedNotice,
            });
        },

        clearState() {
            this._clearState(STORAGE_KEY);
        },
        syncEventReminder() {
            if (window.__eventReminder && window.__eventReminder.id !== this._loadState(DISMISSED_KEY)) {
                this.armReminder(window.__eventReminder);
            }
        },
        armReminder({id, eventAtIso, title}) {
            if (this.timer.running) return;

            const target = new Date(eventAtIso).getTime();
            if (isNaN(target)) return;

            const seconds = Math.max(0, Math.round((target - Date.now()) / 1000));
            this.timer.seconds = seconds;
            this.armedReminderId = id ?? null;
            this.reminderTitle = title;
            this.missedNotice = false;

            if (seconds === 0) {
                this.timer.running = false;
                this.armedUntil = null;
                if (this.armedReminderId !== null) this._saveState(DISMISSED_KEY, this.armedReminderId);
                this.restore();
                this.startAlarmLoop();
            } else {
                this.timer.running = true;
                this.armedUntil = Date.now() + seconds * 1000;
                this.minimized = true;
                this.open = false;
            }
            this.saveState();
        },
        startAlarmLoop() {
            this.stopAlarm();
            const a = new Audio(this.alarm);
            a.loop = true;
            this.alarmAudioInstance = a;
            a.play().catch(() => {
            });
            this.alarmInterval = setInterval(() => this.stopAlarm(), 60000);
            this.notifyAlarm();
        },
        notifyAlarm() {
            if (typeof Notification === 'undefined') return;
            if (Notification.permission !== 'granted') return;
            const body = this.reminderTitle ? `یادآوری: ${this.reminderTitle}` : 'زمان‌سنج شما به پایان رسید';
            new Notification('⏰ زمان تمام شد', {body});
        },
        snooze(minutes = 5) {
            this.stopAlarm();
            this.missedNotice = false;
            this.timer.seconds = minutes * 60;
            this.timer.running = true;
            this.armedUntil = Date.now() + minutes * 60 * 1000;
            this.saveState();
        },
        stopAlarm() {
            if (this.alarmInterval) {
                clearInterval(this.alarmInterval);
                this.alarmInterval = null;
            }
            if (this.alarmAudioInstance) {
                this.alarmAudioInstance.pause();
                this.alarmAudioInstance.currentTime = 0;
                this.alarmAudioInstance = null;
            }
        },
        toggleTimer() {
            this.timer.running = !this.timer.running;
            this.armedUntil = this.timer.running ? (Date.now() + this.timer.seconds * 1000) : null;
            if (this.timer.running) {
                this.stopAlarm();
                this.missedNotice = false;
            }
            this.saveState();
        },
        resetTimer() {
            this.timer.running = false;
            this.timer.seconds = 300;
            this.armedUntil = null;
            this.reminderTitle = null;
            this.missedNotice = false;
            this.stopAlarm();
            this.saveState();
        },
        setTimerPreset(s) {
            this.timer.seconds = Number(s) || 0;
            this.timer.running = false;
            this.armedUntil = null;
            this.reminderTitle = null;
            this.missedNotice = false;
            this.stopAlarm();
            this.saveState();
        },
        setCustomMinutes() {
            if (this.timer.running || this.alarmInterval || this.customMins == null || this.customMins === '') return;
            const mins = Math.round(Number(this.customMins));
            if (!Number.isFinite(mins)) return;
            this.setTimerPreset(Math.min(MAX_CUSTOM_MINS, Math.max(1, mins)) * 60);
        },
        formatSeconds(s) {
            s = Number(s || 0);
            const hh = Math.floor(s / 3600);
            const mm = Math.floor((s % 3600) / 60).toString().padStart(2, '0');
            const ss = Math.floor(s % 60).toString().padStart(2, '0');
            return hh > 0 ? `${hh}:${mm}:${ss}` : `${mm}:${ss}`;
        },
        minimize() {
            this.minimized = true;
            this._hideModal();
            this.saveState();
        },
        restore() {
            this.minimized = false;
            this.open = true;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
            this.saveState();
        },
        _hideModal() {
            this.open = false;
            if (this.modal) {
                this.modal.classList.remove('flex');
                this.modal.classList.add('hidden');
            }
        },
        closeModal() {
            this.stopAlarm();
            if (this.armedReminderId !== null) this._saveState(DISMISSED_KEY, this.armedReminderId);
            this.timer.running = false;
            this.timer.seconds = 300;
            this.armedUntil = null;
            this.armedReminderId = null;
            this.reminderTitle = null;
            this.missedNotice = false;
            this.minimized = false;
            this.clearState();
            this._hideModal();
        },
        mounted() {
            this.open = true;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
            if (typeof Notification !== 'undefined' && Notification.permission === 'default') {
                Notification.requestPermission()?.catch?.(() => {});
            }
            this.syncEventReminder();
        },
        destroy() {
            if (this._tickInterval) {
                clearInterval(this._tickInterval);
                this._tickInterval = null;
            }
            this.stopAlarm();
            if (this._windowStopwatchListener) window.removeEventListener('stopwatch', this._windowStopwatchListener);
            if (this._windowKeydownListener) window.removeEventListener('keydown', this._windowKeydownListener);
            if (this._eventReminderListener) window.removeEventListener('event-reminder-armed', this._eventReminderListener);
            this.minimized = false;
            this._hideModal();
        }
    };
}