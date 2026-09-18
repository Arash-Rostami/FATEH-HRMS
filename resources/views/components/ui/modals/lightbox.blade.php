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

                <button
                    @click="{{ $state }} = false"
                    class="absolute right-3 top-3 flex h-11 w-11 items-center justify-center rounded-xl bg-black/55 text-white shadow-lg transition hover:scale-105 hover:bg-black/75"
                >
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
        </div>
    </div>
</template>