import maximizeMixin from "../mixins/maximize.js";
import clipboardMixin from "../mixins/clipboard.js";
import tableToolsMixin from "../mixins/tableTools.js";
import settings from "./settings.js";

export default function ths() {
    return {
        ...maximizeMixin('ths'),
        ...clipboardMixin(),
        ...tableToolsMixin('ths'),
        init() {
            this.initMaximize();
            this.initTableTools();
            this.$wire.on('ths-reply-posted', () => {
                this.$nextTick(() => {
                    const el = document.getElementById('ths-reply-thread');
                    if (el) el.scrollTop = el.scrollHeight;
                });
            });
        },
        destroy() {
            this.destroyTableTools();
        },
        initPattern() {
            const settingInstance = settings();
            return settingInstance.initPattern();
        }
    }
}