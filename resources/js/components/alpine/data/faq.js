export default () => ({
    active: null,
    showDepartments: false,

    toggle(id) {
        this.active = (this.active === id) ? null : id
    },

    isActive(id) {
        return this.active === id
    },

    toggleDepartments() {
        this.showDepartments = !this.showDepartments
    }
})
