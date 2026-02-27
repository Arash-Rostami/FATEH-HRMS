export default function mobile(initialPage, pageTwoKeys, activeTabEntangle) {
    return {
        activeTab: activeTabEntangle,
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

            this.('activeTab', () => this.syncPage());

            this.(() => {
                if (this.observer) this.observer.disconnect();
            });
        }
    };
}
