export default function maximizeMixin() {
    return {
        max: false,
        toggleMaximize() {
            this.max = !this.max;
            ['footer', 'header', 'navbar'].forEach(id => {
                document.getElementById(id)?.classList.toggle('layout-hidden', this.max);
            });
            // Useful for charts
            if (this.$nextTick) {
                this.$nextTick(() => window.dispatchEvent(new Event('resize')));
            }
        }
    };
}
