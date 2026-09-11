import highlightMatchMixin from '../mixins/highlightMatch.js';

export default function faq() {
    return {
        ...highlightMatchMixin(),
        active: null,
        view: 'list',

        init() {
            this.active = this.$wire.get('open');
            this.view = this.$wire.get('view') || 'list';
        },

        toggle(id) {
            this.active = (this.active === id) ? null : id;
        },
    };
}
