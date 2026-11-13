<template x-teleport="body">
    <div
        x-show="open" x-cloak dir="rtl" data-peek-card
        @mouseenter="keep()"
        @mouseleave="hide()"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        :style="{ position: 'fixed', top: pos.top + 'px', right: pos.right + 'px' }"
        class="w-[21rem] z-[110] overflow-hidden rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] p-2 shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-scrim)_22%,transparent)]"
    >
        <div x-show="frame" class="flex items-center gap-2 px-2 pb-2 pt-1">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                <span class="material-symbols-rounded text-[16px] leading-none" aria-hidden="true" x-text="icon"></span>
            </div>
            <p class="truncate text-sm font-bold text-[var(--md-sys-color-on-surface)]" x-text="label"></p>
        </div>

        <div x-show="frame" class="relative h-[12.5rem] overflow-hidden rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-lowest)]">
            <iframe :srcdoc="frame" sandbox="allow-same-origin" inert tabindex="-1" aria-hidden="true" title=""
                    class="absolute right-0 top-0 border-0 pointer-events-none"
                    style="width:1280px;height:800px;transform:scale(0.25);transform-origin:top right"></iframe>
        </div>

        <div x-show="!frame" class="flex h-[12.5rem] flex-col items-center justify-center gap-2 rounded-xl bg-[var(--md-sys-color-surface-container-high)] px-6 text-center text-[var(--md-sys-color-on-surface-variant)]">
            <span class="material-symbols-rounded text-[40px] leading-none" aria-hidden="true" x-text="icon"></span>
            <p class="text-sm font-bold" x-text="label"></p>
            <p class="text-xs opacity-70">برای ساخت پیش‌نمایش، یک‌بار این بخش را باز کنید.</p>
        </div>
    </div>
</template>
