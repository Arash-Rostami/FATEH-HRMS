<x-ui.modals.popover alignment="top-full left-0 origin-top-left">
    <x-slot:trigger>
        <button
            type="button"
            aria-label="ابزارهای بیشتر"
            title="ابزارهای بیشتر"
            aria-haspopup="menu"
            :aria-expanded="open"
            x-on:close-title-more.window="open = false"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-[0.96] transition-all duration-200"
        >
            <span class="material-symbols-rounded text-lg">more_horiz</span>
        </button>
    </x-slot:trigger>
    <x-slot:content>
        <div x-on:click.stop class="py-0.5">
            <button type="button" role="menuitemcheckbox" :aria-checked="onlySaved" x-on:click="onlySaved = !onlySaved"
                    class="w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors duration-200">
                <span class="material-symbols-rounded text-[16px]">bookmark</span>
                <span>فقط نشان‌شده‌ها</span>
                <span class="ms-auto flex h-4 w-4 items-center justify-center rounded-[2px] bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]" x-show="onlySaved" x-cloak>
                    <span class="material-symbols-rounded text-[12px] leading-none font-fill">check</span>
                </span>
            </button>
        </div>

        @unless(tenantOff('ai-report'))
            <div class="flex items-center" x-data="{ navigating: false }">
                <a href="{{ route('ai-report') }}" wire:navigate
                   x-on:click="navigating = true; setTimeout(() => navigating = false, 6000)"
                   class="w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors duration-200">
                    <span class="material-symbols-rounded text-[16px]">article</span>
                    <span>گزارش ساز هوشمند</span>
                </a>
                <div x-show="navigating" x-cloak x-transition.opacity.duration.200ms
                     x-on:livewire:navigate-failed.window="navigating = false"
                     x-on:livewire:navigate-aborted.window="navigating = false"
                     class="fixed inset-0 z-[9999] flex items-center justify-center bg-[color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)] backdrop-blur-sm">
                    <x-ui.loaders.spin-badge text="در حال باز کردن صفحه…"/>
                </div>
            </div>
        @endunless

        <button type="button" x-on:click="$dispatch('close-title-more'); $dispatch('open-modal', { name: 'reports-badge-legend' })"
                class="w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors duration-200">
            <span class="material-symbols-rounded text-[16px]">notifications</span>
            <span>راهنمای نشانگر اعلان</span>
        </button>
        <button type="button" x-on:click="$dispatch('close-title-more'); $dispatch('open-modal', { name: 'reports-legend' })"
                class="w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors duration-200">
            <span class="material-symbols-rounded text-[16px]">help</span>
            <span>راهنمای گزارش‌ها</span>
        </button>
        <x-ui.video-guide module="knowledge" variant="row"/>
    </x-slot:content>
</x-ui.modals.popover>
