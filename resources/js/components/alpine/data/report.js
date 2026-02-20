export default function report(view, showModal) {
    return {
        view: view,
        showModal: showModal,
        activeId: null,
        activeReport: null,
        loading: false,
        observer: null,

        init() {
            this.$nextTick(() => {
                this.setupIntersectionObserver();
            });

            this.$watch('view', value => {
                this.$nextTick(() => {
                    this.setupIntersectionObserver();
                });
            });

            Livewire.hook('morph.updated', ({ component, el }) => {
                if (component.id === this.$wire.__instance.id) {
                    this.$nextTick(() => {
                        this.setupIntersectionObserver();
                    });
                }
            });
        },

        scrollNext() {
            if (this.$refs.reportContainer) {
                this.$refs.reportContainer.scrollBy({ left: -300, behavior: 'smooth' });
            }
        },

        scrollPrev() {
            if (this.$refs.reportContainer) {
                this.$refs.reportContainer.scrollBy({ left: 300, behavior: 'smooth' });
            }
        },

        handleScroll() {
            // Placeholder
        },

        setupIntersectionObserver() {
            if (this.observer) {
                this.observer.disconnect();
            }

            const options = {
                root: this.view === 'card' ? this.$refs.reportContainer : null,
                rootMargin: '200px',
                threshold: 0.1
            };

            this.observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && !this.loading) {
                    this.loadMore();
                }
            }, options);

            const trigger = this.$refs.loadTrigger;
            if (trigger) {
                this.observer.observe(trigger);
            }
        },

        async loadMore() {
            if (this.loading) return;
            this.loading = true;
            try {
                await this.$wire.loadMore();
            } catch (e) {
                console.error('Failed to load more reports', e);
            } finally {
                this.loading = false;
            }
        }
    }
}
