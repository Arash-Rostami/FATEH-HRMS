const LAYOUT_ELEMENT_IDS = ['footer', 'header', 'navbar'];
const LAYOUT_HIDDEN_CLASS = 'layout-hidden';

export default function maximizeMixin() {
    return {
        max: false,

        applyMaximize(active) {
            for (let i = 0, len = LAYOUT_ELEMENT_IDS.length; i < len; i++) {
                document.getElementById(LAYOUT_ELEMENT_IDS[i])?.classList.toggle(LAYOUT_HIDDEN_CLASS, active);
            }

            if (typeof this.$nextTick === 'function') {
                this.$nextTick(() => window.dispatchEvent(new Event('resize')));
            }
        },

        toggleMaximize() {
            this.max = !this.max;
            this.applyMaximize(this.max);
        }
    };
}
