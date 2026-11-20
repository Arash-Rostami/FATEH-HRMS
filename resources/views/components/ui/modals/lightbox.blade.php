@props([
    'state' => 'imageViewer',
    'z' => 'z-[99999]',
    'backdrop' => 'bg-[rgba(24,24,27,0.95)]',
])

<template x-teleport="body">
    <div
        x-cloak
        x-show="{{ $state }}"
        x-transition.opacity.duration.150ms
        class="fixed inset-0 {{ $z }} {{ $backdrop }}"
        @keydown.escape.window="{{ $state }} = false"
        @click.self="{{ $state }} = false"
    >
        <div class="absolute inset-0 flex items-center justify-center p-4 sm:p-8" @click.self="{{ $state }} = false">
            <div class="relative w-full max-w-[min(96vw,1600px)]">
                {{ $slot }}
            </div>
        </div>

        <x-ui.modals.close-button :close="$state . ' = false'" tone="on-dark" class="absolute right-2 top-2"/>
    </div>
</template>