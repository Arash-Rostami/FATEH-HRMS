export default (Alpine) => {
    // Glob import for static analysis by Vite
    const bgImages = import.meta.glob('/resources/assets/img/bg/*.png', { eager: true, as: 'url' });

    // Convert object to array sorted by filename
    const images = Object.keys(bgImages)
        .sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }))
        .map(key => bgImages[key]);

    Alpine.store('background', {
        enabled: localStorage.getItem('backgroundEnabled') === 'true',

        images: images,

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
            window.dispatchEvent(new CustomEvent('background-toggled', { detail: value }));
        },

        init() {
            window.addEventListener('background-toggled', (e) => {
                this.enabled = e.detail;
            });
        }
    })
}
