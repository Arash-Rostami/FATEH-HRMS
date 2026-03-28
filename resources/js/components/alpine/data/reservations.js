export default () => ({
    init() {
        // Enforce specific layout setups if required on initialization
        console.log('Reservations MD3 component initialized');
    },

    openFilter() {
        this.openFilterModal = true;
    },

    closeFilter() {
        this.openFilterModal = false;
    },

    scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});
