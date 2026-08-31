export default (Alpine) => {
    Alpine.store('activityReactionPicker', {
        entryId: null,

        open(id) {
            this.entryId = id;
        },

        close() {
            this.entryId = null;
        },

        is(id) {
            return this.entryId === id;
        }
    });
};
