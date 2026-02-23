export default function scrollManager() {
    return {
        isVisible: true,
        lastY: 0,
        ticking: false,

        init() {
            const handler = () => {
                if (!this.ticking) {
                    window.requestAnimationFrame(() => this.update());
                    this.ticking = true;
                }
            };

            window.addEventListener('scroll', handler, { passive: true });
            this.$cleanup(() => window.removeEventListener('scroll', handler));
        },

        update() {
            const currentY = window.scrollY;
            this.isVisible = currentY < 50 || currentY < this.lastY;
            this.lastY = currentY;
            this.ticking = false;
        }
    };
}
