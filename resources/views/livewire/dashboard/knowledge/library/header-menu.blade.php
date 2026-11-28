<x-ui.modals.popover alignment="top-full left-0 origin-top-left">
    <x-slot:trigger>
        <button
            type="button"
            aria-label="ابزارهای بیشتر"
            title="ابزارهای بیشتر"
            aria-haspopup="menu"
            :aria-expanded="open"
            x-on:close-title-more.window="open = false"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
        >
            <span class="material-symbols-rounded text-lg">more_horiz</span>
        </button>
    </x-slot:trigger>
    <x-slot:content>
        <div class="px-2 pt-1.5 pb-1" x-on:click.stop>
            <p class="mb-1 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">اندازهٔ آیکون‌ها و دکمه‌ها</p>
            <x-ui.buttons.chat-scale spread/>
        </div>
        <button type="button" x-on:click="$dispatch('close-title-more'); showBadgeLegend()"
                class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
            <span class="material-symbols-rounded text-[16px]">notifications</span>
            <span>راهنمای نشانگر اعلان</span>
        </button>
        <button type="button" x-on:click="$dispatch('close-title-more'); showLegend()"
                class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
            <span class="material-symbols-rounded text-[16px]">help</span>
            <span>راهنمای رسانه</span>
        </button>
        <x-ui.video-guide module="knowledge" variant="row"/>
    </x-slot:content>
</x-ui.modals.popover>
