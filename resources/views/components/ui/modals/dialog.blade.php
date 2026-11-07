@props(['name', 'title' => null])

<template x-teleport="body">

    <div
        x-data="{ show: false, name: '{{ $name }}' }"
        x-show="show"
        x-transition:enter="animate-slide-down"
        x-transition:leave="animate-slide-down-out"
        x-on:open-modal.window="show = ($event.detail.name === name)"
        x-on:close-modal.window="show = false"
        x-on:keydown.escape.window="show = false"
        x-on:click.self="show = false"
        style="display: none;"
        class="fixed inset-0 z-[100] overflow-y-auto scrollbar-hover-reveal px-4 py-6 sm:px-0 flex items-center justify-center bg-[var(--md-sys-color-primary)]/60"
        x-cloak
        dir="rtl"
    >
        <div class="relative rounded-2xl overflow-hidden glass-panel modal-lift !border-0 w-full sm:max-w-2xl sm:mx-auto bg-[var(--md-sys-color-surface)] flex flex-col max-h-[calc(100dvh-6rem)]"
             x-on:click.stop>
            @if($title)
                <div
                    class="bg-[var(--md-sys-color-surface-container)] px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)] flex justify-between items-center">
                    <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">{{ $title }}</h3>

                    <x-ui.modals.close-button/>

                </div>
            @endif

            <div class="p-6 flex-1 min-h-0 overflow-y-auto custom-scrollbar">
                {{ $slot }}
            </div>
        </div>
    </div>
</template>
