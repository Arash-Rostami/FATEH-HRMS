@props(['show', 'maxWidth' => 'max-w-3xl'])

<template x-teleport="body">
    <div
        class="fixed inset-0 z-[100] overflow-hidden"
        x-show="{{ $show }}"
        x-cloak
    >
        <div
            class="fixed inset-0 bg-[var(--md-sys-color-primary)]/60"
            x-show="{{ $show }}"
            x-transition:enter="animate-backdrop-in"
            x-transition:leave="animate-backdrop-out"
            @click="{{ $show }} = false"
            aria-hidden="true"
        ></div>

        <div
            @class([
                    'fixed inset-y-0 left-0 w-full ' . $maxWidth . ' bg-[var(--md-sys-color-surface)] shadow-2xl flex flex-col border-r border-[var(--md-sys-color-outline-variant)]/20 will-change-transform',
                    'animate-slide-from-left' => $show,
                    'animate-slide-to-left' => !$show,
                ])
            x-show="{{ $show }}"
            x-transition:leave="animate-slide-to-left"
            x-transition:leave.duration.400ms
        >
            {{ $slot }}
        </div>
    </div>
</template>
