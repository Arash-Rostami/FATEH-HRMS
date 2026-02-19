import {Fancybox} from "@fancyapps/ui";

export default function gallery() {
    return {
        activeId: null,
        loading: false,
        observer: null,
        init() {
            this.$nextTick(() => {
                this.setupScrollListener();
                this.setupIntersectionObserver();
                this.updateActiveItem();
                this.initFancybox();
            });
            Livewire.hook('morph.updated', ({component, el}) => {
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
            this.$refs.galleryContainer.scrollBy({left: -this.$refs.timeline.offsetWidth, behavior: 'smooth'});
        },
        scrollPrev() {
            this.$refs.galleryContainer.scrollBy({left: this.$refs.timeline.offsetWidth, behavior: 'smooth'});
        },
        handleScroll() {
            // Optional: Add logic if needed
        },
        setupScrollListener() {
            const container = this.$refs.galleryContainer;
            if (!container) return;
            let timeout;
            container.addEventListener('scroll', () => {
                if (timeout) return;
                timeout = setTimeout(() => {
                    this.updateActiveItem();
                    timeout = null;
                }, 50); // 50ms throttle
            }, {passive: true});
        },
        setupIntersectionObserver() {
            if (this.observer) this.observer.disconnect();
            this.observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && !this.loading && this.$wire.hasMorePages) {
                    this.loadMore();
                }
            }, {
                root: this.$refs.galleryContainer,
                threshold: 0.1,
                rootMargin: '0px 0px 200px 0px'
            });
            if (this.$refs.loadTrigger) {
                this.observer.observe(this.$refs.loadTrigger);
            }
        },
        updateActiveItem() {
            const container = this.$refs.galleryContainer;
            if (!container) return;
            const containerRect = container.getBoundingClientRect();
            const isDesktop = window.innerWidth >= 768;
            let closestId = null;
            let minDistance = Infinity;
            // Find all photo items
            const items = container.querySelectorAll('[data-photo-id]');
            items.forEach(item => {
                const rect = item.getBoundingClientRect();
                let distance;
                if (isDesktop) {
                    distance = Math.abs(containerRect.right - rect.right);
                } else {
                    const containerCenter = containerRect.top + (containerRect.height / 2);
                    const itemCenter = rect.top + (rect.height / 2);
                    distance = Math.abs(containerCenter - itemCenter);
                }
                if (distance < minDistance) {
                    minDistance = distance;
                    closestId = item.dataset.photoId;
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
                console.error('Failed to load more photos', e);
            } finally {
                this.loading = false;
            }
        }
    }
}
