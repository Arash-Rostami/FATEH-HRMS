import settings from "./settings.js";

export default function ths() {
    return {
        initPattern() {
            const settingInstance = settings();
            return settingInstance.initPattern();
        }
    }
}
