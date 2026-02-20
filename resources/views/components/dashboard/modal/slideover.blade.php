@props(['show', 'maxWidth' => 'max-w-3xl'])

<div
    class="fixed inset-0 z-[100]"
    x-show="{{ $show }}"
    x-cloak
    style="display: none;"
>
    <div
        class="absolute inset-0 bg-black/40 transition-opacity duration-500"
        x-show="{{ $show }}"
        x-transition:enter="ease-out duration-500"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="{{ $show }} = false"
    ></div>

    <div
        class="absolute inset-y-0 left-0 w-full bg-[var(--md-sys-color-surface)] shadow-2xl flex flex-col transform transition-transform duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] border-r border-[var(--md-sys-color-outline-variant)]/20 {{ $maxWidth }}"
        x-show="{{ $show }}"
        x-transition:enter="translate-x-full rtl:-translate-x-full"
        x-transition:enter-start="translate-x-full rtl:-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="translate-x-full rtl:-translate-x-full"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full rtl:-translate-x-full"
    >
        {{ $slot }}
    </div>
</div>
