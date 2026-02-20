export default function report() {
    return {
        activeId: null,
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
                // RTL: Scroll left (negative) to see next items (assuming standard RTL)
                this.$refs.reportContainer.scrollBy({ left: -300, behavior: 'smooth' });
            }
        },

        scrollPrev() {
            if (this.$refs.reportContainer) {
                // RTL: Scroll right (positive) to see previous items
                this.$refs.reportContainer.scrollBy({ left: 300, behavior: 'smooth' });
            }
        },

        setupIntersectionObserver() {
            if (this.observer) {
                this.observer.disconnect();
            }

            // Using null (viewport) as root for list view, and container for card view
            // NOTE: For infinite scroll sentinel to work inside overflow container, root MUST be null or the container.
            const options = {
                root: null, // Often safer to use viewport unless very specific container clipping needed
                rootMargin: '200px',
                threshold: 0.1
            };

            this.observer = new IntersectionObserver((entries) => {
                // Ensure we only trigger if intersecting AND not currently loading
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
