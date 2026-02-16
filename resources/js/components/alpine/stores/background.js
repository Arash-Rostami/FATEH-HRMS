export default (Alpine) => {
    Alpine.store('background', {
        enabled: localStorage.getItem('backgroundEnabled') === 'true',

        images: [
            '/assets/img/bg/backdrop1.png',
            '/assets/img/bg/backdrop2.png',
            '/assets/img/bg/backdrop3.png',
            '/assets/img/bg/backdrop4.png',
            '/assets/img/bg/backdrop5.png',
            '/assets/img/bg/backdrop6.png',
            '/assets/img/bg/backdrop7.png',
            '/assets/img/bg/backdrop8.png'
        ],

        tabsOrder: [
            'overview',
            'dashboard', // Maps to Posts
            'calendar',
            'gallery',
            'share',
            'analytics',
            'profile',
            'help'
        ],

        toggle(value) {
            this.enabled = value;
            localStorage.setItem('backgroundEnabled', value);
            // Dispatch event for components that might listen directly
            window.dispatchEvent(new CustomEvent('background-toggled', { detail: value }));
        },

        init() {
            // Sync with potential external changes if needed
            window.addEventListener('background-toggled', (e) => {
                this.enabled = e.detail;
            });
        }
    })
}
