const KEY = 'app-density';
const COMPACT_CLASS = 'app-density-compact';
const VAL_COMPACT = 'compact';
const VAL_COMFORT = 'comfortable';

const rootClasses = document.documentElement.classList;

export default function densityStore(Alpine) {
    Alpine.store('density', {
        compact: false,

        init() {
            let isCompact = false;
            try {
                isCompact = localStorage.getItem(KEY) === VAL_COMPACT;
            } catch (e) {}

            this.compact = isCompact;

            if (isCompact) {
                rootClasses.add(COMPACT_CLASS);
            } else {
                rootClasses.remove(COMPACT_CLASS);
            }
        },

        toggle() {
            const next = !this.compact;
            this.compact = next;

            try {
                localStorage.setItem(KEY, next ? VAL_COMPACT : VAL_COMFORT);
            } catch (e) {}

            if (next) {
                rootClasses.add(COMPACT_CLASS);
            } else {
                rootClasses.remove(COMPACT_CLASS);
            }
        }
    });
}
