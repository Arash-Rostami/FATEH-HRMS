@props(['name', 'title' => null])

<template x-teleport="body">

    <div
        x-data="{ show: false, name: '{{ $name }}' }"
        x-show="show"
        x-on:open-modal.window="show = ($event.detail.name === name)"
        x-on:close-modal.window="show = false"
        x-on:keydown.escape.window="show = false"
        style="display: none;"
        class="fixed inset-0 z-[100] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center animate-slide-down"
        x-cloak
    >
        <div x-show="show" class="fixed inset-0 transform transition-all" x-on:click="show = false"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-[var(--md-sys-color-primary)]/60"></div>
        </div>

        <div x-show="show"
             class="mb-6 relative transform rounded-2xl overflow-hidden glass-panel transition-all sm:w-full sm:max-w-2xl sm:mx-auto bg-[var(--md-sys-color-surface)]"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            @if($title)
                <div
                    class="bg-[var(--md-sys-color-surface-container)] px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)] flex justify-between items-center">
                    <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">{{ $title }}</h3>
                    <button x-on:click="show = false"
                            class="text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-error)] transition-colors rounded-lg p-1 hover:bg-[var(--md-sys-color-error-container)]">
                        <span class="sr-only">بستن</span>
                        <span class="material-symbols-rounded text-2xl">close</span>
                    </button>
                </div>
            @endif

            <div class="p-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</template>
