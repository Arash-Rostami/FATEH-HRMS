document.addEventListener('alpine:init', () => {
    Alpine.data('linksCarousel', () => ({
        hasOverflow: false,
        canScrollLeft: false,
        canScrollRight: false,

        init() {
            this.checkScroll();
            this.$watch('hasOverflow', () => this.checkScroll());
        },

        checkScroll() {
            const el = this.$refs.container;
            if (!el) return;

            this.hasOverflow = el.scrollWidth > el.clientWidth;

            // In RTL, scrollLeft is typically negative or starts at max
            // We'll use simple bounds checking
            const scrollLeft = Math.abs(el.scrollLeft);
            const maxScroll = el.scrollWidth - el.clientWidth;

            // Allow small pixel buffer for float inaccuracies
            this.canScrollRight = scrollLeft < (maxScroll - 1);
            this.canScrollLeft = scrollLeft > 1;
        },

        scrollLeft() {
            this.$refs.container.scrollBy({ left: -300, behavior: 'smooth' });
            setTimeout(() => this.checkScroll(), 350);
        },

        scrollRight() {
            this.$refs.container.scrollBy({ left: 300, behavior: 'smooth' });
            setTimeout(() => this.checkScroll(), 350);
        }
    }));
});
