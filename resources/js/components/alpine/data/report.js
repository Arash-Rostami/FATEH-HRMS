export default function report() {
    return {
        showModal: false,
        activeReport: null,
        view: null,
        loading: false,
        observer: null,

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

            this.$nextTick(() => this.setupInfiniteScroll());

            Livewire.hook('morph', ({el}) => {
                if (this.$root.contains(el)) {
                    this.$nextTick(() => this.observeTriggers());
                }
            });
        },

        setupInfiniteScroll() {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach((e) => {
                    if (e.isIntersecting) this.loadMore();
                });
            }, {
                root: null,
                threshold: 0,
                rootMargin: '300px',
            });
            this.observeTriggers();
        },

        observeTriggers() {
            if (!this.observer) return;
            this.observer.disconnect();
            [this.$refs.loadTriggerCard, this.$refs.loadTriggerList]
                .filter(Boolean)
                .forEach((el) => this.observer.observe(el));
        },

        async loadMore() {

            if (this.loading) return;
            this.loading = true;
            try {
                await this.$wire.loadMore();
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        scrollNext() {
            this.$refs.reportContainer.scrollBy({left: -350, behavior: 'smooth'});
        },

        scrollPrev() {
            this.$refs.reportContainer.scrollBy({left: 350, behavior: 'smooth'});
        },
    };
}
