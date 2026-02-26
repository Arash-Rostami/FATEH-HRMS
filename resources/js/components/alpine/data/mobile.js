export default function mobile(initialPage, pageTwoKeys, activeTab) {
    return {
        activeTab: activeTab,
        page: initialPage,
        isAtBottom: false,
        pageTwoKeys: pageTwoKeys,
        observer: null,

        toggle() {
            this.page = this.page === 0 ? 1 : 0;
        },

        syncPage() {
            this.page = this.pageTwoKeys.includes(String(this.activeTab)) ? 1 : 0;
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

            this.$cleanup(() => {
                if (this.observer) this.observer.disconnect();
            });
        }
    };
}
