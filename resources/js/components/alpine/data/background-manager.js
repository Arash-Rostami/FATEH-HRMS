export default function backgroundManager() {
    return {
        activeIndex: 0,
        direction: 'up',
        backgroundEnabled: localStorage.getItem('backgroundEnabled') === 'true',
        images: [
            '/images/backdrop1.png',
            '/images/backdrop2.png',
            '/images/backdrop3.png',
            '/images/backdrop4.png',
            '/images/backdrop5.png',
            '/images/backdrop6.png',
            '/images/backdrop7.png',
            '/images/backdrop8.png'
        ],
        tabsOrder: [
            'overview',
            'dashboard', // This maps to Posts component
            'calendar',
            'gallery',
            'share',
            'analytics',
            'profile',
            'help'
        ],

        init() {
            // Listen for background toggle event from settings
            window.addEventListener('background-toggled', (e) => {
                this.backgroundEnabled = e.detail;
            });

            // Initial setup
            if (this.$wire) {
                this.updateState();

                this.$watch('$wire.activeTab', () => this.updateState());
                this.$watch('$wire.direction', () => this.updateState());
            }
        },

        updateState() {
            const tab = this.$wire.activeTab;
            this.direction = this.$wire.direction;

            const index = this.tabsOrder.indexOf(tab);
            if (index !== -1) {
                this.activeIndex = index;
            }
        },

        // Helper for transition classes
        getClasses(index) {
            if (index === this.activeIndex) {
                return 'opacity-40 translate-y-0 scale-100 z-10';
            }

            // Determine exit direction based on movement
            // If moving down list (direction='up'), old item slides up
            // If moving up list (direction='down'), old item slides down

            let transform = '';

            if (this.direction === 'up') {
                 // Content moves UP, so entering comes from bottom, leaving goes to top
                 // Wait, direction 'up' usually means "scroll down", so content moves up.
                 if (index < this.activeIndex) {
                     // Previous items move up (leave to top)
                     transform = '-translate-y-full scale-95';
                 } else {
                     // Next items (not active) are below
                     transform = 'translate-y-full scale-95';
                 }
            } else {
                // Direction 'down'
                if (index > this.activeIndex) {
                    // Next items move down (leave to bottom)
                    transform = 'translate-y-full scale-95';
                } else {
                    // Previous items are above
                    transform = '-translate-y-full scale-95';
                }
            }

            return `opacity-0 ${transform} z-0`;
        }
    }
}
