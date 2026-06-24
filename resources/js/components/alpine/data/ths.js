import maximizeMixin from "../mixins/maximize.js";
import settings from "./settings.js";

export default function ths() {
    return {
        ...maximizeMixin(),

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
