import maximizeMixin from "../mixins/maximize.js";
import aiWriterMixin from "../mixins/aiWriter.js";
import clipboardMixin from "../mixins/clipboard.js";
import tableToolsMixin from "../mixins/tableTools.js";
import settings from "./settings.js";

const THREAD_ID = 'ths-reply-thread';
const PANE_SELECTOR = '[data-ths-pane]';

const scrollToEnd = () => {
    const el = document.getElementById(THREAD_ID);
    const pane = el?.closest(PANE_SELECTOR) ?? el;
    if (pane) pane.scrollTop = pane.scrollHeight;
};

export default function ths() {
    return {
        ...maximizeMixin('ths'),
        ...aiWriterMixin(),
        ...clipboardMixin(),
        ...tableToolsMixin('ths'),
        init() {
            this.initMaximize();
            this.initAiWriter();
            this.initTableTools();
            this.$wire.on('ths-reply-posted', () => this.$nextTick(scrollToEnd));
        },
        destroy() {
            this.destroyTableTools();
        },
        initPattern() {
            return settings().initPattern();
        }
    }
}