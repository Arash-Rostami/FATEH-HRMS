const ACTIVE_CLASSES = 'animate-backdrop-crossfade-in animate-delay-200';
const PREVIOUS_CLASSES = 'animate-backdrop-crossfade-out z-0';
const HIDDEN_CLASSES = 'opacity-0 z-0';

const timeOfDayKey = () => {
    const hour = new Date().getHours();
    if (hour >= 5 && hour <= 11) return 'morning';
    if (hour >= 12 && hour <= 16) return 'noon';
    if (hour >= 17 && hour <= 19) return 'evening';
    return 'night';
};

export default function background() {
    return {
        activeIndex: 0,
        previousIndex: null,

        init() {
            const store = this.$store?.background;

            if (store?.backdropMode === 'time') {
                const images = store.images || [];
                const key = timeOfDayKey();
                const index = images.findIndex((url) => url.includes(key));
                this.activeIndex = index !== -1 ? index : 0;
                return;
            }

            if (this.$wire) {
                this.updateState(true);
                this.$watch('$wire.activeTab', () => this.updateState());
            }
        },

        updateState(initial = false) {
            const tabsOrder = this.$store?.background?.tabsOrder;

            if (!Array.isArray(tabsOrder)) {
                return;
            }

            const index = tabsOrder.indexOf(this.$wire.activeTab);

            if (index !== -1) {
                this.previousIndex = initial ? null : this.activeIndex;
                this.activeIndex = index - 1;
            }
        },

        getClasses(index) {
            if (index === this.activeIndex) return ACTIVE_CLASSES;
            if (index === this.previousIndex) return PREVIOUS_CLASSES;

            return HIDDEN_CLASSES;
        },

        get _baseFilter() {
            const store = this.$store?.background;
            if (!store) return '';

            let filter = '';

            if (store.blur) filter += `blur(${store.blur}px) `;
            if (store.brightness !== undefined && store.brightness !== 1) filter += `brightness(${store.brightness}) `;
            if (store.contrast !== undefined && store.contrast !== 1) filter += `contrast(${store.contrast}) `;
            if (store.grayscale) filter += `grayscale(${store.grayscale})`;

            return filter.trim();
        },

        imageStyle(index) {
            const store = this.$store?.background;
            if (!store) return {};

            const style = { backgroundImage: `url(${store.images?.[index] ?? ''})` };
            const filter = this._baseFilter;

            if (filter) {
                style.filter = filter;
            }

            if (index === this.activeIndex) {
                const w = store.width;
                if (w && w !== '100%') {
                    style.width = w;
                    style.marginInline = 'auto';
                    style.marginBlock = '1.25rem';
                }
            }

            return style;
        }
    };
}
