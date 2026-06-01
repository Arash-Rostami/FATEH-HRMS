import {Fancybox} from "@fancyapps/ui";

export default function gallery() {
    return {
        activeId: null,
        loading: false,
        observer: null,
        showTimeline: false,

        init() {
            this.$nextTick(() => {
                this.setupScrollListener();
                this.setupIntersectionObserver();
                this.initFancybox();
z
                setTimeout(() => {
                    this.updateActiveItem();
                }, 100);
            });

            Livewire.hook('morph', ({component, el}) => {
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

        initFancybox() {
            Fancybox.bind("[data-fancybox]", {
                Toolbar: {
                    display: {
                        left: ["infobar"],
                        middle: [
                            "zoomIn",
                            "zoomOut",
                            "toggle1to1",
                            "rotateCCW",
                            "rotateCW",
                            "flipX",
                            "flipY",
                        ],
                        right: ["slideshow", "fullscreen", "download", "thumbs", "close"],
                    },
                },
                animated: true,
                showClass: "f-fadeIn",
                hideClass: "f-fadeOut",
                Image: {
                    zoom: true,
                },
                backdrop: true,
                keyboard: true,
                dragToClose: true,
                infinite: true,
                Carousel: {
                    transition: "slide",
                },
            });
        },

        scrollNext() {
            const container = this.$refs.galleryContainer;
            const scrollAmount = window.innerWidth >= 768 ? 450 : container.offsetWidth;
            container.scrollBy({left: -scrollAmount, behavior: 'smooth'});
        },

        scrollPrev() {
            const container = this.$refs.galleryContainer;
            const scrollAmount = window.innerWidth >= 768 ? 450 : container.offsetWidth;
            container.scrollBy({left: scrollAmount, behavior: 'smooth'});
        },

        handleScroll() {
            const el = this.$refs.galleryContainer;
            const scrollLeft = Math.abs(el.scrollLeft);
            const maxScroll = el.scrollWidth - el.clientWidth;
        },

        setupScrollListener() {
            const container = this.$refs.galleryContainer;
            if (!container) return;

            let timeout;
            container.addEventListener('scroll', () => {
                if (timeout) window.cancelAnimationFrame(timeout);
                timeout = window.requestAnimationFrame(() => {
                    this.updateActiveItem();
                });
            }, {passive: true});
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
            const viewportCenter = viewportRect.left + (viewportRect.width / 2);

            let closestId = null;
            let minDistance = Infinity;

            const items = container.querySelectorAll('[data-photo-id]');

            items.forEach(item => {
                const rect = item.getBoundingClientRect();

                const itemCenter = rect.left + (rect.width / 2);
                const distance = Math.abs(viewportCenter - itemCenter);

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
        }
    }
}
