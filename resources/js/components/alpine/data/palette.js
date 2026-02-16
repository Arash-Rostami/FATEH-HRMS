export default function palette() {
    return {
        open: false,
        selectedIndex: 0,

        init() {
            this.$watch('open', value => {
                if (value) {
                    this.selectedIndex = 0;
                    this.$nextTick(() => this.$refs.searchInput.focus());
                }
            });
        },

        toggle() {
            this.open = !this.open;
        },

        next() {
            if (this.$wire.results && this.$wire.results.length > 0) {
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.$wire.results.length - 1);
            }
        },

        prev() {
            this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
        },

        select() {
            if (this.$wire.results && this.$wire.results.length > 0) {
                const result = this.$wire.results[this.selectedIndex];
                if (result && result.action) {
                    this.open = false;
                }
            }
        }
    }
}
