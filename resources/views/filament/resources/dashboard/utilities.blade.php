<div class="relative ms-4 me-2 w-full max-w-[180px] xs:max-w-[240px] sm:max-w-[340px] md:max-w-[380px] border-r border-slate-200/70 pr-3">

    <div class="absolute top-6 right-1 scale-90 z-20 flex md:hidden items-center justify-center w-5 h-5 rounded-md border border-[var(--md-sys-color-primary-container)]/30 bg-[var(--md-sys-color-primary)]/40 text-[var(--md-sys-color-primary-container)] backdrop-blur-sm pointer-events-none animate-pulse">
        <span class="material-symbols-rounded text-[14px]">swipe</span>
    </div>

    <div class="overflow-x-auto overflow-y-hidden overscroll-x-contain touch-pan-x [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden pb-12 -mb-12">
        <div class="flex items-center gap-1 w-max px-3">
            <button type="button" @click="window.filamentMenu.share()" class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">share</span>
                <x-ui.modals.tooltip text="اشتراک‌گذاری" position="bottom"/>
            </button>

            <button type="button" @click="window.filamentMenu.printPage()" class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">print</span>
                <x-ui.modals.tooltip text="چاپ صفحه" position="bottom"/>
            </button>

            <button type="button" @click="$store.filamentMenu.toggleZen()" class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100" x-text="$store.filamentMenu.zen ? 'visibility_off' : 'visibility'"></span>
                <x-ui.modals.tooltip text="حالت تمرکز" position="bottom"/>
            </button>

            <button type="button" @click="$store.filamentMenu.toggleFullscreen()" class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100" x-text="$store.filamentMenu.fullscreen ? 'close_fullscreen' : 'fullscreen'"></span>
                <x-ui.modals.tooltip text="تمام صفحه" position="bottom"/>
            </button>

            <button type="button" @click="window.filamentMenu.requestPiP()" class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">picture_in_picture_alt</span>
                <x-ui.modals.tooltip text="پنجره شناور" position="bottom"/>
            </button>

            <button type="button" @click="$store.filamentMenu.toggleWakeLock()" class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100" x-text="$store.filamentMenu.wakeLock ? 'bedtime' : 'light_mode'"></span>
                <x-ui.modals.tooltip text="روشن نگه‌داشتن صفحه" position="bottom"/>
            </button>

            <button type="button" @click="window.filamentMenu.clearStorage()" class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">bolt</span>
                <x-ui.modals.tooltip text="پاک‌سازی کش" position="bottom"/>
            </button>

            <button type="button" @click="window.filamentMenu.showShortcuts()" class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">keyboard_command_key</span>
                <x-ui.modals.tooltip text="میانبرها" position="bottom"/>
            </button>
        </div>
    </div>

    <span aria-hidden="true" class="pointer-events-none absolute top-0 left-0 h-10 w-5 z-10 bg-gradient-to-r from-[var(--md-sys-color-primary)] to-transparent"></span>
    <span aria-hidden="true" class="pointer-events-none absolute top-0 right-0 h-10 w-5 z-10 bg-gradient-to-l from-[var(--md-sys-color-primary)] to-transparent"></span>
</div>
