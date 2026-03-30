import settings from "./settings.js";

export default () => ({
    initPattern() {
        const settingInstance = settings();
        return settingInstance.initPattern();
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
