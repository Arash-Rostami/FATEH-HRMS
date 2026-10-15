<button type="button" x-on:click="toggleSidebar()" :aria-pressed="sbCollapsed"
        :aria-label="sbCollapsed ? 'باز کردن نوار کناری' : 'جمع کردن نوار کناری'"
        :title="sbCollapsed ? 'باز کردن نوار کناری' : 'جمع کردن نوار کناری'"
        class="hidden md:flex {{ $margin ?? 'ms-1.5' }} {{ $size ?? 'w-6 h-6' }} shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-90">
    <span class="material-symbols-rounded {{ $icon ?? 'text-[14px]' }} transition-transform duration-300" :class="sbCollapsed ? 'rotate-180' : ''" aria-hidden="true">chevron_right</span>
</button>
