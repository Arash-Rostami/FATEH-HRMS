export default function backgroundManager() {
    return {
        activeIndex: 0,
        direction: 'up',

        init() {
            if (this.$wire) {
                this.updateState();

                this.$watch('$wire.activeTab', () => this.updateState());
                this.$watch('$wire.direction', () => this.updateState());
            }
        },

        updateState() {
            const tab = this.$wire.activeTab;
            this.direction = this.$wire.direction;

            // Access store directly
            const index = Alpine.store('background').tabsOrder.indexOf(tab);
            if (index !== -1) {
                this.activeIndex = index;
            }
        },

        getClasses(index) {
            if (index === this.activeIndex) {
                return 'opacity-40 translate-y-0 scale-100 z-10';
            }

            let transform = '';

            if (this.direction === 'up') {
                 if (index < this.activeIndex) {
                     transform = '-translate-y-full scale-95';
                 } else {
                     transform = 'translate-y-full scale-95';
                 }
            } else {
                if (index > this.activeIndex) {
                    transform = 'translate-y-full scale-95';
                } else {
                    transform = '-translate-y-full scale-95';
                }
            }

            return `opacity-0 ${transform} z-0`;
        }
    }
}
