import { feedReactions } from './emoji.js';

export default (Alpine) => {
    Alpine.store('activityReactionPicker', {
        entryId: null,
        top: 0,
        right: 0,
        reactions: feedReactions,

        open(id, el) {
            this.entryId = id;
            const rect = el?.getBoundingClientRect();
            if (rect) {
                this.top = rect.bottom + 6;
                this.right = window.innerWidth - rect.right;
            }
        },

        close() {
            this.entryId = null;
        },

        is(id) {
            return this.entryId === id;
        }
    });
};
