<div class="ms-4 me-2 flex items-center w-full max-w-[180px] xs:max-w-[240px] sm:max-w-[340px] md:max-w-[380px]
            overflow-x-auto overflow-y-hidden overscroll-x-contain touch-pan-x
            [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden
            [mask-image:linear-gradient(to_right,transparent,#000_18px,#000_calc(100%-18px),transparent)]
            [-webkit-mask-image:linear-gradient(to_right,transparent,#000_18px,#000_calc(100%-18px),transparent)]">

    <div class="flex items-center gap-1 w-max px-3">

        {{-- Share --}}
        <button type="button"
                @click="window.filamentMenu.share()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">share</span>
            <x-ui.modals.tooltip text="اشتراک‌گذاری" position="bottom" />
        </button>

        {{-- Print --}}
        <button type="button"
                @click="window.filamentMenu.printPage()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">print</span>
            <x-ui.modals.tooltip text="چاپ صفحه" position="bottom" />
        </button>

        {{-- Focus Mode --}}
        <button type="button"
                x-data
                @click="$store.filamentMenu.toggleZen()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100"
                  x-text="$store.filamentMenu.zen ? 'visibility_off' : 'visibility'"></span>
            <x-ui.modals.tooltip text="حالت تمرکز" position="bottom" />
        </button>

        {{-- Fullscreen --}}
        <button type="button"
                x-data
                @click="$store.filamentMenu.toggleFullscreen()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100"
                  x-text="$store.filamentMenu.fullscreen ? 'close_fullscreen' : 'fullscreen'"></span>
            <x-ui.modals.tooltip text="تمام صفحه" position="bottom" />
        </button>

        {{-- PiP --}}
        <button type="button"
                @click="window.filamentMenu.requestPiP()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">picture_in_picture_alt</span>
            <x-ui.modals.tooltip text="پنجره شناور" position="bottom" />
        </button>

        {{-- Wake Lock --}}
        <button type="button"
                x-data
                @click="$store.filamentMenu.toggleWakeLock()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100"
                  x-text="$store.filamentMenu.wakeLock ? 'bedtime' : 'light_mode'"></span>
            <x-ui.modals.tooltip text="روشن نگه‌داشتن صفحه" position="bottom" />
        </button>

        {{-- Clear Cache --}}
        <button type="button"
                @click="window.filamentMenu.clearStorage()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">bolt</span>
            <x-ui.modals.tooltip text="پاک‌سازی کش" position="bottom" />
        </button>

        {{-- Shortcuts --}}
        <button type="button"
                @click="window.filamentMenu.showShortcuts()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">keyboard_command_key</span>
            <x-ui.modals.tooltip text="میانبرها" position="bottom" />
        </button>

    </div>
</div>
