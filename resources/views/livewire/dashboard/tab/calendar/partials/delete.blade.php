<div
    x-show="$wire.isDeleteModalOpen"
    class="fixed inset-0 z-[70] flex items-center justify-center p-4"
    style="display: none;"
>
    <div
        x-show="$wire.isDeleteModalOpen"
        x-transition.opacity.duration.300ms
        @click="$wire.set('isDeleteModalOpen', false)"
        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
    ></div>

    <div
        x-show="$wire.isDeleteModalOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        class="bg-[var(--md-sys-color-surface)] w-full max-w-sm rounded-[2.5rem] shadow-2xl p-8 text-center relative z-10 border border-[var(--md-sys-color-outline-variant)]/20"
    >
        <div class="w-20 h-20 mx-auto rounded-full bg-[var(--md-sys-color-error-container)] flex items-center justify-center mb-6 animate-pulse">
            <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-on-error-container)]">delete_forever</span>
        </div>

        <h3 class="text-2xl font-black text-[var(--md-sys-color-on-surface)] mb-3">
            حذف رویداد؟
        </h3>

        <p class="text-[var(--md-sys-color-on-surface-variant)] mb-8 leading-relaxed">
            آیا مطمئن هستید که می‌خواهید این رویداد را حذف کنید؟ <br>
            <span class="text-xs text-[var(--md-sys-color-error)] font-bold">این عملیات غیرقابل بازگشت است.</span>
        </p>

        <div class="flex gap-4">
            <button
                wire:click="$set('isDeleteModalOpen', false)"
                class="flex-1 py-3.5 rounded-[1.25rem] border border-[var(--md-sys-color-outline-variant)] text-[var(--md-sys-color-on-surface-variant)] font-bold hover:bg-[var(--md-sys-color-surface-variant)] transition-colors"
            >
                لغو
            </button>
            <button
                wire:click="deleteEvent"
                class="flex-1 py-3.5 rounded-[1.25rem] bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] font-bold shadow-lg shadow-[var(--md-sys-color-error)]/30 hover:scale-105 active:scale-95 transition-all"
            >
                بله، حذف کن
            </button>
        </div>
    </div>
</div>
