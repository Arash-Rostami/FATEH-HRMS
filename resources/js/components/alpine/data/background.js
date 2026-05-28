export default function background() {
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

            const index = Alpine.store('background').tabsOrder.indexOf(tab);

            if (index !== -1) {
                this.activeIndex = index - 1;
            }
        },

        getClasses(index) {
            if (index === this.activeIndex) return 'opacity-[35%] translate-y-0 scale-100 w-[85%] mx-auto my-5';

            let transform = '';

            if (this.direction === 'up') {
                if (index < this.activeIndex) {
                    transform = '-translate-y-full scale-75';
                } else {
                    transform = 'translate-y-full scale-75';
                }
            } else {
                if (index > this.activeIndex) {
                    transform = 'translate-y-full scale-75';
                } else {
                    transform = '-translate-y-full scale-75';
                }
            }

            return `opacity-0 ${transform} z-0`;
        }
    }
}
