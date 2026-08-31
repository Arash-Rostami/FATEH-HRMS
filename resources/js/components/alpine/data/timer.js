const FORMATTERS = {};

function formattersFor(mode) {
    if (!FORMATTERS[mode]) {
        FORMATTERS[mode] = mode === 'fa'
            ? {
                date: new Intl.DateTimeFormat('fa-IR', { year: 'numeric', month: 'long', day: 'numeric', calendar: 'persian', numberingSystem: 'arab' }),
                time: new Intl.DateTimeFormat('fa-IR', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, numberingSystem: 'arab' }),
            }
            : {
                date: new Intl.DateTimeFormat('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }),
                time: new Intl.DateTimeFormat('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }),
            };
    }
    return FORMATTERS[mode];
}

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
            const f = formattersFor(this.mode);

            this.date = f.date.format(now);
            this.time = f.time.format(now);
        }
    };
}
