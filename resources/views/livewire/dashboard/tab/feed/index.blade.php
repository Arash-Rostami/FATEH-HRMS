<div
    x-data="{
        swiper: null,
        initSwiper() {
            if (this.swiper && !this.swiper.destroyed) this.swiper.destroy(true, true);

            // Wait for DOM to be fully ready
            this.$nextTick(() => {
                this.swiper = new Swiper('.swiper-feed', {
                    effect: 'cards',
                    grabCursor: true,
                    loop: false,
                    centeredSlides: true,
                    initialSlide: 0,
                    slidesPerView: 'auto',
                    speed: 500,
                    cardsEffect: {
                        perSlideOffset: 12, // Increased spacing for better depth
                        perSlideRotate: 3,  // Subtle rotation
                        rotate: true,
                        slideShadows: true,
                    },
                    navigation: {
                        nextEl: '.swiper-nav-next',
                        prevEl: '.swiper-nav-prev',
                    },
                    keyboard: {
                        enabled: true,
                        onlyInViewport: true,
                    },
                    mousewheel: {
                        forceToAxis: true,
                        sensitivity: 1,
                    },
                    touchStartPreventDefault: false, // Important for scrolling inside slides
                    on: {
                        reachEnd: () => {
                            if (this.$wire.hasMorePages) {
                                this.$wire.loadMore();
                            }
                        }
                    }
                });
            });
        }
    }"
    x-init="
        if (typeof Swiper === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js';
            script.onload = () => initSwiper();
            document.head.appendChild(script);

            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css';
            document.head.appendChild(link);
        } else {
            initSwiper();
        }

        Livewire.on('feeds-loaded', () => {
            if (this.swiper) {
                this.swiper.update();
                // Optionally maintain slide index or scroll to new position
            }
        });

        Livewire.hook('morph.updated', ({ el, component }) => {
           if (el.classList.contains('swiper-wrapper') && this.swiper) {
               this.swiper.update();
           }
        });
    "
    class="relative w-full h-full flex items-center justify-center bg-[var(--md-sys-color-background)] overflow-hidden"
    dir="rtl"
>
    <!-- Inject Custom Styles -->
    @include('livewire.dashboard.tab.feed.styles')

    <!-- Ambient Background Lighting -->
    <div class="absolute top-[-20%] right-[-10%] w-[600px] h-[600px] rounded-full bg-[var(--md-sys-color-primary)] opacity-[0.08] blur-[120px] pointer-events-none animate-pulse"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-[var(--md-sys-color-secondary)] opacity-[0.08] blur-[100px] pointer-events-none"></div>

    <!-- Main Swiper Container -->
    <div class="swiper swiper-feed w-full max-w-md h-[80vh] rounded-3xl" wire:ignore>
        <div class="swiper-wrapper">
            @foreach($this->feeds as $feed)
                <div class="swiper-slide w-full h-full rounded-3xl bg-[var(--md-sys-color-surface)] shadow-xl overflow-hidden flex flex-col relative border border-[var(--md-sys-color-outline-variant)]/30 backdrop-blur-3xl" wire:key="feed-{{$feed->id}}">
                    @include('livewire.dashboard.tab.feed.item', ['feed' => $feed])
                </div>
            @endforeach

            @if($hasMorePages)
                <div class="swiper-slide w-full h-full flex items-center justify-center bg-[var(--md-sys-color-surface-container-low)] rounded-3xl backdrop-blur-sm" wire:key="loader">
                    <div class="flex flex-col items-center gap-4 text-[var(--md-sys-color-on-surface-variant)] animate-pulse">
                        <div class="w-12 h-12 rounded-full border-4 border-[var(--md-sys-color-primary)] border-t-transparent animate-spin shadow-lg shadow-[var(--md-sys-color-primary)]/20"></div>
                        <span class="font-bold text-sm tracking-wide">در حال بارگذاری...</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Custom Navigation Buttons (Desktop only via CSS) -->
        <div class="swiper-nav-btn swiper-nav-prev hidden sm:flex">
            <span class="material-symbols-rounded text-3xl">chevron_right</span>
        </div>
        <div class="swiper-nav-btn swiper-nav-next hidden sm:flex">
            <span class="material-symbols-rounded text-3xl">chevron_left</span>
        </div>
    </div>
</div>
