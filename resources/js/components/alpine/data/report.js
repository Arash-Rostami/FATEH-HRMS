
export default function report() {
    return {
        showModal: false,
        activeReport: null,
        view: null,

        init() {
            this.view = this.$wire.get('view');

            const enforce = () => {
                if (window.innerWidth < 768 && this.view !== 'list') {
                    this.view = 'list';
                    this.$wire.call('toggleView', 'list');
                }
            };

            enforce();
            window.addEventListener('resize', enforce);
        },

        scrollNext() {
            this.$refs.reportContainer.scrollBy({ left: -350, behavior: 'smooth' });
        },

        scrollPrev() {
            this.$refs.reportContainer.scrollBy({ left: 350, behavior: 'smooth' });
        },
    };
}
