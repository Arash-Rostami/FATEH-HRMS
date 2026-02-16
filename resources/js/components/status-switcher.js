export default () => ({
    open: false,

    toggle() {
        this.open = !this.open;
    },

    select(status) {
        this.$wire.changeStatus(status);
        this.open = false;
    }
});
