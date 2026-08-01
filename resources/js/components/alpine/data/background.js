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

            const index = Alpine.store('background').tabsOrder.indexOf(tab);

            if (index !== -1) {
                this.previousIndex = initial ? null : this.activeIndex;
                this.activeIndex = index - 1;
            }
        },

        getClasses(index) {
            if (index === this.activeIndex) return 'animate-backdrop-crossfade-in animate-delay-200 w-[85%] mx-auto my-5';
            if (index === this.previousIndex) return 'animate-backdrop-crossfade-out z-0';

            return 'opacity-0 z-0';
        }
    }
}
