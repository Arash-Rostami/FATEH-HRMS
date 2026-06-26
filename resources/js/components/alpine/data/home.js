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
            const type = () => {
                if (i >= this.full.length) return;

                this.displayed = this.full.slice(0, i + 1);

                let delay = 26;
                let char = this.full.charAt(i);

                if (['.', '!', '؟', '?'].includes(char)) {
                    delay = 1000;
                } else if (['،', ',', ';'].includes(char)) {
                    delay = 500;
                }

                i++;
                setTimeout(type, delay);
            };
            type();
        },
        get connection() {
            return this.online ? { label: 'متصل', icon: 'wifi' } : { label: 'آفلاین', icon: 'wifi_off' };
        },
    };
}
