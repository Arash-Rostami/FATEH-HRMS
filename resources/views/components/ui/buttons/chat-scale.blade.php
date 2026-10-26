<div class="flex items-center gap-0.5 p-0.5 rounded-xl border border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-variant)]/40" role="group" aria-label="اندازهٔ آیکون‌ها و دکمه‌ها">
    <button
        type="button"
        x-on:click="stepScale(-1)"
        :disabled="!canShrinkScale()"
        title="کوچک‌کردن آیکون‌ها و دکمه‌ها"
        aria-label="کوچک‌کردن آیکون‌ها و دکمه‌ها"
        class="flex items-center justify-center w-7 h-7 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 disabled:opacity-40 disabled:pointer-events-none transition-all duration-200"
    >
        <span class="material-symbols-rounded text-[18px]">remove</span>
    </button>
    <button
        type="button"
        x-on:click="resetScale()"
        :disabled="isDefaultScale()"
        title="بازگشت به اندازهٔ پیش‌فرض"
        aria-label="بازگشت به اندازهٔ پیش‌فرض"
        class="flex items-center justify-center gap-0.5 min-w-[2.75rem] h-7 px-1.5 rounded-lg text-[11px] font-bold tabular-nums text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] active:scale-95 disabled:text-[var(--md-sys-color-on-surface-variant)] disabled:opacity-60 disabled:pointer-events-none transition-all duration-200"
    >
        <span x-show="!isDefaultScale()" x-cloak class="material-symbols-rounded text-[14px] leading-none">restart_alt</span>
        <span x-text="scaleLabel()">۱۰۰٪</span>
    </button>
    <button
        type="button"
        x-on:click="stepScale(1)"
        :disabled="!canGrowScale()"
        title="بزرگ‌کردن آیکون‌ها و دکمه‌ها"
        aria-label="بزرگ‌کردن آیکون‌ها و دکمه‌ها"
        class="flex items-center justify-center w-7 h-7 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 disabled:opacity-40 disabled:pointer-events-none transition-all duration-200"
    >
        <span class="material-symbols-rounded text-[18px]">add</span>
    </button>
</div>
