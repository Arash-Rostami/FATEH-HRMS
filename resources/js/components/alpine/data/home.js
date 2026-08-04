const GREET_KEY = 'header_greeting';
const BASE_DELAY = 26;
const SENTENCE_DELAY = 1000;
const PAUSE_DELAY = 500;
const SENTENCE_MARKS = new Set(['.', '!', '؟', '?']);
const PAUSE_MARKS = new Set(['،', ',', ';']);
const CONNECTION_ONLINE = { label: 'متصل', icon: 'wifi' };
const CONNECTION_OFFLINE = { label: 'آفلاین', icon: 'wifi_off' };

export default function home(greetingText = '') {
    return {
        full: greetingText,
        displayed: '',
        online: navigator.onLine,
        _typeTimeout: null,
        _onlineListener: null,
        _offlineListener: null,

        init() {
            this.typeGreeting();

            this._onlineListener = () => { this.online = true; };
            this._offlineListener = () => { this.online = false; };

            window.addEventListener('online', this._onlineListener);
            window.addEventListener('offline', this._offlineListener);
        },

        typeGreeting() {
            if (!this.full) return;

            if (this._typeTimeout) {
                clearTimeout(this._typeTimeout);
                this._typeTimeout = null;
            }

            if (sessionStorage.getItem(GREET_KEY) === this.full) {
                this.displayed = this.full;
                return;
            }

            sessionStorage.setItem(GREET_KEY, this.full);

            let i = 0;
            const type = () => {
                if (i >= this.full.length) return;

                this.displayed = this.full.slice(0, i + 1);

                const char = this.full[i];
                let delay = BASE_DELAY;

                if (SENTENCE_MARKS.has(char)) {
                    delay = SENTENCE_DELAY;
                } else if (PAUSE_MARKS.has(char)) {
                    delay = PAUSE_DELAY;
                }

                i++;
                this._typeTimeout = setTimeout(type, delay);
            };

            type();
        },

        get connection() {
            return this.online ? CONNECTION_ONLINE : CONNECTION_OFFLINE;
        },

        destroy() {
            if (this._typeTimeout) {
                clearTimeout(this._typeTimeout);
                this._typeTimeout = null;
            }

            if (this._onlineListener) {
                window.removeEventListener('online', this._onlineListener);
                this._onlineListener = null;
            }

            if (this._offlineListener) {
                window.removeEventListener('offline', this._offlineListener);
                this._offlineListener = null;
            }
        }
    };
}
