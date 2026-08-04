const ACTIVE_CLASSES = 'animate-backdrop-crossfade-in animate-delay-200 w-[85%] mx-auto my-5';
const PREVIOUS_CLASSES = 'animate-backdrop-crossfade-out z-0';
const HIDDEN_CLASSES = 'opacity-0 z-0';

export default function background() {
    return {
        activeIndex: 0,
        previousIndex: null,

        init() {
            if (this.$wire) {
                this.updateState(true);
                this.$watch('$wire.activeTab', () => this.updateState());
            }
        },

        updateState(initial = false) {
            const tab = this.$wire.activeTab;
            const tabsOrder = this.$store?.background?.tabsOrder;

            if (!Array.isArray(tabsOrder)) return;

            const index = tabsOrder.indexOf(tab);

            if (index !== -1) {
                this.previousIndex = initial ? null : this.activeIndex;
                this.activeIndex = index - 1;
            }
        },

        getClasses(index) {
            if (index === this.activeIndex) return ACTIVE_CLASSES;
            if (index === this.previousIndex) return PREVIOUS_CLASSES;

            return HIDDEN_CLASSES;
        }
    };
}
