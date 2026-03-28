export default () => ({
    init() {
        // Enforce specific layout setups if required on initialization
        console.log('Reservations Smart MD3 component initialized');

        // Apply global settings or backgrounds if configured in your main Alpine setup
        if (typeof Alpine !== 'undefined' && Alpine.store('settings')) {
             const bg = Alpine.store('settings').backgroundPattern;
             if(bg) {
                 this.$el.style.backgroundImage = bg;
             }
        }
    },

    scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    // Add interactions for horizontally scrolling large date/time lists
    // Note: in RTL, scrolling right means moving towards the end of the list (positive scrollLeft)
    // Actually, in many browsers RTL scrollLeft goes negative or behaves differently.
    // Using `scrollBy` with positive/negative values usually handles the direction visually.
    scrollLeft(ref) {
        if(this.$refs[ref]) {
             this.$refs[ref].scrollBy({ left: -250, behavior: 'smooth' });
        }
    },

    scrollRight(ref) {
        if(this.$refs[ref]) {
             this.$refs[ref].scrollBy({ left: 250, behavior: 'smooth' });
        }
    }
});
