import mentionMixin from '../mixins/mention.js';

export default function projectComposer(value, names) {
    return {
        ...mentionMixin(),
        value,
        _names: names || [],
        body: '',
        query: '',
        open: false,
        fullscreen: false,
        maxLength: 4000,
        _candidatesCache: null,
        _filteredCache: [],
        _filteredQuery: null,
        _notifyCache: [],
        _notifyBody: null,

        init() {
            this.body = this.value || '';
            this.$watch('value', (v) => { this.body = v || ''; });
        },

        get candidates() {
            if (!this._candidatesCache) {
                const list = this._names;
                const out = [];
                for (let i = 0, len = list.length; i < len; i++) {
                    const item = list[i];
                    const name = typeof item === 'string' ? item : (item?.name || '');
                    if (name) out.push(name);
                }
                this._candidatesCache = out;
            }
            return this._candidatesCache;
        },

        get filtered() {
            if (this._filteredQuery !== this.query) {
                this._filteredQuery = this.query;
                this._filteredCache = this.mentionFilter(this.candidates, this.query);
            }
            return this._filteredCache;
        },

        get willNotify() {
            if (this._notifyBody !== this.body) {
                this._notifyBody = this.body;
                const candidates = this.candidates;
                const out = [];
                for (let i = 0, len = candidates.length; i < len; i++) {
                    if (this.mentionIsNotified(this.body, candidates[i])) out.push(candidates[i]);
                }
                this._notifyCache = out;
            }
            return this._notifyCache;
        },

        get counterTone() {
            const ratio = (this.body || '').length / this.maxLength;
            if (ratio >= 1) return 'var(--md-sys-color-error)';
            if (ratio >= 0.9) return 'var(--tool-gold-color)';
            return 'var(--md-sys-color-on-surface-variant)';
        },

        onInput(e) {
            this.$dispatch('activity-typed');
            this.value = e.target.value;
            const t = this.mentionAtTerm(this.value, e.target.selectionStart);
            if (!t) { this.open = false; return; }
            this.query = t.term;
            this.open = this.filtered.length > 0;
        },

        pick(name) {
            const ta = this.$refs.composer;
            const r = this.mentionBuild(this.value, ta.selectionStart, name);
            if (!r) { this.open = false; return; }
            this.value = r.value;
            this.open = false;
            this.$nextTick(() => {
                ta.focus();
                ta.setSelectionRange(r.caret, r.caret);
            });
        },
    };
}
