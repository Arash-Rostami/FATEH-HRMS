const GREET_KEY = 'header_greeting';
const BASE_DELAY = 26;
const SENTENCE_DELAY = 1000;
const PAUSE_DELAY = 500;
const SENTENCE_MARKS = new Set(['.', '!', '؟', '?']);
const PAUSE_MARKS = new Set(['،', ',', ';']);

export default function greeting(fullText = '') {
    return {
        full: fullText,
        displayed: '',
        _typeTimeout: null,

        init() {
            if (!this.full) return;

            if (this._typeTimeout) {
                clearTimeout(this._typeTimeout);
                this._typeTimeout = null;
            }

            try {
                if (sessionStorage.getItem(GREET_KEY) === this.full) {
                    this.displayed = this.full;
                    return;
                }
                sessionStorage.setItem(GREET_KEY, this.full);
            } catch (e) {}

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

        destroy() {
            if (this._typeTimeout) {
                clearTimeout(this._typeTimeout);
                this._typeTimeout = null;
            }
        }
    };
}
