export default function timer() {
    return {
        time: '',
        date: '',
        mode: localStorage.getItem('timer-mode') || 'fa',

        init() {
            this.updateTime();
            setInterval(() => this.updateTime(), 1000);
        },

        toggleMode() {
            this.mode = this.mode === 'fa' ? 'en' : 'fa';
            localStorage.setItem('timer-mode', this.mode);
            this.updateTime();
        },

        updateTime() {
            const now = new Date();

            if (this.mode === 'fa') {
                // Persian Date & Time
                const dateOptions = { year: 'numeric', month: 'long', day: 'numeric', calendar: 'persian', numberingSystem: 'arab' };
                const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, numberingSystem: 'arab' };

                this.date = new Intl.DateTimeFormat('fa-IR', dateOptions).format(now);
                this.time = new Intl.DateTimeFormat('fa-IR', timeOptions).format(now);
            } else {
                // English Date & Time
                const dateOptions = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
                const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };

                this.date = new Intl.DateTimeFormat('en-US', dateOptions).format(now);
                this.time = new Intl.DateTimeFormat('en-US', timeOptions).format(now);
            }
        }
    };
}
