import maximizeMixin from "../mixins/maximize.js";
import monthFilterMixin from "../mixins/monthFilter.js";

const FEED_SELECTOR = '[data-feed]';
const FEED_ID_SELECTOR = '[data-feed-id]';
const DESKTOP_BREAKPOINT = 768;
const SCROLL_THROTTLE_MS = 50;
const OBSERVER_ROOT_MARGIN = '200px';

export default () => ({
    ...maximizeMixin(),
    ...monthFilterMixin(FEED_SELECTOR),
    activeId: null,
    loading: false,
    observer: null,
    showTimeline: false,
    view: 'filmstrip',
    maximizedFeed: null,

    _isDestroyed: false,
    _scrollListener: null,
    _scrollTimeout: null,
    _morphHook: null,

    feed(el) {
        return el?.closest(FEED_SELECTOR)?.dataset.feed;
    },

    toggleMaximize(name) {
        this.maximizedFeed = this.maximizedFeed === name ? null : name;
        this.applyMaximize(!!this.maximizedFeed);
    },

    init() {
        this._isDestroyed = false;
        this.view = this.$wire.get('view') || 'filmstrip';
        this.watchMonth();

        this.$nextTick(() => {
            this.setupScrollListener();
            this.setupInfiniteScroll();
            this.updateActiveItem();
            this.refreshVisible();
        });

        this._morphHook = Livewire.hook('morph', ({ el }) => {
            if (this._isDestroyed) return;
            if (this.$root?.contains(el)) {
                this.$nextTick(() => {
                    this.updateActiveItem();
                    this.observeTrigger();
                    this.refreshVisible();
                });
            }
        });
    },

    setupInfiniteScroll() {
        this.observer = new IntersectionObserver((entries) => {
            if (entries[0]?.isIntersecting) this.loadMore();
        }, {
            root: null,
            threshold: 0,
            rootMargin: OBSERVER_ROOT_MARGIN,
        });
        this.observeTrigger();
    },

    observeTrigger() {
        if (!this.observer) return;
        this.observer.disconnect();
        if (this.$refs.loadTrigger) this.observer.observe(this.$refs.loadTrigger);
    },

    async loadMore() {
        if (this.loading || !this.$wire.get('hasMorePages')) return;
        this.loading = true;
        try {
            await this.$wire.loadMore();
        } catch (e) {
            console.error(e);
        } finally {
            this.loading = false;
        }
    },

    async focusFeed(id) {
        try {
            await this.$wire.focusRecord(id);
            this.view = 'filmstrip';
        } catch (e) {
            console.error(e);
        }
    },

    scrollNext() {
        const timeline = this.$refs.timeline;
        if (!timeline) return;
        timeline.scrollBy({ left: -timeline.offsetWidth, behavior: 'smooth' });
    },

    scrollPrev() {
        const timeline = this.$refs.timeline;
        if (!timeline) return;
        timeline.scrollBy({ left: timeline.offsetWidth, behavior: 'smooth' });
    },

    setupScrollListener() {
        const container = this.$refs.timeline;
        if (!container) return;

        this._scrollListener = () => {
            if (this._scrollTimeout) return;
            this._scrollTimeout = setTimeout(() => {
                this._scrollTimeout = null;
                this.updateActiveItem();
            }, SCROLL_THROTTLE_MS);
        };

        container.addEventListener('scroll', this._scrollListener, { passive: true });
    },

    updateActiveItem() {
        const timeline = this.$refs.timeline;
        const feedContainer = this.$refs.feedContainer;
        if (!timeline || !feedContainer) return;

        const containerRect = timeline.getBoundingClientRect();
        const isDesktop = window.innerWidth >= DESKTOP_BREAKPOINT;
        const items = feedContainer.querySelectorAll(FEED_ID_SELECTOR);

        let closestId = null;
        let minDistance = Infinity;

        if (isDesktop) {
            const referencePoint = containerRect.right - containerRect.width * 0.1;
            for (let i = 0, len = items.length; i < len; i++) {
                const rect = items[i].getBoundingClientRect();
                const distance = Math.abs(referencePoint - rect.right);
                if (distance < minDistance) {
                    minDistance = distance;
                    closestId = items[i].dataset.feedId;
                }
            }
        } else {
            const containerCenter = containerRect.top + containerRect.height / 2;
            for (let i = 0, len = items.length; i < len; i++) {
                const rect = items[i].getBoundingClientRect();
                const itemCenter = rect.top + rect.height / 2;
                const distance = Math.abs(containerCenter - itemCenter);
                if (distance < minDistance) {
                    minDistance = distance;
                    closestId = items[i].dataset.feedId;
                }
            }
        }

        if (closestId) this.activeId = closestId;
    },

    destroy() {
        this._isDestroyed = true;

        if (typeof this._morphHook === 'function') {
            this._morphHook();
            this._morphHook = null;
        }

        if (this._scrollTimeout) {
            clearTimeout(this._scrollTimeout);
            this._scrollTimeout = null;
        }

        const timeline = this.$refs?.timeline;
        if (timeline && this._scrollListener) {
            timeline.removeEventListener('scroll', this._scrollListener);
            this._scrollListener = null;
        }

        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }
    }
});
