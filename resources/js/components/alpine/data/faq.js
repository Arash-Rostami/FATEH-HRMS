export default () => ({
    active: null,

    toggle(id) {
        this.active = (this.active === id) ? null : id
    },

    isActive(id) {
        return this.active === id
    }
})
