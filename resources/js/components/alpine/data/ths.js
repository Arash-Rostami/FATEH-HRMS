import maximizeMixin from "../mixins/maximize.js";
import clipboardMixin from "../mixins/clipboard.js";
import settings from "./settings.js";

export default function ths() {
    return {
        ...maximizeMixin(),
        ...clipboardMixin(),
        openSearch: false,
        init() {
            this.$wire.on('ths-reply-posted', () => {
                this.$nextTick(() => {
                    const el = document.getElementById('ths-reply-thread');
                    if (el) el.scrollTop = el.scrollHeight;
                });
            });
        },
        toggleSearch() {
            this.openSearch = !this.openSearch;
        },
        initPattern() {
            const settingInstance = settings();
            return settingInstance.initPattern();
        }
    }
}