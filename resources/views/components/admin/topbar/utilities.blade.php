<div x-data="{
        tooltipVisible: false,
        tooltipText: '',
        tooltipX: 0,
        tooltipY: 0,
        showTooltip(event, text) {
            this.tooltipText = text;
            const rect = event.currentTarget.getBoundingClientRect();
            // Position exactly below the button (bottom position)
            this.tooltipX = rect.left + (rect.width / 2);
            this.tooltipY = rect.bottom + 12; // 12px gap
            this.tooltipVisible = true;
        },
        hideTooltip() {
            this.tooltipVisible = false;
        }
     }"
     class="ms-4 me-2 flex items-center w-full max-w-[180px] xs:max-w-[240px] sm:max-w-[340px] md:max-w-[380px]
            overflow-x-auto overflow-y-hidden overscroll-x-contain touch-pan-x
            [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden
            [mask-image:linear-gradient(to_right,transparent,#000_18px,#000_calc(100%-18px),transparent)]
            [-webkit-mask-image:linear-gradient(to_right,transparent,#000_18px,#000_calc(100%-18px),transparent)]">

    <div class="flex items-center gap-1 w-max px-3">

        {{-- Share --}}
        <button type="button"
                @click="window.filamentMenu.share()"
                @mouseenter="showTooltip($event, 'اشتراک‌گذاری')"
                @mouseleave="hideTooltip()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">share</span>
        </button>

        {{-- Print --}}
        <button type="button"
                @click="window.filamentMenu.printPage()"
                @mouseenter="showTooltip($event, 'چاپ صفحه')"
                @mouseleave="hideTooltip()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">print</span>
        </button>

        {{-- Focus Mode --}}
        <button type="button"
                @click="$store.filamentMenu.toggleZen()"
                @mouseenter="showTooltip($event, 'حالت تمرکز')"
                @mouseleave="hideTooltip()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100"
                  x-text="$store.filamentMenu.zen ? 'visibility_off' : 'visibility'"></span>
        </button>

        {{-- Fullscreen --}}
        <button type="button"
                @click="$store.filamentMenu.toggleFullscreen()"
                @mouseenter="showTooltip($event, 'تمام صفحه')"
                @mouseleave="hideTooltip()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100"
                  x-text="$store.filamentMenu.fullscreen ? 'close_fullscreen' : 'fullscreen'"></span>
        </button>

        {{-- PiP --}}
        <button type="button"
                @click="window.filamentMenu.requestPiP()"
                @mouseenter="showTooltip($event, 'پنجره شناور')"
                @mouseleave="hideTooltip()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">picture_in_picture_alt</span>
        </button>

        {{-- Wake Lock --}}
        <button type="button"
                @click="$store.filamentMenu.toggleWakeLock()"
                @mouseenter="showTooltip($event, 'روشن نگه‌داشتن صفحه')"
                @mouseleave="hideTooltip()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100"
                  x-text="$store.filamentMenu.wakeLock ? 'bedtime' : 'light_mode'"></span>
        </button>

        {{-- Clear Cache --}}
        <button type="button"
                @click="window.filamentMenu.clearStorage()"
                @mouseenter="showTooltip($event, 'پاک‌سازی کش')"
                @mouseleave="hideTooltip()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">bolt</span>
        </button>

        {{-- Shortcuts --}}
        <button type="button"
                @click="window.filamentMenu.showShortcuts()"
                @mouseenter="showTooltip($event, 'میانبرها')"
                @mouseleave="hideTooltip()"
                class="group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">keyboard_command_key</span>
        </button>

    </div>

    {{-- Teleported Custom Tooltip --}}
    <template x-teleport="body">
        <span x-show="tooltipVisible"
              x-transition:enter="transition-[opacity,transform] duration-300 ease-out"
              x-transition:enter-start="opacity-0 -translate-y-2"
              x-transition:enter-end="opacity-100 translate-y-0"
              x-transition:leave="transition-[opacity,transform] duration-200 ease-in"
              x-transition:leave-start="opacity-100 translate-y-0"
              x-transition:leave-end="opacity-0 -translate-y-2"
              :style="`top: ${tooltipY}px; left: ${tooltipX}px;`"
              class="fixed -translate-x-1/2 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-[12px] font-medium tracking-wide px-3 py-1.5 rounded-lg whitespace-nowrap z-[9999] shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),0_2px_4px_-1px_rgba(0,0,0,0.06)] border border-[var(--md-sys-color-outline)]/10 pointer-events-none"
              role="tooltip">
            <span x-text="tooltipText"></span>
        </span>
    </template>

</div>
