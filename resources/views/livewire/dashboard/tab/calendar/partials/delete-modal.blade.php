<div
    x-show="$wire.isDeleteModalOpen"
    x-transition.opacity.duration.300ms
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    style="display: none;"
>
    <div
        x-show="$wire.isDeleteModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        @click.away="$wire.set('isDeleteModalOpen', false)"
        class="bg-[var(--md-sys-color-surface)] w-full max-w-sm rounded-[28px] shadow-2xl overflow-hidden flex flex-col items-center p-6 text-center"
    >
        <div class="w-16 h-16 rounded-full bg-[var(--md-sys-color-error-container)] flex items-center justify-center mb-4">
            <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-on-error-container)]">delete_forever</span>
        </div>

        <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-2">
            حذف رویداد
        </h3>

        <p class="text-[var(--md-sys-color-on-surface-variant)] mb-6 text-sm">
            آیا از حذف این رویداد اطمینان دارید؟ این عملیات قابل بازگشت نیست.
        </p>

        <div class="flex gap-3 w-full">
            <button
                wire:click="$set('isDeleteModalOpen', false)"
                class="flex-1 py-3 rounded-xl text-[var(--md-sys-color-primary)] font-medium hover:bg-[var(--md-sys-color-primary-container)] transition-colors"
            >
                انصراف
            </button>
            <button
                wire:click="deleteEvent"
                class="flex-1 py-3 rounded-xl bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] font-bold shadow-lg shadow-[var(--md-sys-color-error)]/30 hover:scale-105 active:scale-95 transition-all"
            >
                حذف کن
            </button>
        </div>
    </div>
</div>
