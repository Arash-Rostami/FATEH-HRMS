@props([
    'prev' => 'scrollPrev',
    'next' => 'scrollNext',
    'prevShow' => null,
    'nextShow' => null,
])

<div dir="rtl" class="hidden md:flex absolute -top-5 inset-x-0 items-end justify-between z-20">
    <button type="button"
            @if($prevShow) x-cloak x-show="{{ $prevShow }}" @endif
            @click="{{ $prev }}"
            class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 transition-all duration-200 flex items-center justify-center relative -right-10"
            aria-label="جابه‌جایی به راست">
        <span class="material-symbols-rounded text-2xl">chevron_right</span>
    </button>

    <button type="button"
            @if($nextShow) x-cloak x-show="{{ $nextShow }}" @endif
            @click="{{ $next }}"
            class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 transition-all duration-200 flex items-center justify-center relative -left-10"
            aria-label="جابه‌جایی به چپ">
        <span class="material-symbols-rounded text-2xl">chevron_left</span>
    </button>
</div>
