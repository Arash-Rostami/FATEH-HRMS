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
                    speed: 400, // Faster transitions
                    observer: true,
                    observeParents: true,
                    cardsEffect: {
                        perSlideOffset: 8,  // Reduced spacing
                        perSlideRotate: 2,  // Reduced rotation for performance
                        rotate: false,      // Disabled 3D rotation
                        slideShadows: false, // Disabled expensive shadows
                    },
                    navigation: {
                        nextEl: '.swiper-nav-next',
                        prevEl: '.swiper-nav-prev',
                    },
                    // Keyboard/Mousewheel removed to focus on touch performance
                    touchStartPreventDefault: false,
                    threshold: 5, // Prevent accidental swipes
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
            script.defer = true;
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
            }
        });
    "
    class="relative w-full h-full flex items-center justify-center bg-[var(--md-sys-color-background)] overflow-hidden"
    dir="rtl"
>
    <!-- Inject Custom Styles -->
    @include('livewire.dashboard.tab.feed.styles')

    <!-- Main Swiper Container -->
    <div class="swiper swiper-feed w-full max-w-md h-[80vh] rounded-2xl" wire:ignore>
        <div class="swiper-wrapper">
            @foreach($this->feeds as $feed)
                <div class="swiper-slide w-full h-full rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md overflow-hidden flex flex-col relative border border-[var(--md-sys-color-outline-variant)]/30" wire:key="feed-{{$feed->id}}">
                    @include('livewire.dashboard.tab.feed.item', ['feed' => $feed])
                </div>
            @endforeach

            @if($hasMorePages)
                <div class="swiper-slide w-full h-full flex items-center justify-center bg-[var(--md-sys-color-surface-container-low)] rounded-2xl" wire:key="loader">
                    <div class="flex flex-col items-center gap-4 text-[var(--md-sys-color-on-surface-variant)]">
                        <div class="w-10 h-10 rounded-full border-2 border-[var(--md-sys-color-primary)] border-t-transparent animate-spin"></div>
                        <span class="font-medium text-sm">در حال بارگذاری...</span>
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
