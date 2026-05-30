export default function home(greetingText = '') {
    return {
        full: greetingText,
        displayed: '',
        greetKey: 'header_greeting',
        online: navigator.onLine,

        init() {
            this.typeGreeting();
            window.addEventListener('online',  () => (this.online = true));
            window.addEventListener('offline', () => (this.online = false));
        },
        typeGreeting() {
            if (!this.full) return;
            if (sessionStorage.getItem(this.greetKey) === this.full) { this.displayed = this.full; return; }
            sessionStorage.setItem(this.greetKey, this.full);
            let i = 0;
            const type = () => { this.displayed = this.full.slice(0, ++i); if (i < this.full.length) setTimeout(type, 26); };
            type();
        },
        get connection() {
            return this.online ? { label: 'متصل', icon: 'wifi' } : { label: 'آفلاین', icon: 'wifi_off' };
        },
    };
}
