<div x-data="{
        initSwiper() {
            if (typeof Swiper !== 'undefined') {
                new Swiper(this.$refs.swiper, {
                    slidesPerView: 'auto',
                    spaceBetween: 4,
                    freeMode: true,
                    grabCursor: true,
                });
            }
        }
    }"
    x-init="initSwiper"
    class="flex items-center w-full max-w-[200px] sm:max-w-[400px] overflow-hidden ml-2">

    <div x-ref="swiper" class="swiper w-full">
        <div class="swiper-wrapper items-center">

            {{-- Share --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="window.filamentMenu.share()"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center hover:bg-[var(--md-sys-color-on-primary)]/10 rounded-full">
                    <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100">share</span>
                    <x-ui.modals.tooltip text="اشتراک‌گذاری" position="bottom" />
                </button>
            </div>

            {{-- Print --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="window.filamentMenu.printPage()"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center hover:bg-[var(--md-sys-color-on-primary)]/10 rounded-full">
                    <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100">print</span>
                    <x-ui.modals.tooltip text="چاپ صفحه" position="bottom" />
                </button>
            </div>

            {{-- Focus Mode --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="$store.filamentMenu.toggleZen()"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center hover:bg-[var(--md-sys-color-on-primary)]/10 rounded-full">
                    <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100"
                          x-text="$store.filamentMenu.zen ? 'visibility_off' : 'visibility'"></span>
                    <x-ui.modals.tooltip text="حالت تمرکز" position="bottom" />
                </button>
            </div>

            {{-- Fullscreen --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="$store.filamentMenu.toggleFullscreen()"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center hover:bg-[var(--md-sys-color-on-primary)]/10 rounded-full">
                    <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100"
                          x-text="$store.filamentMenu.fullscreen ? 'close_fullscreen' : 'fullscreen'"></span>
                    <x-ui.modals.tooltip text="تمام صفحه" position="bottom" />
                </button>
            </div>

            {{-- Picture in Picture --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="window.filamentMenu.requestPiP()"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center hover:bg-[var(--md-sys-color-on-primary)]/10 rounded-full">
                    <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100">picture_in_picture_alt</span>
                    <x-ui.modals.tooltip text="پنجره شناور" position="bottom" />
                </button>
            </div>

            {{-- Wake Lock --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="$store.filamentMenu.toggleWakeLock()"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center hover:bg-[var(--md-sys-color-on-primary)]/10 rounded-full">
                    <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100"
                          x-text="$store.filamentMenu.wakeLock ? 'bedtime' : 'light_mode'"></span>
                    <x-ui.modals.tooltip text="روشن نگه‌داشتن صفحه" position="bottom" />
                </button>
            </div>

            {{-- Clear Cache --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="window.filamentMenu.clearStorage()"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center hover:bg-[var(--md-sys-color-on-primary)]/10 rounded-full">
                    <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100">bolt</span>
                    <x-ui.modals.tooltip text="پاک‌سازی کش" position="bottom" />
                </button>
            </div>

            {{-- Shortcuts --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="window.filamentMenu.showShortcuts()"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center hover:bg-[var(--md-sys-color-on-primary)]/10 rounded-full">
                    <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100">keyboard_command_key</span>
                    <x-ui.modals.tooltip text="میانبرها" position="bottom" />
                </button>
            </div>

        </div>
    </div>
</div>
