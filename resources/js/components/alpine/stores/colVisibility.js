const KEY = 'dms-col-hidden';
const STYLE_ID = 'dms-col-visibility-style';
const TABLE_SCOPE = '.dms-doc-table';

export default (Alpine) => {
    Alpine.store('colVisibility', {
        hidden: [],

        init() {
            try {
                this.hidden = JSON.parse(localStorage.getItem(KEY)) || [];
            } catch (e) {
                this.hidden = [];
            }
            if (!Array.isArray(this.hidden)) {
                this.hidden = [];
            }
            this.render();
        },

        isHidden(key) {
            return this.hidden.includes(key);
        },

        toggle(key) {
            if (this.hidden.includes(key)) {
                this.hidden = this.hidden.filter((k) => k !== key);
            } else {
                this.hidden.push(key);
            }
            this.persist();
            this.render();
        },

        reset() {
            this.hidden = [];
            this.persist();
            this.render();
        },

        persist() {
            localStorage.setItem(KEY, JSON.stringify(this.hidden));
        },

        render() {
            let style = document.getElementById(STYLE_ID);
            if (!style) {
                style = document.createElement('style');
                style.id = STYLE_ID;
                document.head.appendChild(style);
            }
            style.textContent = this.hidden
                .map((key) => `${TABLE_SCOPE} [data-col="${key}"]{display:none!important}`)
                .join('');
        },
    });
};