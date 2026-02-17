@props(['id', 'title', 'message', 'confirmText' => 'حذف', 'cancelText' => 'انصراف', 'confirmAction'])

<div
    x-data="{ show: false }"
    x-on:open-modal-{{ $id }}.window="show = true"
    x-on:close-modal-{{ $id }}.window="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-[100] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
    style="display: none;"
    dir="rtl"
>
    <!-- Backdrop -->
    <div x-show="show" class="fixed inset-0 transform transition-all"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="show = false"></div>
    </div>

    <!-- Modal Content -->
    <div x-show="show"
         class="mb-6 bg-[var(--md-sys-color-surface)] rounded-3xl overflow-hidden shadow-2xl transform transition-all sm:w-full sm:max-w-md sm:mx-auto border border-[var(--md-sys-color-outline-variant)]/20"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <span class="material-symbols-rounded text-3xl text-red-600">warning</span>
            </div>

            <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] mb-2">
                {{ $title }}
            </h3>

            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed">
                {{ $message }}
            </p>
        </div>

        <div class="bg-[var(--md-sys-color-surface-container-low)] px-6 py-4 flex flex-row-reverse gap-3 justify-center">
             <button type="button"
                    class="w-full inline-flex justify-center rounded-full border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm transition-all active:scale-95"
                    wire:click="{{ $confirmAction }}; show = false">
                {{ $confirmText }}
            </button>
            <button type="button"
                    class="w-full inline-flex justify-center rounded-full border border-[var(--md-sys-color-outline)]/20 shadow-sm px-4 py-2 bg-[var(--md-sys-color-surface)] text-base font-medium text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-highest)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--md-sys-color-primary)] sm:w-auto sm:text-sm transition-all active:scale-95"
                    @click="show = false">
                {{ $cancelText }}
            </button>
        </div>
    </div>
</div>
