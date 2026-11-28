<div class="flex flex-wrap items-center gap-2">
    <x-ui.buttons.chat-scale/>
    <button type="button" x-on:click="showBadgeLegend()" title="راهنمای نشانگر اعلان" aria-label="راهنمای نشانگر اعلان"
            class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"><span class="material-symbols-rounded text-lg">notifications</span></button>
    <button type="button" x-on:click="showLegend()" title="راهنمای رسانه" aria-label="راهنمای رسانه"
            class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"><span class="material-symbols-rounded text-lg">help</span></button>
</div>
