export default function stopwatch(mp3) {
    return {
        timer: {running: false, seconds: 300},
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
            setInterval(() => {
                if (this.timer.running && this.timer.seconds > 0) {
                    this.timer.seconds--;
                    if (this.timer.seconds === 0) {
                        this.timer.running = false;
                        this.startAlarmLoop();
                    }
                }
            }, 1000);
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
            if (this.timer.running) this.stopAlarm();
        },
        resetTimer() {
            this.timer.running = false;
            this.timer.seconds = 300;
            this.stopAlarm();
        },
        setTimerPreset(s) {
            this.timer.seconds = Number(s) || 0;
            this.timer.running = false;
            this.stopAlarm();
        },
        formatSeconds(s) {
            s = Number(s || 0);
            const mm = Math.floor(s / 60).toString().padStart(2, '0');
            const ss = Math.floor(s % 60).toString().padStart(2, '0');
            return `${mm}:${ss}`;
        },
        minimize() {
            this.minimized = true;
            this.open = false;
            this.modal.classList.remove('flex');
            this.modal.classList.add('hidden');
        },
        restore() {
            this.minimized = false;
            this.open = true;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
        },
        closeModal() {
            this.stopAlarm();
            this.destroyed();
        },
        mounted() {
            this.open = true;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
        },
        destroyed() {
            this.open = false;
            this.modal.classList.remove('flex');
            this.modal.classList.add('hidden');
        }
    };
}

