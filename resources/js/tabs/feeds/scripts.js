export default () => ({
    activeId: null,
    loading: false,
    deleteModalOpen: false,
    commentIdToDelete: null,
    observer: null,

    init() {
        // Wait for Alpine to settle
        this.$nextTick(() => {
            this.setupScrollListener();
            this.setupIntersectionObserver();
            this.updateActiveItem();
        });

        // Watch for Livewire updates to re-check active item or re-observe
        Livewire.hook('morph.updated', ({ component, el }) => {
            if (component.id === this.$wire.__instance.id) {
                this.$nextTick(() => {
                    this.updateActiveItem();
                    // Re-observe the sentinel if it was re-rendered
                    if (this.$refs.loadTrigger && this.observer) {
                        this.observer.disconnect();
                        this.observer.observe(this.$refs.loadTrigger);
                    }
                });
            }
        });
    },

    setupScrollListener() {
        const container = this.$refs.feedContainer;
        if (!container) return;

        // Throttle scroll event
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
            // Check if intersecting AND not loading AND has more pages
            // We rely on Livewire property 'hasMorePages'
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
                // We want the item whose center is closest to the container's center?
                // OR "First item appears on RIGHT. The currently focused (right-most visible snap item)".
                // In RTL, scroll starts at Right. The "Start" edge is Right.
                // Snap alignment is typically 'start' (Right in RTL).
                // So we check distance between Item's Right edge and Container's Right edge.

                // Distance from Right edge of container
                distance = Math.abs(containerRect.right - rect.right);
            } else {
                // Vertical Layout (Mobile)
                // Center alignment is usually best for mobile feed snap
                // Distance from center of container
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
    },

    // Modal Actions
    confirmDelete(commentId) {
        this.commentIdToDelete = commentId;
        this.deleteModalOpen = true;
    },

    cancelDelete() {
        this.deleteModalOpen = false;
        setTimeout(() => {
            this.commentIdToDelete = null;
        }, 300); // Wait for transition
    },

    deleteComment() {
        if (!this.commentIdToDelete) return;

        this.$wire.deleteComment(this.commentIdToDelete).then(() => {
            this.cancelDelete();
        });
    }
})
