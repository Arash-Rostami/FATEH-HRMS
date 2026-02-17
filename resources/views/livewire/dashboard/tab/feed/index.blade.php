<div
    x-data="{
        swiper: null,
        initSwiper() {
            if (this.swiper) this.swiper.destroy(true, true);

            this.swiper = new Swiper('.swiper-feed', {
                effect: 'cards',
                grabCursor: true,
                centeredSlides: true,
                slidesPerView: 'auto',
                initialSlide: 0,
                cardsEffect: {
                    perSlideOffset: 8,
                    perSlideRotate: 2,
                    slideShadows: false,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                on: {
                    reachEnd: () => {
                        if (this.$wire.hasMorePages) {
                            this.$wire.loadMore();
                        }
                    }
                }
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
            this.$nextTick(() => {
                this.swiper.update();
            });
        });

        Livewire.hook('morph.updated', ({ el, component }) => {
           if (el.classList.contains('swiper-wrapper')) {
               this.swiper.update();
           }
        });
    "
    class="w-full h-full relative flex flex-col items-center justify-center p-4 overflow-hidden"
>
    <div class="absolute inset-0 pointer-events-none opacity-30 bg-gradient-to-br from-[var(--md-sys-color-primary)]/10 via-transparent to-[var(--md-sys-color-secondary)]/10 blur-3xl"></div>

    <div class="swiper swiper-feed w-full max-w-md h-[80vh] rounded-3xl overflow-visible">
        <div class="swiper-wrapper">
            @foreach($this->feeds as $feed)
                <div class="swiper-slide w-full h-full rounded-3xl bg-[var(--md-sys-color-surface)] shadow-xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden flex flex-col relative" wire:key="feed-{{$feed->id}}">
                    @include('livewire.dashboard.tab.feed.item', ['feed' => $feed])
                </div>
            @endforeach

            @if($hasMorePages)
                <div class="swiper-slide w-full h-full flex items-center justify-center bg-[var(--md-sys-color-surface-container)] rounded-3xl" wire:key="loader">
                    <div class="flex flex-col items-center gap-4 text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="w-8 h-8 border-4 border-[var(--md-sys-color-primary)] border-t-transparent rounded-full animate-spin"></span>
                        <span class="font-medium text-sm">در حال بارگذاری...</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="swiper-button-next !text-[var(--md-sys-color-primary)] !w-10 !h-10 !bg-[var(--md-sys-color-surface-container-high)] !rounded-full !shadow-lg !backdrop-blur-md after:!text-lg hover:scale-110 transition-transform"></div>
        <div class="swiper-button-prev !text-[var(--md-sys-color-primary)] !w-10 !h-10 !bg-[var(--md-sys-color-surface-container-high)] !rounded-full !shadow-lg !backdrop-blur-md after:!text-lg hover:scale-110 transition-transform"></div>
    </div>
</div>
