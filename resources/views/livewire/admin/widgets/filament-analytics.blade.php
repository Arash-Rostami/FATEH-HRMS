@php($tabs = $this->getAllTabs())
<x-filament-widgets::widget>
    <x-filament::fieldset class="min-w-0 w-full">
        <x-ui.title
            icon="analytics"
            title="{{ __('آمار و اطلاعات سیستم') }}"
            :count="$this->getActiveStatsCount()"
            countLabel="آیتم آماری"
        />

        <div
            x-data="{
                activeTab: @entangle('activeTab').live,
                init() {
                    const initSwiper = () => {
                        if (typeof Swiper !== 'undefined') {
                            new Swiper(this.$refs.tabsSwiper, {
                                slidesPerView: 'auto',
                                spaceBetween: 12,
                                centerInsufficientSlides: true,
                                allowTouchMove: true,
                                roundLengths: true,
                                freeMode: {
                                    enabled: true,
                                    sticky: false,
                                    momentumBounce: true,
                                    momentumRatio: 1,
                                    momentumVelocityRatio: 1
                                },
                                grabCursor: true,
                                watchOverflow: true,
                                speed: 400,
                                resistance: true,
                                resistanceRatio: 0.85,
                                a11y: {
                                    enabled: true,
                                    prevSlideMessage: 'قبلی',
                                    nextSlideMessage: 'بعدی',
                                },
                                keyboard: {
                                    enabled: true,
                                    onlyInViewport: true,
                                },
                                mousewheel: {
                                    forceToAxis: true,
                                    sensitivity: 1,
                                    invert: false,
                                },
                                navigation: {
                                    nextEl: this.$refs.nextBtn,
                                    prevEl: this.$refs.prevBtn,
                                },
                                autoplay: {
                                    disableOnInteraction: true,
                                    pauseOnMouseEnter: true,
                                }
                            });
                        } else {
                            setTimeout(initSwiper, 100);
                        }
                    };

                    this.$nextTick(() => {
                        initSwiper();
                    });
                }
            }"
            class="mt-4"
        >
            <div wire:ignore class="relative">
                <div x-ref="tabsSwiper" class="swiper w-full overflow-hidden"
                     style="padding-bottom: 0.5rem; padding-top: 0.5rem; margin-top: -0.5rem; margin-bottom: -0.5rem;">
                    <div class="swiper-wrapper flex items-center">
                        @foreach($tabs as $key => $tab)
                            <div class="swiper-slide !w-auto">
                                <button
                                    @click="activeTab = '{{ $key }}'"
                                    type="button"
                                    class="flex items-center gap-2 px-4 py-2.5 rounded-2xl text-sm font-medium transition-all duration-300 ring-1 ring-inset shadow-sm outline-none"
                                    :class="activeTab === '{{ $key }}'
                                        ? 'bg-primary-50 text-primary-600 ring-primary-600 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/30'
                                        : 'bg-white text-gray-600 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900/50 dark:text-gray-400 dark:ring-gray-800 dark:hover:bg-gray-800'"
                                >
                                    <x-filament::icon
                                        icon="{{ $tab['icon'] }}"
                                        class="w-5 h-5 transition-colors"
                                        x-bind:class="activeTab === '{{ $key }}' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500'"
                                    />
                                    <span>{{ $tab['label'] }}</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div
                    x-ref="nextBtn"
                    class="swiper-button-next !w-6 !h-6 !mt-0 !mb-2 !text-gray-600 dark:!text-gray-300 after:!content-none !bg-white/90 dark:!bg-gray-800/90 !rounded-lg shadow-md ring-1 ring-gray-200 dark:ring-gray-700 flex items-center justify-center transition-all duration-300 [&.swiper-button-disabled]:!opacity-0 [&.swiper-button-disabled]:!pointer-events-none !-left-6 z-10"
                >
                    <x-filament::icon icon="heroicon-m-chevron-left" class="w-3.5 h-3.5"/>
                </div>

                <div
                    x-ref="prevBtn"
                    class="swiper-button-prev !w-6 !h-6 !mt-0 !mb-2 !text-gray-600 dark:!text-gray-300 after:!content-none !bg-white/90 dark:!bg-gray-800/90 !rounded-lg shadow-md ring-1 ring-gray-200 dark:ring-gray-700 flex items-center justify-center transition-all duration-300 [&.swiper-button-disabled]:!opacity-0 [&.swiper-button-disabled]:!pointer-events-none !-right-6 z-10"
                >
                    <x-filament::icon icon="heroicon-m-chevron-right" class="w-3.5 h-3.5"/>
                </div>
            </div>
        </div>

        <div class="mt-6">
            {{ $this->getSchema('stats') }}
        </div>
    </x-filament::fieldset>
</x-filament-widgets::widget>
