const REPORT_ID_SELECTOR = '[data-report-id]';
const MOBILE_BREAKPOINT = 768;
const INIT_TIMER_DELAY = 100;
const INTERSECTION_THRESHOLD = 0.1;
const INTERSECTION_ROOT_MARGIN = '0px 200px 0px 200px';
const SCROLLER_SELECTOR = '.overflow-y-auto';
const FILTER_SELECTOR = '[x-data^="filters"]';
const SCROLL_TOP_THRESHOLD = 30;
const SCROLL_HIDE_DELTA = 10;
const SCROLL_SHOW_DELTA = -10;

export default function report() {
    return {
        showModal: false,
        activeReport: null,
        view: null,
        activeId: null,
        showTimeline: false,
        loading: false,
        observer: null,

        _isDestroyed: false,
        _enforce: null,
        _initTimer: null,
        _observer: null,
        _timelineEl: null,
        _timelineHandler: null,
        _timelineRaf: null,
        _scrollerEl: null,
        _scrollerHandler: null,
        _scrollerRaf: null,
        _morphHook: null,
        _mutationPending: false,

        init() {
            this._isDestroyed = false;
            this.view = this.$wire.get('view');

            this._enforce = () => {
                if (window.innerWidth < MOBILE_BREAKPOINT && this.view !== 'list') {
                    this.view = 'list';
                    this.$wire.call('toggleView', 'list');
                }
            };

            this._enforce();
            window.addEventListener('resize', this._enforce, { passive: true });

            this.$nextTick(() => {
                this.setupScrollListener();
                this.setupFilterCompact();
                this.setupIntersectionObserver();
                this._initTimer = setTimeout(() => this.updateActiveItem(), INIT_TIMER_DELAY);

                this._observer = new MutationObserver(() => {
                    if (this._mutationPending) return;
                    this._mutationPending = true;
                    this.$nextTick(() => {
                        this._mutationPending = false;
                        if (!this._isDestroyed) this.updateActiveItem();
                    });
                });
                this._observer.observe(this.$root, { childList: true, subtree: true });
            });

            this._morphHook = Livewire.hook('morph', ({ component, el }) => {
                if (this._isDestroyed) return;
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
            this._isDestroyed = true;

            if (typeof this._morphHook === 'function') {
                this._morphHook();
                this._morphHook = null;
            }

            if (this._enforce) {
                window.removeEventListener('resize', this._enforce);
                this._enforce = null;
            }

            if (this._initTimer) {
                clearTimeout(this._initTimer);
                this._initTimer = null;
            }

            if (this._timelineRaf) {
                window.cancelAnimationFrame(this._timelineRaf);
                this._timelineRaf = null;
            }

            if (this._scrollerRaf) {
                window.cancelAnimationFrame(this._scrollerRaf);
                this._scrollerRaf = null;
            }

            if (this._observer) {
                this._observer.disconnect();
                this._observer = null;
            }

            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }

            if (this._timelineEl && this._timelineHandler) {
                this._timelineEl.removeEventListener('scroll', this._timelineHandler);
                this._timelineEl = null;
                this._timelineHandler = null;
            }

            if (this._scrollerEl && this._scrollerHandler) {
                this._scrollerEl.removeEventListener('scroll', this._scrollerHandler);
                this._scrollerEl = null;
                this._scrollerHandler = null;
            }
        },

        setupScrollListener() {
            const container = this.$refs.timeline;
            if (!container) return;

            this._timelineEl = container;
            this._timelineHandler = () => {
                if (this._timelineRaf) window.cancelAnimationFrame(this._timelineRaf);
                this._timelineRaf = window.requestAnimationFrame(() => this.updateActiveItem());
            };
            container.addEventListener('scroll', this._timelineHandler, { passive: true });
        },

        setupIntersectionObserver() {
            if (!this.$refs.loadTriggerCard) return;
            if (this.observer) this.observer.disconnect();

            this.observer = new IntersectionObserver((entries) => {
                if (entries[0]?.isIntersecting && !this.loading && this.$wire.hasMorePages) {
                    this.loadMore();
                }
            }, {
                root: this.$refs.timeline,
                threshold: INTERSECTION_THRESHOLD,
                rootMargin: INTERSECTION_ROOT_MARGIN
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
            const scroller = this.$root.closest(SCROLLER_SELECTOR);
            if (!scroller) return;
            const filterEl = this.$root.querySelector(FILTER_SELECTOR);
            if (!filterEl) return;

            let lastY = scroller.scrollTop;
            this._scrollerEl = scroller;

            this._scrollerHandler = () => {
                if (this._scrollerRaf) return;

                this._scrollerRaf = window.requestAnimationFrame(() => {
                    this._scrollerRaf = null;

                    const y = scroller.scrollTop;
                    const d = y - lastY;
                    const data = Alpine.$data(filterEl);

                    if (!data) return;

                    if (y < SCROLL_TOP_THRESHOLD) {
                        data.compact = false;
                    } else if (d > SCROLL_HIDE_DELTA) {
                        data.compact = true;
                        data.showFilters = false;
                    } else if (d < SCROLL_SHOW_DELTA) {
                        data.compact = false;
                    }
                    lastY = y;
                });
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

            const items = container.querySelectorAll(REPORT_ID_SELECTOR);
            for (let i = 0, len = items.length; i < len; i++) {
                const rect = items[i].getBoundingClientRect();
                const distance = Math.abs(referencePoint - rect.right);

                if (distance < minDistance) {
                    minDistance = distance;
                    closestId = items[i].dataset.reportId;
                }
            }

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
