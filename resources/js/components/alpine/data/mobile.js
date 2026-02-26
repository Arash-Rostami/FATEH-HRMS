export default function mobile(initialPage, pageTwoKeys) {
    return {
        page: initialPage,
        isAtBottom: false,
        pageTwoKeys: pageTwoKeys,
        observer: null,

        toggle() {
            this.page = this.page === 0 ? 1 : 0;
        },

        syncPage() {
            this.page = this.pageTwoKeys.includes(this.activeTab) ? 1 : 0;
        },

        init() {
            const el = document.getElementById('footer') || document.querySelector('footer');

            if (el && window.IntersectionObserver) {
                this.observer = new IntersectionObserver(([e]) => {
                    this.isAtBottom = !!e?.isIntersecting;
                });
                this.observer.observe(el);
            }

            this.$watch('activeTab', () => this.syncPage());
        },

        destroy() {
            if (this.observer) {
                this.observer.disconnect();
            }
        }
    };
}
