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
                this.displayed = this.full.slice(0, ++i);
                if (i < this.full.length) setTimeout(type, 26);
            };
            type();
        }
    }
}
