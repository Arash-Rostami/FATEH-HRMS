import maximizeMixin from "../mixins/maximize.js";

export default () => ({
    ...maximizeMixin(),
    activeId: null,
    loading: false,
    observer: null,
    showTimeline: false,
    maximizedFeed: null,

    feed(el) {
        return el?.closest('[data-feed]')?.dataset.feed;
    },

    toggleMaximize(name) {
        this.maximizedFeed = this.maximizedFeed === name ? null : name;
        this.applyMaximize(!!this.maximizedFeed);
    },

    init() {
        this.$nextTick(() => {
            this.setupScrollListener();
            this.setupInfiniteScroll();
            this.updateActiveItem();
        });


        Livewire.hook('morph', ({ el }) => {
            if (this.$root.contains(el)) {
                this.$nextTick(() => {
                    this.updateActiveItem();
                    this.observeTrigger();
                });
            }
        });
    },

    setupInfiniteScroll() {
        this.observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) this.loadMore();
        }, {
            root: null,
            threshold: 0,
            rootMargin: '200px',
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


    handleScroll() {
        const el = this.$refs.feedContainer;
        const scrollLeft = Math.abs(el.scrollLeft);
        const maxScroll = el.scrollWidth - el.clientWidth;
    },


    scrollNext() {
        this.$refs.timeline.scrollBy({ left: -this.$refs.timeline.offsetWidth, behavior: 'smooth' });
    },

    scrollPrev() {
        this.$refs.timeline.scrollBy({ left: this.$refs.timeline.offsetWidth, behavior: 'smooth' });
    },

    setupScrollListener() {
        const container = this.$refs.timeline;
        if (!container) return;

        let timeout;
        container.addEventListener('scroll', () => {
            if (timeout) return;
            timeout = setTimeout(() => {
                this.updateActiveItem();
                timeout = null;
            }, 50);
        }, { passive: true });
    },

    updateActiveItem() {
        const timeline = this.$refs.timeline;
        const feedContainer = this.$refs.feedContainer;
        if (!timeline || !feedContainer) return;

        const containerRect = timeline.getBoundingClientRect();
        const isDesktop = window.innerWidth >= 768;
        let closestId = null;
        let minDistance = Infinity;

        feedContainer.querySelectorAll('[data-feed-id]').forEach(item => {
            const rect = item.getBoundingClientRect();
            let distance;

            if (isDesktop) {
                const referencePoint = containerRect.right - containerRect.width * 0.1;
                distance = Math.abs(referencePoint - rect.right);
            } else {
                const containerCenter = containerRect.top + containerRect.height / 2;
                const itemCenter = rect.top + rect.height / 2;
                distance = Math.abs(containerCenter - itemCenter);
            }

            if (distance < minDistance) {
                minDistance = distance;
                closestId = item.dataset.feedId;
            }
        });

        if (closestId) this.activeId = closestId;
    },
})
