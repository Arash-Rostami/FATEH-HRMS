export default () => ({
    openFilterModal: false,
    selectedType: 'seat',
    selectedDate: null,

    init() {
        console.log('Reservation component initialized');
    },

    openFilter() {
        this.openFilterModal = true;
    },

    closeFilter() {
        this.openFilterModal = false;
    },

    confirmFilter() {
        this.closeFilter();
        // Trigger Livewire event or model update
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('refresh-resources', { type: this.selectedType, date: this.selectedDate });
        }
    }
});
