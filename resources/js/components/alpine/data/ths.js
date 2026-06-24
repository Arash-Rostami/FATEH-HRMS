import settings from "./settings.js";

export default function ths() {
    return {

        max: false,

        toggleMaximize() {
            this.max = !this.max;

            ['footer', 'header', 'navbar'].forEach(id => {
                document.getElementById(id)
                    ?.classList.toggle('layout-hidden', this.max);
            });
        },
        openSearch: false,
        toggleSearch() {
            this.openSearch = !this.openSearch;
        },
        initPattern() {
            const settingInstance = settings();
            return settingInstance.initPattern();
        }
    }
}
