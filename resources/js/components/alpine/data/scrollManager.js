export default function scrollManager() {
    return {
        isVisible: true,
        lastY: 0,
        ticking: false,
        scrollHandler: null,

        init() {
            this.scrollHandler = () => {
                if (!this.ticking) {
                    window.requestAnimationFrame(() => this.update());
                    this.ticking = true;
                }
            };

            window.addEventListener('scroll', this.scrollHandler, { passive: true });
        },

        update() {
            const currentY = window.scrollY;
            this.isVisible = currentY < 50 || currentY < this.lastY;
            this.lastY = currentY;
            this.ticking = false;
        },

        destroy() {
            if (this.scrollHandler) {
                window.removeEventListener('scroll', this.scrollHandler);
            }
        }
    };
}
