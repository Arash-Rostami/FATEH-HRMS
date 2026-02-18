<div
    x-show="$wire.isDeleteModalOpen"
    x-transition.opacity.duration.300ms
    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md"
    style="display: none;"
>
    <div
        x-show="$wire.isDeleteModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-90 translate-y-4"
        @click.away="$wire.set('isDeleteModalOpen', false)"
        class="bg-[var(--md-sys-color-surface)] w-full max-w-sm rounded-[32px] shadow-2xl overflow-hidden flex flex-col items-center p-8 text-center border border-[var(--md-sys-color-outline-variant)]/50 relative"
    >
        {{-- Warning Icon with Glow --}}
        <div class="relative mb-6">
            <div class="absolute inset-0 bg-[var(--md-sys-color-error)]/20 blur-xl rounded-full"></div>
            <div class="w-20 h-20 rounded-full bg-[var(--md-sys-color-error-container)] flex items-center justify-center relative z-10 shadow-inner">
                <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-on-error-container)]">delete_forever</span>
            </div>
        </div>

        <h3 class="text-2xl font-bold text-[var(--md-sys-color-on-surface)] mb-2 font-yekan">
            حذف رویداد
        </h3>

        <p class="text-[var(--md-sys-color-on-surface-variant)] mb-8 text-sm leading-relaxed max-w-[250px]">
            آیا از حذف این رویداد اطمینان دارید؟ این عملیات قابل بازگشت نیست.
        </p>

        <div class="flex gap-4 w-full">
            <button
                wire:click="$set('isDeleteModalOpen', false)"
                class="flex-1 py-3.5 rounded-2xl text-[var(--md-sys-color-on-surface-variant)] font-bold hover:bg-[var(--md-sys-color-surface-variant)] transition-colors border border-[var(--md-sys-color-outline)]/20"
            >
                انصراف
            </button>
            <button
                wire:click="deleteEvent"
                class="flex-1 py-3.5 rounded-2xl bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] font-bold shadow-lg shadow-[var(--md-sys-color-error)]/30 hover:scale-105 active:scale-95 transition-all hover:shadow-xl hover:shadow-[var(--md-sys-color-error)]/40"
            >
                حذف کن
            </button>
        </div>
    </div>
</div>
