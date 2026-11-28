@props(['down' => 'stepScale(-1)', 'up' => 'stepScale(1)', 'reset' => 'resetScale()', 'canDown' => 'canShrinkScale()', 'canUp' => 'canGrowScale()', 'isDefault' => 'isDefaultScale()', 'text' => 'scaleLabel()', 'downTitle' => 'کوچک‌کردن آیکون‌ها و دکمه‌ها', 'upTitle' => 'بزرگ‌کردن آیکون‌ها و دکمه‌ها', 'groupLabel' => 'اندازهٔ آیکون‌ها و دکمه‌ها', 'resetTitle' => 'بازگشت به اندازهٔ پیش‌فرض', 'spread' => false])
<div class="flex items-center gap-0.5 p-0.5 rounded-xl border border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-variant)]/40 {{ $spread ? 'w-full justify-between' : '' }}" role="group" aria-label="{{ $groupLabel }}">
    <button
        type="button"
        x-on:click="{{ $down }}"
        :disabled="!({{ $canDown }})"
        title="{{ $downTitle }}"
        aria-label="{{ $downTitle }}"
        class="flex items-center justify-center w-7 h-7 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 disabled:opacity-40 disabled:pointer-events-none transition-all duration-200"
    >
        <span class="material-symbols-rounded text-[18px]">remove</span>
    </button>
    <button
        type="button"
        x-on:click="{{ $reset }}"
        :disabled="{{ $isDefault }}"
        title="{{ $resetTitle }}"
        aria-label="{{ $resetTitle }}"
        class="flex items-center justify-center gap-0.5 min-w-[2.75rem] h-7 px-1.5 rounded-lg text-[11px] font-bold tabular-nums text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] active:scale-95 disabled:text-[var(--md-sys-color-on-surface-variant)] disabled:opacity-60 disabled:pointer-events-none transition-all duration-200"
    >
        <span x-show="!({{ $isDefault }})" x-cloak class="material-symbols-rounded text-[14px] leading-none">restart_alt</span>
        <span x-text="{{ $text }}">۱۰۰٪</span>
    </button>
    <button
        type="button"
        x-on:click="{{ $up }}"
        :disabled="!({{ $canUp }})"
        title="{{ $upTitle }}"
        aria-label="{{ $upTitle }}"
        class="flex items-center justify-center w-7 h-7 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 disabled:opacity-40 disabled:pointer-events-none transition-all duration-200"
    >
        <span class="material-symbols-rounded text-[18px]">add</span>
    </button>
</div>
