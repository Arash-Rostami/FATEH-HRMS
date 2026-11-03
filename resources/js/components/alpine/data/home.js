const GREET_KEY = 'header_greeting';
const BASE_DELAY = 26;
const SENTENCE_DELAY = 1000;
const PAUSE_DELAY = 500;
const SENTENCE_MARKS = new Set(['.', '!', '؟', '?']);
const PAUSE_MARKS = new Set(['،', ',', ';']);
const CONNECTION_ONLINE = { label: 'متصل', icon: 'wifi' };
const CONNECTION_OFFLINE = { label: 'آفلاین', icon: 'wifi_off' };
const CONNECTION_LAN = { label: 'فقط شبکه داخلی', icon: 'lan' };

const splitGraphemes = (text) => (typeof Intl !== 'undefined' && Intl.Segmenter
    ? Array.from(new Intl.Segmenter(undefined, { granularity: 'grapheme' }).segment(text), (s) => s.segment)
    : Array.from(text));

export default function home(greetingText = '') {
    return {
        full: greetingText,
        displayed: '',
        online: navigator.onLine,
        lan: false,
        _typeTimeout: null,
        _onlineListener: null,
        _offlineListener: null,
        _lanListener: null,
        _visibilityListener: null,

        init() {
            this.typeGreeting();

            this._visibilityListener = () => { if (document.hidden) this.finishGreeting(); };
            document.addEventListener('visibilitychange', this._visibilityListener);

            this._onlineListener = () => { this.online = true; this.lan = false; };
            this._offlineListener = () => { this.online = false; };
            this._lanListener = () => { this.lan = true; };

            window.addEventListener('online', this._onlineListener);
            window.addEventListener('offline', this._offlineListener);
            window.addEventListener('internet-unreachable', this._lanListener);
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

            const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;
            if (reduced || document.hidden) {
                this.displayed = this.full;
                return;
            }

            const chars = splitGraphemes(this.full);
            let i = 0;
            const type = () => {
                if (i >= chars.length) return;

                this.displayed = chars.slice(0, i + 1).join('');

                const char = chars[i];
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

        finishGreeting() {
            if (this._typeTimeout) {
                clearTimeout(this._typeTimeout);
                this._typeTimeout = null;
            }
            if (this.full) this.displayed = this.full;
        },

        get connection() {
            if (!this.online) return CONNECTION_OFFLINE;
            return this.lan ? CONNECTION_LAN : CONNECTION_ONLINE;
        },

        destroy() {
            if (this._typeTimeout) {
                clearTimeout(this._typeTimeout);
                this._typeTimeout = null;
            }

            if (this._visibilityListener) {
                document.removeEventListener('visibilitychange', this._visibilityListener);
                this._visibilityListener = null;
            }

            if (this._onlineListener) {
                window.removeEventListener('online', this._onlineListener);
                this._onlineListener = null;
            }

            if (this._offlineListener) {
                window.removeEventListener('offline', this._offlineListener);
                this._offlineListener = null;
            }

            if (this._lanListener) {
                window.removeEventListener('internet-unreachable', this._lanListener);
                this._lanListener = null;
            }
        }
    };
}
