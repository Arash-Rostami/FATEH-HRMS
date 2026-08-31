import monthFilterMixin from "../mixins/monthFilter.js";

const PHOTO_SELECTOR = '[data-photo-id]';

export default function gallery() {
    return {
        ...monthFilterMixin(PHOTO_SELECTOR),
        activeId: null,
        loading: false,
        showTimeline: false,
        view: 'filmstrip',
        previewTimer: null,

        _isDestroyed: false,
        _initTimeout: null,
        _scrollListener: null,
        _scrollRaf: null,
        _morphHook: null,

        init() {
            this._isDestroyed = false;
            this.view = this.$wire.get('view') || 'filmstrip';
            this.watchMonth();

            this.$nextTick(() => {
                this.setupScrollListener();
                this.refreshVisible();

                this._initTimeout = setTimeout(() => {
                    this.updateActiveItem();
                }, 100);
            });

            this._morphHook = Livewire.hook('morph', ({ component }) => {
                if (this._isDestroyed) return;
                if (component.id === this.$wire.__instance.id) {
                    this.$nextTick(() => {
                        this.updateActiveItem();
                        this.refreshVisible();
                    });
                }
            });
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
                if (this._scrollRaf) window.cancelAnimationFrame(this._scrollRaf);
                this._scrollRaf = window.requestAnimationFrame(() => {
                    this.updateActiveItem();
                });
            };

            container.addEventListener('scroll', this._scrollListener, { passive: true });
        },

        updateActiveItem() {
            const container = this.$refs.galleryContainer;
            const viewport = this.$refs.timeline;
            if (!container || !viewport) return;

            const viewportRect = viewport.getBoundingClientRect();
            const referencePoint = viewportRect.right - viewportRect.width * 0.1;

            let closestId = null;
            let minDistance = Infinity;

            const items = container.querySelectorAll(PHOTO_SELECTOR);
            for (let i = 0, len = items.length; i < len; i++) {
                const item = items[i];
                const rect = item.getBoundingClientRect();
                const distance = Math.abs(referencePoint - rect.right);

                if (distance < minDistance) {
                    minDistance = distance;
                    closestId = item.dataset.photoId;
                }
            }

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

        galleryPreviewPlay(el) {
            if (!el || el.tagName !== 'VIDEO') return;
            try {
                el.currentTime = 0;
                const p = el.play();
                if (p && typeof p.then === 'function') p.catch(() => {});
                if (this.previewTimer) clearTimeout(this.previewTimer);
                this.previewTimer = setTimeout(() => {
                    try { el.pause(); } catch (e) {}
                }, 1500);
            } catch (e) {}
        },

        galleryPreviewStop(el) {
            if (this.previewTimer) {
                clearTimeout(this.previewTimer);
                this.previewTimer = null;
            }
            if (!el || el.tagName !== 'VIDEO') return;
            try {
                el.pause();
                el.currentTime = 0.1;
            } catch (e) {}
        },

        formatDuration(seconds) {
            if (!seconds || !isFinite(seconds)) return '';
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return m + ':' + (s < 10 ? '0' : '') + s;
        },

        destroy() {
            this._isDestroyed = true;

            if (typeof this._morphHook === 'function') {
                this._morphHook();
                this._morphHook = null;
            }

            if (this._initTimeout) {
                clearTimeout(this._initTimeout);
                this._initTimeout = null;
            }

            if (this.previewTimer) {
                clearTimeout(this.previewTimer);
                this.previewTimer = null;
            }

            if (this._scrollRaf) {
                window.cancelAnimationFrame(this._scrollRaf);
                this._scrollRaf = null;
            }

            const timeline = this.$refs?.timeline;
            if (timeline && this._scrollListener) {
                timeline.removeEventListener('scroll', this._scrollListener);
                this._scrollListener = null;
            }
        }
    }
}
