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
    class="flex items-center w-full max-w-[200px] sm:max-w-[360px] overflow-hidden ms-4 me-2">

    <div x-ref="swiper" class="swiper w-full">
        <div class="swiper-wrapper items-center">

            {{-- Share --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="window.filamentMenu.share()"
                        title="اشتراک‌گذاری"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 outline-none text-[var(--md-sys-color-primary-container)]">
                    <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">share</span>
                </button>
            </div>

            {{-- Print --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="window.filamentMenu.printPage()"
                        title="چاپ صفحه"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 outline-none text-[var(--md-sys-color-primary-container)]">
                    <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">print</span>
                </button>
            </div>

            {{-- Focus Mode --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="$store.filamentMenu.toggleZen()"
                        title="حالت تمرکز"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 outline-none text-[var(--md-sys-color-primary-container)]">
                    <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100"
                          x-text="$store.filamentMenu.zen ? 'visibility_off' : 'visibility'"></span>
                </button>
            </div>

            {{-- Fullscreen --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="$store.filamentMenu.toggleFullscreen()"
                        title="تمام صفحه"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 outline-none text-[var(--md-sys-color-primary-container)]">
                    <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100"
                          x-text="$store.filamentMenu.fullscreen ? 'close_fullscreen' : 'fullscreen'"></span>
                </button>
            </div>

            {{-- Picture in Picture --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="window.filamentMenu.requestPiP()"
                        title="پنجره شناور"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 outline-none text-[var(--md-sys-color-primary-container)]">
                    <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">picture_in_picture_alt</span>
                </button>
            </div>

            {{-- Wake Lock --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="$store.filamentMenu.toggleWakeLock()"
                        title="روشن نگه‌داشتن صفحه"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 outline-none text-[var(--md-sys-color-primary-container)]">
                    <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100"
                          x-text="$store.filamentMenu.wakeLock ? 'bedtime' : 'light_mode'"></span>
                </button>
            </div>

            {{-- Clear Cache --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="window.filamentMenu.clearStorage()"
                        title="پاک‌سازی کش"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 outline-none text-[var(--md-sys-color-primary-container)]">
                    <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">bolt</span>
                </button>
            </div>

            {{-- Shortcuts --}}
            <div class="swiper-slide !w-auto">
                <button type="button"
                        @click="window.filamentMenu.showShortcuts()"
                        title="میانبرها"
                        class="group relative flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 outline-none text-[var(--md-sys-color-primary-container)]">
                    <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">keyboard_command_key</span>
                </button>
            </div>

        </div>
    </div>
</div>
