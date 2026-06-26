export default function greeting(fullText) {
    return {
        full: fullText,
        displayed: '',
        key: 'header_greeting',

        init() {
            if (sessionStorage.getItem(this.key) === this.full) {
                this.displayed = this.full;
                return;
            }
            sessionStorage.setItem(this.key, this.full);
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
        }
    }
}
