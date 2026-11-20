@props(['name', 'title' => null, 'icon' => 'info', 'subtitle' => null])

<template x-teleport="body">

    <div
        x-data="{ show: false, name: '{{ $name }}' }"
        x-show="show"
        x-transition:enter="animate-slide-down"
        x-transition:leave="animate-slide-down-out"
        x-on:open-modal.window="show = ($event.detail.name === name)"
        x-on:close-modal.window="show = false"
        x-on:keydown.escape.window.capture="if (show && $onTop) { $event.stopImmediatePropagation(); show = false }"
        x-on:click.self="show = false"
        style="display: none;"
        class="fixed inset-0 z-[100] overflow-y-auto scrollbar-hover-reveal px-4 py-6 sm:px-0 flex items-center justify-center bg-[var(--md-sys-color-primary)]/60"
        x-cloak
        dir="rtl"
    >
        <div class="relative rounded-2xl overflow-hidden glass-panel modal-lift !border-0 w-full sm:max-w-2xl sm:mx-auto bg-[var(--md-sys-color-surface)] flex flex-col max-h-[calc(100dvh-6rem)]"
             x-on:click.stop>
            @if($title)
                <div class="px-4 sm:px-6 py-2.5 sm:py-4 bg-gradient-to-l from-[var(--md-sys-color-primary)] to-[color-mix(in_srgb,var(--md-sys-color-primary)_85%,transparent)] text-[var(--md-sys-color-on-primary)] border-b border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] shrink-0 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                        <span class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_25%,transparent)] flex items-center justify-center shadow-md shrink-0">
                            <span class="material-symbols-rounded text-lg sm:text-xl">{{ $icon }}</span>
                        </span>
                        <div class="flex flex-col flex-1 min-w-0">
                            <h3 class="text-[15px] sm:text-base font-bold leading-tight truncate">{{ $title }}</h3>
                            @if($subtitle)
                                <p class="text-[11px] sm:text-xs text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_80%,transparent)] truncate mt-0.5">{{ $subtitle }}</p>
                            @endif
                        </div>
                    </div>

                    <x-ui.modals.close-button label="بستن پنجره" tone="on-primary" size="sm"/>
                </div>
            @endif

            <div class="p-6 flex-1 min-h-0 overflow-y-auto custom-scrollbar">
                {{ $slot }}
            </div>
        </div>
    </div>
</template>
