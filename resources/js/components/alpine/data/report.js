const REPORT_ID_SELECTOR = '[data-report-id]';
const ATTR_REPORT_ID = 'data-report-id';
const MOBILE_BREAKPOINT = 768;
const INIT_TIMER_DELAY = 100;
const SCROLLER_SELECTOR = '.overflow-y-auto';
const FILTER_SELECTOR = '[x-data^="filters"]';
const SCROLL_TOP_THRESHOLD = 30;
const SCROLL_HIDE_DELTA = 10;
const SCROLL_SHOW_DELTA = -10;
const RECENT_KEY = 'fateh_reports_recent';
const RECENT_MAX = 6;

export default function report() {
    return {
        showModal: false,
        activeReport: null,
        view: null,
        activeId: null,
        showTimeline: false,
        loading: false,
        recent: [],

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

            try {
                const saved = localStorage.getItem(RECENT_KEY);
                if (saved && saved.length > 2) {
                    const parsed = JSON.parse(saved);
                    this.recent = Array.isArray(parsed) ? parsed : [];
                }
            } catch {
                this.recent = [];
            }

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

            this._morphHook = Livewire.hook('morph', ({ component }) => {
                if (this._isDestroyed) return;
                if (component.id === this.$wire.__instance.id) {
                    this.$nextTick(() => this.updateActiveItem());
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
                cancelAnimationFrame(this._timelineRaf);
                this._timelineRaf = null;
            }

            if (this._scrollerRaf) {
                cancelAnimationFrame(this._scrollerRaf);
                this._scrollerRaf = null;
            }

            if (this._observer) {
                this._observer.disconnect();
                this._observer = null;
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
                if (this._timelineRaf) cancelAnimationFrame(this._timelineRaf);
                this._timelineRaf = requestAnimationFrame(() => {
                    this._timelineRaf = null;
                    this.updateActiveItem();
                });
            };
            container.addEventListener('scroll', this._timelineHandler, { passive: true });
        },

        async loadMore() {
            if (this.loading) return;
            this.loading = true;
            try {
                await this.$wire.loadMore();
            } catch {}
            this.loading = false;
        },

        recordOpen(item) {
            if (!item || !item.id) return;

            const arr = this.recent;
            const id = item.id;
            const len = arr.length;

            for (let i = 0; i < len; i++) {
                if (arr[i].id === id) {
                    arr.splice(i, 1);
                    break;
                }
            }

            arr.unshift({ id, payload: item });

            if (arr.length > RECENT_MAX) {
                arr.length = RECENT_MAX;
            }

            queueMicrotask(() => {
                try {
                    localStorage.setItem(RECENT_KEY, JSON.stringify(this.recent));
                } catch {}
            });
        },

        openRecent(item) {
            this.activeReport = item.payload || item;
            this.showModal = true;
        },

        clearRecent() {
            this.recent = [];
            queueMicrotask(() => {
                try {
                    localStorage.removeItem(RECENT_KEY);
                } catch {}
            });
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

                this._scrollerRaf = requestAnimationFrame(() => {
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
            const referencePoint = viewportRect.right - (viewportRect.width * 0.1);

            let closestId = null;
            let minDistance = Infinity;

            const items = container.querySelectorAll(REPORT_ID_SELECTOR);
            const len = items.length;

            for (let i = 0; i < len; i++) {
                const item = items[i];
                const rect = item.getBoundingClientRect();

                const distance = referencePoint - rect.right;
                const absDist = distance < 0 ? -distance : distance;

                if (absDist < minDistance) {
                    minDistance = absDist;
                    closestId = item.getAttribute(ATTR_REPORT_ID);
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
        }
    };
}
