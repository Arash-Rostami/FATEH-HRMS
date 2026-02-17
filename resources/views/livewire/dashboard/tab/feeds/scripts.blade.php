<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('feedCarousel', () => ({
            scrollContainer: null,
            observer: null,

            init() {
                this.scrollContainer = this.$refs.timeline;
                this.setupObserver();
            },

            setupObserver() {
                if (this.observer) this.observer.disconnect();

                const options = {
                    root: this.scrollContainer,
                    threshold: 0.6,
                    rootMargin: "0px -20% 0px -20%"
                };

                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('scale-100', 'opacity-100', 'z-10');
                            entry.target.classList.remove('scale-90', 'opacity-60', 'blur-[1px]');
                        } else {
                            entry.target.classList.remove('scale-100', 'opacity-100', 'z-10');
                            entry.target.classList.add('scale-90', 'opacity-60', 'blur-[1px]');
                        }
                    });
                }, options);

                this.$nextTick(() => {
                    this.scrollContainer.querySelectorAll('.feed-item').forEach(el => this.observer.observe(el));
                });
            },

            checkScroll() {
                const el = this.scrollContainer;
                const scrollLeft = Math.abs(el.scrollLeft);
                const maxScroll = el.scrollWidth - el.clientWidth;

                if (maxScroll - scrollLeft < 50 || scrollLeft < 50 && el.scrollWidth > el.clientWidth) {
                   this.$wire.loadMore();
                }
            }
        }));
    });
</script>
