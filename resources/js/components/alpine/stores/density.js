const KEY = 'app-density';
const COMPACT_CLASS = 'app-density-compact';

const root = document.documentElement;

export default (Alpine) => {
    Alpine.store('density', {
        compact: false,

        init() {
            this.compact = localStorage.getItem(KEY) === 'compact';
            root.classList.toggle(COMPACT_CLASS, this.compact);
        },

        toggle() {
            this.compact = !this.compact;
            localStorage.setItem(KEY, this.compact ? 'compact' : 'comfortable');
            root.classList.toggle(COMPACT_CLASS, this.compact);
        }
    });
};
