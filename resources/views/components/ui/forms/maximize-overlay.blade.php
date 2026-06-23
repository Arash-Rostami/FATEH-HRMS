@props([
    'icon' => 'edit',
    'title' => null,
    'disabled' => false,
])

<template x-teleport="body">
    <div x-show="fullscreen"
         dir="rtl"
         style="display: none;"
         @keydown.window.escape="fullscreen = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95 !bg-[var(--md-sys-color-primary)]/60 "
         class="fixed inset-0 z-[9999] flex flex-col bg-[var(--md-sys-color-surface)]/95">

        <div class="flex items-center justify-between p-4 md:p-6 border-b border-[var(--md-sys-color-outline-variant)]/30">
            <div class="flex items-center gap-3">
                <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">{{ $icon }}</span>
                <span class="text-[var(--md-sys-color-on-surface)] font-medium">{{ $title }}</span>
                <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface-variant)]">fullscreen</span>
            </div>
            <button type="button"
                    title="بستن"
                    @click="fullscreen = false"
                    class="p-2 !pb-0 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant-hover)] transition-colors">
                <span class="material-symbols-rounded text-[20px]">close</span>
            </button>
        </div>

        <div class="flex-1 p-4 md:p-6 md:max-w-5xl md:mx-auto w-full">
            <textarea
                x-model="value"
                {{ $disabled ? 'disabled' : '' }}
                class="w-full h-full resize-none outline-none bg-transparent text-[var(--md-sys-color-on-surface)] text-lg md:text-xl placeholder:text-[var(--md-sys-color-on-surface-variant)]/50 focus:ring-0 border-none"
                placeholder="{{ $title }}..."
            ></textarea>
        </div>
    </div>
</template>
