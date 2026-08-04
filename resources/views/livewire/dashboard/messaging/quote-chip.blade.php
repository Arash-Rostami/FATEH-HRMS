<div x-show="quoteChip.visible" x-cloak
     :style="{ left: quoteChip.x + 'px', top: quoteChip.y + 'px' }"
     x-on:click.prevent="useQuoteChip()"
     x-on:mousedown.prevent=""
     class="fixed -translate-y-[calc(100%+8px)] -translate-x-1/2 px-3 py-2 rounded-xl shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-[12px] font-bold flex items-center gap-1.5 cursor-pointer hover:opacity-90 active:scale-95 transition-all whitespace-nowrap z-[60]"
     x-transition:enter="animate-bubble-in"
     x-transition:leave="animate-fade-out"
     role="button" aria-label="پاسخ به متن انتخاب‌شده">
    <span class="material-symbols-rounded text-[16px]">reply</span>
    پاسخ
</div>