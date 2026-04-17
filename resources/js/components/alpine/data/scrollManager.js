export default function scrollManager() {
    return {
        isVisible: true,
        lastY: 0,
        ticking: false,
        scrollHandler: null,

        init() {
            this.scrollHandler = () => {
                if (!this.ticking) {
                    requestAnimationFrame(() => this.update());
                    this.ticking = true;
                }
            };
            window.addEventListener('scroll', this.scrollHandler, { passive: true });
        },

        update() {
            const y = window.scrollY;
            const delta = y - this.lastY;

            if (y < 60)             this.isVisible = true;
            else if (delta > 4)     this.isVisible = false;
            else if (delta < -6)    this.isVisible = true;

            this.lastY = y;
            this.ticking = false;
        },

        destroy() {
            window.removeEventListener('scroll', this.scrollHandler);
        }
    };
}
