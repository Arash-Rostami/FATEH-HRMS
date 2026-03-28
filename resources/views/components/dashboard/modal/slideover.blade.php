@props(['show', 'maxWidth' => 'max-w-3xl'])

<template x-teleport="body">
    <div
        class="fixed inset-0 z-[100] overflow-hidden"
        x-show="{{ $show }}"
        x-cloak
    >
        <div
            class="fixed inset-0 bg-black/40"
            x-show="{{ $show }}"
            x-transition:enter="animate-backdrop-in"
            x-transition:leave="animate-backdrop-out"
            @click="{{ $show }} = false"
            aria-hidden="true"
        ></div>

        <div
            class="fixed inset-y-0 left-0 w-full {{ $maxWidth }} bg-[var(--md-sys-color-surface)] shadow-2xl flex flex-col border-r border-[var(--md-sys-color-outline-variant)]/20"
            x-show="{{ $show }}"
            x-transition:enter="animate-slide-over-in"
            x-transition:leave="animate-slide-over-out"
        >
            {{ $slot }}
        </div>
    </div>
</template>
