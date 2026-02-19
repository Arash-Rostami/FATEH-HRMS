import { Fancybox } from "@fancyapps/ui";

export default () => ({
    activeId: null,
    loading: false,
    loadMoreObserver: null,
    activeItemObserver: null,

    init() {
        this.$nextTick(() => {
            this.setupLoadMoreObserver();
            this.setupActiveItemObserver();
            this.initFancybox();
        });

        Livewire.hook('morph.updated', ({ component, el }) => {
            if (component.id === this.$wire.__instance.id) {
                this.$nextTick(() => {
                    this.refreshActiveItemObserver();
                    this.refreshLoadMoreObserver();
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
        if (container) {
             container.scrollBy({ left: -container.offsetWidth / 2, behavior: 'smooth' });
        }
    },
    scrollPrev() {
         const container = this.$refs.galleryContainer;
        if (container) {
             container.scrollBy({ left: container.offsetWidth / 2, behavior: 'smooth' });
        }
    },

    setupLoadMoreObserver() {
        if (this.loadMoreObserver) this.loadMoreObserver.disconnect();

        this.loadMoreObserver = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !this.loading && this.$wire.hasMorePages) {
                this.loadMore();
            }
        }, {
            root: this.$refs.galleryContainer,
            threshold: 0.1,
            rootMargin: '0px 0px 200px 0px'
        });

        this.refreshLoadMoreObserver();
    },

    refreshLoadMoreObserver() {
        if (!this.loadMoreObserver) return;
        this.loadMoreObserver.disconnect();

        if (this.$refs.loadTrigger) {
            this.loadMoreObserver.observe(this.$refs.loadTrigger);
        }
    },

    setupActiveItemObserver() {
        if (this.activeItemObserver) this.activeItemObserver.disconnect();

        this.activeItemObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.activeId = entry.target.dataset.photoId;
                }
            });
        }, {
            root: this.$refs.galleryContainer,
            threshold: 0.6,
        });

        this.refreshActiveItemObserver();
    },

    refreshActiveItemObserver() {
        if (!this.activeItemObserver) return;

        this.activeItemObserver.disconnect();

        const items = this.$refs.galleryContainer.querySelectorAll('[data-photo-id]');
        items.forEach(item => this.activeItemObserver.observe(item));
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
})
