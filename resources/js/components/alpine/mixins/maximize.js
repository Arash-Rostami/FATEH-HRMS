export default function maximizeMixin() {
    return {
        max: false,
        applyMaximize(active) {
            ['footer', 'header', 'navbar'].forEach(id => {
                document.getElementById(id)?.classList.toggle('layout-hidden', active);
            });
            if (this.$nextTick) {
                this.$nextTick(() => window.dispatchEvent(new Event('resize')));
            }
        },
        toggleMaximize() {
            this.max = !this.max;
            this.applyMaximize(this.max);
        }
    };
}