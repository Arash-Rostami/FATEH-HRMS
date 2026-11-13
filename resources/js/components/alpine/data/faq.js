import highlightMatchMixin from '../mixins/highlightMatch.js';

export default function faq() {
    return {
        ...highlightMatchMixin(),
        active: null,
        view: 'list',
        onlySaved: false,

        init() {
            this.active = this.$wire.get('open');
            this.view = this.$wire.get('view') || 'list';
        },

        toggle(id, el) {
            this.active = (this.active === id) ? null : id;
            if (this.active === id) setTimeout(() => el?.closest('[data-rf]')?.scrollIntoView({behavior: 'smooth', block: 'nearest'}), 320);
        },
    };
}
