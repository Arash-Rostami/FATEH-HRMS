const LAYOUT_ELEMENT_IDS = ['footer', 'header', 'navbar'];
const LAYOUT_HIDDEN_CLASS = 'layout-hidden';
const MAX_WIDGET_CLOSE_MS = 350;

export default function maximizeMixin() {
    return {
        max: false,
        maxLeaving: false,

        applyMaximize(active) {
            for (let i = 0, len = LAYOUT_ELEMENT_IDS.length; i < len; i++) {
                document.getElementById(LAYOUT_ELEMENT_IDS[i])?.classList.toggle(LAYOUT_HIDDEN_CLASS, active);
            }
            document.getElementById('content-shell')?.classList.toggle('maximized', active);

            if (typeof this.$nextTick === 'function') {
                this.$nextTick(() => window.dispatchEvent(new Event('resize')));
            }
        },

        toggleMaximize() {
            if (this.max) {
                this.maxLeaving = true;
                this.applyMaximize(false);
                setTimeout(() => {
                    this.max = false;
                    this.maxLeaving = false;
                }, MAX_WIDGET_CLOSE_MS);
                return;
            }

            this.max = true;
            this.applyMaximize(true);
        }
    };
}
