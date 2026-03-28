export default () => ({
    openFilterModal: false,
    selectedType: 'seat',
    selectedDate: null,

    init() {
        // Initialize reservations component logic
    },

    openFilter() {
        this.openFilterModal = true;
    },

    closeFilter() {
        this.openFilterModal = false;
    },

    confirmFilter() {
        this.closeFilter();
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('refresh-resources', { type: this.selectedType, date: this.selectedDate });
        }
    }
});
