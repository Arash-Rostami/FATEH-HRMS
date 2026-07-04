export default function report() {
    return {
        showModal: false,
        activeReport: null,
        view: null,
        activeId: null,
        showTimeline: false,
        loading: false,
        observer: null,

        init() {
            this.view = this.$wire.get('view');

            this._enforce = () => {
                if (window.innerWidth < 768 && this.view !== 'list') {
                    this.view = 'list';
                    this.$wire.call('toggleView', 'list');
                }
            };

            this._enforce();
            window.addEventListener('resize', this._enforce);

            this.$nextTick(() => {
                this.setupScrollListener();
                this.setupFilterCompact();
                this.setupIntersectionObserver();
                this._initTimer = setTimeout(() => this.updateActiveItem(), 100);
                this._observer = new MutationObserver(() => this.$nextTick(() => this.updateActiveItem()));
                this._observer.observe(this.$root, { childList: true, subtree: true });
            });

            Livewire.hook('morph', ({ component, el }) => {
                if (component.id === this.$wire.__instance.id) {
                    this.$nextTick(() => {
                        if (this.$refs.loadTriggerCard) {
                            this.setupIntersectionObserver();
                        }
                    });
                }
            });
        },

        destroy() {
            window.removeEventListener('resize', this._enforce);
            if (this._initTimer) clearTimeout(this._initTimer);
            if (this._observer) this._observer.disconnect();
            if (this.observer) this.observer.disconnect();
            if (this._timelineEl && this._timelineHandler) {
                this._timelineEl.removeEventListener('scroll', this._timelineHandler);
            }
            if (this._scrollerEl && this._scrollerHandler) {
                this._scrollerEl.removeEventListener('scroll', this._scrollerHandler);
            }
        },

        setupScrollListener() {
            const container = this.$refs.timeline;
            if (!container) return;

            let timeout;
            this._timelineEl = container;
            this._timelineHandler = () => {
                if (timeout) window.cancelAnimationFrame(timeout);
                timeout = window.requestAnimationFrame(() => this.updateActiveItem());
            };
            container.addEventListener('scroll', this._timelineHandler, { passive: true });
        },

        setupIntersectionObserver() {
            if (!this.$refs.loadTriggerCard) return;
            if (this.observer) this.observer.disconnect();

            this.observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && !this.loading && this.$wire.hasMorePages) {
                    this.loadMore();
                }
            }, {
                root: this.$refs.timeline,
                threshold: 0.1,
                rootMargin: '0px 200px 0px 200px'
            });

            this.observer.observe(this.$refs.loadTriggerCard);
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

        setupFilterCompact() {
            const scroller = this.$root.closest('.overflow-y-auto');
            if (!scroller) return;
            const filterEl = this.$root.querySelector('[x-data^="filters"]');
            if (!filterEl) return;

            let lastY = scroller.scrollTop;
            this._scrollerEl = scroller;
            this._scrollerHandler = () => {
                const y = scroller.scrollTop, d = y - lastY;
                const data = Alpine.$data(filterEl);
                if (!data) return;
                if (y < 30) { data.compact = false; }
                else if (d > 10) { data.compact = true; data.showFilters = false; }
                else if (d < -10) { data.compact = false; }
                lastY = y;
            };
            scroller.addEventListener('scroll', this._scrollerHandler, { passive: true });
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

        scrollNext() {
            const el = this.$refs.timeline;
            if (!el) return;
            el.scrollBy({ left: -el.offsetWidth, behavior: 'smooth' });
        },

        scrollPrev() {
            const el = this.$refs.timeline;
            if (!el) return;
            el.scrollBy({ left: el.offsetWidth, behavior: 'smooth' });
        },
    };
}