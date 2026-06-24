export default function home(greetingText = '') {
    return {
        full: greetingText,
        displayed: '',
        greetKey: 'header_greeting',
        greetTimestampKey: 'header_greeting_time',
        online: navigator.onLine,

        init() {
            this.typeGreeting();
            window.addEventListener('online',  () => (this.online = true));
            window.addEventListener('offline', () => (this.online = false));
        },
        typeGreeting() {
            if (!this.full) return;

            const lastTime = localStorage.getItem(this.greetTimestampKey);
            const now = new Date().getTime();
            // 5 minutes = 300000 milliseconds
            const shouldSkip = lastTime && (now - parseInt(lastTime, 10)) < 300000;

            if (shouldSkip && localStorage.getItem(this.greetKey) === this.full) {
                this.displayed = this.full;
                return;
            }

            localStorage.setItem(this.greetKey, this.full);
            localStorage.setItem(this.greetTimestampKey, now.toString());

            let i = 0;
            const type = () => {
                this.displayed = this.full.slice(0, ++i);
                if (i < this.full.length) setTimeout(type, 50);
            };
            type();
        },
        get connection() {
            return this.online ? { label: 'متصل', icon: 'wifi' } : { label: 'آفلاین', icon: 'wifi_off' };
        },
    };
}
