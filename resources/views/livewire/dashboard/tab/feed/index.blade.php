<div
    class="relative w-full h-full min-h-[calc(100vh-160px)] flex flex-col items-center justify-center overflow-hidden py-6"
    x-data="{
        swiper: null,
        init() {
            this.swiper = new Swiper('.feed-swiper', {
                effect: 'cards',
                grabCursor: true,
                initialSlide: 0,
                centeredSlides: true,
                slidesPerView: 'auto',
                cardsEffect: {
                    perSlideOffset: 12,
                    perSlideRotate: 2,
                    rotate: true,
                    slideShadows: false,
                },
                keyboard: {
                    enabled: true,
                },
                mousewheel: {
                    invert: false,
                },
                on: {
                    init: function () {
                        console.log('Swiper initialized');
                    },
                    reachEnd: () => {
                        // Optional: trigger loadMore automatically
                        // $wire.loadMore();
                    }
                }
            });

            // Watch for content changes to update Swiper
            Livewire.on('content-changed', () => {
                this.$nextTick(() => {
                    this.swiper.update();
                    console.log('Swiper updated');
                });
            });
        }
    }"
>
    {{-- Swiper Container --}}
    <div class="swiper feed-swiper w-full max-w-[420px] h-[680px] sm:h-[720px] !overflow-visible px-4 pt-8 pb-12">
        <div class="swiper-wrapper">
            @forelse($this->feeds as $feed)
                <div class="swiper-slide rounded-[32px] h-full transition-transform duration-500 will-change-transform" wire:key="feed-{{ $feed->id }}">
                     @include('livewire.dashboard.tab.feed.partials.card', ['feed' => $feed])
                </div>
            @empty
                <div class="swiper-slide flex items-center justify-center bg-[var(--md-sys-color-surface-container)] rounded-[32px] border border-[var(--md-sys-color-outline-variant)]">
                    <div class="text-center p-8">
                        <span class="material-symbols-rounded text-6xl text-[var(--md-sys-color-primary)] mb-4 block">feed</span>
                        <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">No Feeds Yet</h3>
                        <p class="text-[var(--md-sys-color-on-surface-variant)] mt-2">Check back later for updates.</p>
                    </div>
                </div>
            @endforelse

            @if($this->feeds->count() > 0)
                <div class="swiper-slide flex flex-col items-center justify-center bg-[var(--md-sys-color-surface-container-low)] rounded-[32px] border border-[var(--md-sys-color-outline-variant)]/50 shadow-inner">
                     <button
                        wire:click="loadMore"
                        class="group relative flex flex-col items-center justify-center w-full h-full gap-4 transition-all active:scale-95"
                    >
                        <div class="w-16 h-16 rounded-full bg-[var(--md-sys-color-primary-container)] flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg">
                             <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-on-primary-container)] group-hover:translate-y-1 transition-transform" wire:loading.remove>expand_more</span>
                             <span class="w-6 h-6 border-2 border-[var(--md-sys-color-on-primary-container)] border-t-transparent rounded-full animate-spin" wire:loading></span>
                        </div>
                        <span class="font-bold text-lg text-[var(--md-sys-color-primary)]">Load More Stories</span>
                     </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Comments Drawer --}}
    @include('livewire.dashboard.tab.feed.partials.comments-drawer')

    {{-- Assets Injection --}}
    @assets
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
        <style>
            .swiper-slide {
                opacity: 1 !important; /* Ensure visibility */
                transform-origin: center bottom;
            }
            .swiper-slide-shadow {
                background: linear-gradient(to top, rgba(0,0,0,0.5), transparent) !important;
                border-radius: 32px !important;
            }
        </style>
    @endassets
</div>
