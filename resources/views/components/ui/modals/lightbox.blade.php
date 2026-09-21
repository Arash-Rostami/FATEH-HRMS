@props([
    'state' => 'imageViewer',
    'z' => 'z-[99999]',
    'backdrop' => 'bg-[var(--md-sys-color-primary)]',
])

<template x-teleport="body">
    <div
        x-cloak
        x-show="{{ $state }}"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 {{ $z }} {{ $backdrop }} animate-lightbox-in"
        @keydown.escape.window="{{ $state }} = false"
        @click.self="{{ $state }} = false"
    >
        <div class="absolute inset-0 flex items-center justify-center p-4 sm:p-8">
            <div class="relative w-full max-w-[min(96vw,1600px)]">
                {{ $slot }}

                <x-ui.modals.close-button :close="$state . ' = false'" tone="on-dark" size="lg"
                    class="absolute right-3 top-3 shadow-lg hover:scale-105"/>
            </div>
        </div>
    </div>
</template>