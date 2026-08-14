import persistentStateMixin from "../mixins/persistentState.js";

const STORAGE_KEY = 'stopwatch_state';

export default function stopwatch(mp3) {
    return {
        ...persistentStateMixin(),
        timer: {running: false, seconds: 300},
        armedUntil: null,
        reminderTitle: null,
        customMins: null,
        alarm: mp3,
        alarmInterval: null,
        alarmAudioInstance: null,
        open: false,
        minimized: false,

        init() {
            this.modal = this.$refs.stopwatchModal;
            if (this.modal) {
                window.addEventListener('stopwatch', () => this.mounted());
                window.addEventListener('keydown', (event) => {
                    if (event.key === "Escape" && this.modal.classList.contains('flex')) this.minimize();
                });
            }
            this.restoreState();
            if (window.__eventReminder) this.armReminder(window.__eventReminder);
            setInterval(() => {
                if (this.timer.running && this.timer.seconds > 0) {
                    this.timer.seconds--;
                    if (this.timer.seconds === 0) {
                        this.timer.running = false;
                        this.armedUntil = null;
                        this.startAlarmLoop();
                        this.saveState();
                    }
                }
            }, 1000);
        },

        restoreState() {
            const raw = this._loadState(STORAGE_KEY);
            if (!raw) return;

            this.minimized = !!raw.minimized;
            this.reminderTitle = raw.reminderTitle || null;

            if (raw.running && Number.isFinite(raw.armedUntil)) {
                const remaining = Math.round((raw.armedUntil - Date.now()) / 1000);
                if (remaining <= 0) {
                    this.timer.seconds = 0;
                    this.timer.running = false;
                    this.armedUntil = null;
                    this.startAlarmLoop();
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
                minimized: this.minimized,
                reminderTitle: this.reminderTitle,
            });
        },

        clearState() {
            this._clearState(STORAGE_KEY);
        },
        armReminder({eventAtIso, title}) {
            if (this.timer.running) return;

            const target = new Date(eventAtIso).getTime();
            if (isNaN(target)) return;

            const seconds = Math.max(0, Math.round((target - Date.now()) / 1000));
            this.timer.seconds = seconds;
            this.reminderTitle = title;
            this.minimized = true;

            if (seconds === 0) {
                this.timer.running = false;
                this.armedUntil = null;
                this.startAlarmLoop();
            } else {
                this.timer.running = true;
                this.armedUntil = Date.now() + seconds * 1000;
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
            if (this.timer.running) this.stopAlarm();
            this.saveState();
        },
        resetTimer() {
            this.timer.running = false;
            this.timer.seconds = 300;
            this.armedUntil = null;
            this.reminderTitle = null;
            this.stopAlarm();
            this.saveState();
        },
        setTimerPreset(s) {
            this.timer.seconds = Number(s) || 0;
            this.timer.running = false;
            this.armedUntil = null;
            this.reminderTitle = null;
            this.stopAlarm();
            this.saveState();
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
            this.open = false;
            this.modal.classList.remove('flex');
            this.modal.classList.add('hidden');
            this.saveState();
        },
        restore() {
            this.minimized = false;
            this.open = true;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
            this.saveState();
        },
        closeModal() {
            this.stopAlarm();
            this.timer.running = false;
            this.timer.seconds = 300;
            this.armedUntil = null;
            this.reminderTitle = null;
            this.clearState();
            this.destroyed();
        },
        mounted() {
            this.open = true;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
        },
        destroyed() {
            this.open = false;
            this.minimized = false;
            this.modal.classList.remove('flex');
            this.modal.classList.add('hidden');
        }
    };
}

