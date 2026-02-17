export default () => ({
    activeId: null,
    loading: false,
    observer: null,

    init() {
        this.$nextTick(() => {
            this.setupScrollListener();
            this.setupIntersectionObserver();
            this.updateActiveItem();
        });

        Livewire.hook('morph.updated', ({ component, el }) => {
            if (component.id === this.$wire.__instance.id) {
                this.$nextTick(() => {
                    this.updateActiveItem();
                    if (this.$refs.loadTrigger && this.observer) {
                        this.observer.disconnect();
                        this.observer.observe(this.$refs.loadTrigger);
                    }
                });
            }
        });
    },

    scrollNext() {
        this.$refs.feedContainer.scrollBy({ left: -this.$refs.timeline.offsetWidth, behavior: 'smooth' });
    },
    scrollPrev() {
        this.$refs.feedContainer.scrollBy({ left: this.$refs.timeline.offsetWidth, behavior: 'smooth' });
    },

    handleScroll() {
        const el = this.$refs.feedContainer;
        const scrollLeft = Math.abs(el.scrollLeft);
        const maxScroll = el.scrollWidth - el.clientWidth;
    },

    setupScrollListener() {
        const container = this.$refs.feedContainer;
        if (!container) return;

        let timeout;
        container.addEventListener('scroll', () => {
            if (timeout) return;
            timeout = setTimeout(() => {
                this.updateActiveItem();
                timeout = null;
            }, 50); // 50ms throttle
        }, { passive: true });
    },

    setupIntersectionObserver() {
        if (this.observer) this.observer.disconnect();

        this.observer = new IntersectionObserver((entries) => {

            if (entries[0].isIntersecting && !this.loading && this.$wire.hasMorePages) {
                this.loadMore();
            }
        }, {
            root: this.$refs.feedContainer,
            threshold: 0.1, // Trigger when 10% visible
            rootMargin: '0px 0px 200px 0px' // Pre-load slightly before end
        });

        if (this.$refs.loadTrigger) {
            this.observer.observe(this.$refs.loadTrigger);
        }
    },

    updateActiveItem() {
        const container = this.$refs.feedContainer;
        if (!container) return;

        const containerRect = container.getBoundingClientRect();
        const isDesktop = window.innerWidth >= 768;

        let closestId = null;
        let minDistance = Infinity;

        // Find all feed items
        const items = container.querySelectorAll('[data-feed-id]');

        items.forEach(item => {
            const rect = item.getBoundingClientRect();
            let distance;

            if (isDesktop) {
                // Horizontal Layout (RTL)
                // Distance from Right edge of container
                distance = Math.abs(containerRect.right - rect.right);
            } else {

                const containerCenter = containerRect.top + (containerRect.height / 2);
                const itemCenter = rect.top + (rect.height / 2);
                distance = Math.abs(containerCenter - itemCenter);
            }

            if (distance < minDistance) {
                minDistance = distance;
                closestId = item.dataset.feedId;
            }
        });

        if (closestId) {
            this.activeId = closestId;
        }
    },

    async loadMore() {
        if (this.loading) return;
        this.loading = true;
        try {
            await this.$wire.loadMore();
        } catch (e) {
            console.error('Failed to load more feeds', e);
        } finally {
            this.loading = false;
        }
    }
})
