export default function links() {
    return {
        hasOverflow: false,

        init() {
            this.checkScroll();
            this.$watch('hasOverflow', () => this.checkScroll());
        },

        scrollLeft() {
            this.$refs.container.scrollBy({left: -300, behavior: 'smooth'});
            setTimeout(() => this.checkScroll(), 350);
        },

        scrollRight() {
            this.$refs.container.scrollBy({left: 300, behavior: 'smooth'});
            setTimeout(() => this.checkScroll(), 350);
        },

        checkScroll() {
            const el = this.$refs.container;
            if (!el) return;
            this.hasOverflow = el.scrollWidth > el.clientWidth;
        }
    }
}
