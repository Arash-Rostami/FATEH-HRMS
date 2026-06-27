export default function report() {
    return {
        showModal: false,
        activeReport: null,
        view: null,
        loading: false,
        observer: null,
        activeId: null,
        showTimeline: false,

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

            this.$nextTick(() => {
                this.setupInfiniteScroll();
                this.setupScrollListener();
                setTimeout(() => this.updateActiveItem(), 100);
            });

            Livewire.hook('morph', ({ el }) => {
                if (this.$root.contains(el)) {
                    this.$nextTick(() => {
                        this.observeTriggers();
                        this.updateActiveItem();
                    });
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

        setupScrollListener() {
            const container = this.$refs.timeline;
            if (!container) return;

            let timeout;
            container.addEventListener('scroll', () => {
                if (timeout) window.cancelAnimationFrame(timeout);
                timeout = window.requestAnimationFrame(() => {
                    this.updateActiveItem();
                });
            }, { passive: true });
        },

        updateActiveItem() {
            const container = this.$refs.reportContainer;
            const viewport = this.$refs.timeline;
            if (!container || !viewport) return;

            const viewportRect = viewport.getBoundingClientRect();
            const referencePoint = viewportRect.right - viewportRect.width * 0.1;

            let closestId = null;
            let minDistance = Infinity;

            container.querySelectorAll('[data-report-id]').forEach(item => {
                const rect = item.getBoundingClientRect();
                const distance = Math.abs(referencePoint - rect.right);

                if (distance < minDistance) {
                    minDistance = distance;
                    closestId = item.dataset.reportId;
                }
            });

            if (closestId && this.activeId !== closestId) {
                this.activeId = closestId;
            }
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
            this.$refs.timeline.scrollBy({ left: -this.$refs.timeline.offsetWidth, behavior: 'smooth' });
        },

        scrollPrev() {
            this.$refs.timeline.scrollBy({ left: this.$refs.timeline.offsetWidth, behavior: 'smooth' });
        },
    };
}
