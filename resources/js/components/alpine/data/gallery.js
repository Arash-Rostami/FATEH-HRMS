import { Fancybox } from "@fancyapps/ui";

export default function gallery() {
    return {
        activeId: null,
        loading: false,
        observer: null,
        showTimeline: false,
        month: '',
        visibleCount: 0,
        previewTimer: null,

        init() {
            this.$nextTick(() => {
                this.setupScrollListener();
                this.setupIntersectionObserver();
                this.initFancybox();
                this.refreshVisible();

                setTimeout(() => {
                    this.updateActiveItem();
                }, 100);
            });

            this.$watch('month', () => this.$nextTick(() => this.refreshVisible()));

            Livewire.hook('morph', ({ component, el }) => {
                if (component.id === this.$wire.__instance.id) {
                    this.$nextTick(() => {
                        this.updateActiveItem();
                        this.refreshVisible();
                        if (this.$refs.loadTrigger && this.observer) {
                            this.observer.disconnect();
                            this.observer.observe(this.$refs.loadTrigger);
                        }
                    });
                }
            });
        },

        initFancybox() {
            Fancybox.bind("[data-fancybox]", {
                Toolbar: {
                    display: {
                        left: ["infobar"],
                        middle: ["zoomIn", "zoomOut", "toggle1to1", "rotateCCW", "rotateCW", "flipX", "flipY"],
                        right: ["slideshow", "fullscreen", "download", "thumbs", "close"],
                    },
                },
                animated: true,
                showClass: "f-fadeIn",
                hideClass: "f-fadeOut",
                Image: { zoom: true },
                backdrop: true,
                keyboard: true,
                dragToClose: true,
                infinite: true,
                Carousel: { transition: "slide" },
            });
        },

        scrollNext() {
            this.$refs.timeline.scrollBy({ left: -this.$refs.timeline.offsetWidth, behavior: 'smooth' });
        },

        scrollPrev() {
            this.$refs.timeline.scrollBy({ left: this.$refs.timeline.offsetWidth, behavior: 'smooth' });
        },

        handleScroll() {},

        refreshVisible() {
            let n = 0;
            this.$root.querySelectorAll('[data-photo-id]').forEach((el) => {
                if (el.offsetParent !== null) n++;
            });
            this.visibleCount = n;
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

        setupIntersectionObserver() {
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

            if (this.$refs.loadTrigger) {
                this.observer.observe(this.$refs.loadTrigger);
            }
        },

        updateActiveItem() {
            const container = this.$refs.galleryContainer;
            const viewport = this.$refs.timeline;
            if (!container || !viewport) return;

            const viewportRect = viewport.getBoundingClientRect();
            const referencePoint = viewportRect.right - viewportRect.width * 0.1;

            let closestId = null;
            let minDistance = Infinity;

            container.querySelectorAll('[data-photo-id]').forEach(item => {
                const rect = item.getBoundingClientRect();
                const distance = Math.abs(referencePoint - rect.right);

                if (distance < minDistance) {
                    minDistance = distance;
                    closestId = item.dataset.photoId;
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
            return m + ':' + String(s).padStart(2, '0');
        }
    }
}
