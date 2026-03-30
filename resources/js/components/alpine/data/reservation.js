export default () => ({
    init() {
        if (typeof Alpine !== 'undefined' && Alpine.store('settings')) {
            const bg = Alpine.store('settings').backgroundPattern;
            if (bg) {
                this.$el.style.backgroundImage = bg;
            }
        }
    },

    scrollToTop() {
        window.scrollTo({top: 0, behavior: 'smooth'});
    },

    scrollNext(btnEl) {
        const container = btnEl.previousElementSibling;
        if (container) {
            container.scrollBy({left: -250, behavior: 'smooth'});
        }
    },

    scrollPrev(btnEl) {
        const container = btnEl.nextElementSibling;
        if (container) {
            container.scrollBy({left: 250, behavior: 'smooth'});
        }
    }
});
