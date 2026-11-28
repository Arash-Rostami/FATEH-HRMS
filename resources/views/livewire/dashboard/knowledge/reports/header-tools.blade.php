<div class="flex flex-wrap items-center gap-2">
    <x-ui.buttons.view-toggle />
    <x-ui.buttons.icon-toggle state="onlySaved" icon="bookmark" title="فقط نشان‌شده‌ها" class="!flex"/>
    <x-ui.buttons.icon-toggle state="showTimeline" icon="timeline" title="نمایش یا پنهان‌کردن نوار تاریخ" x-show="view === 'card'" x-cloak />

    @unless(tenantOff('ai-report'))
        <div class="flex items-center" x-data="{ navigating: false }">
            <a
                href="{{ route('ai-report') }}"
                wire:navigate
                x-on:click="navigating = true; setTimeout(() => navigating = false, 6000)"
                title="گزارش ساز هوشمند"
                aria-label="گزارش ساز هوشمند"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">article</span>
            </a>
            <div x-show="navigating" x-cloak x-transition.opacity
                 x-on:livewire:navigate-failed.window="navigating = false"
                 x-on:livewire:navigate-aborted.window="navigating = false"
                 class="fixed inset-0 z-[9999] flex items-center justify-center bg-[color-mix(in_srgb,var(--md-sys-color-scrim)_45%,transparent)] backdrop-blur-sm">
                <x-ui.loaders.spin-badge text="در حال باز کردن صفحه…"/>
            </div>
        </div>
    @endunless

    <button
        type="button"
        @click="$dispatch('open-modal', { name: 'reports-badge-legend' })"
        title="راهنمای نشانگر اعلان"
        class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
    >
        <span class="material-symbols-rounded text-lg">notifications</span>
    </button>
    <button
        type="button"
        @click="$dispatch('open-modal', { name: 'reports-legend' })"
        title="راهنمای گزارش‌ها"
        class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
    >
        <span class="material-symbols-rounded text-lg">help</span>
    </button>
</div>
