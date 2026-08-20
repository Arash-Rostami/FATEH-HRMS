export default function monthFilterMixin(itemSelector) {
    return {
        month: '',
        visibleCount: 0,

        refreshVisible() {
            let n = 0;
            const elements = this.$root.querySelectorAll(itemSelector);
            for (let i = 0, len = elements.length; i < len; i++) {
                if (elements[i].offsetParent !== null) n++;
            }
            this.visibleCount = n;
        },

        watchMonth() {
            this.$watch('month', () => this.$nextTick(() => this.refreshVisible()));
        },
    };
}
