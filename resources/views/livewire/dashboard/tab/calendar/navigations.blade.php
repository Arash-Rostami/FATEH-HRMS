<div class="flex items-center justify-between shrink-0 px-6 py-6 md:px-10">
    <div class="flex flex-col gap-1">
        <h2 class="text-[var(--md-sys-color-on-surface)] text-2xl md:text-4xl font-black font-yekan tracking-tight">
            {{ $this->currentMonthName }}
        </h2>
    </div>

    <div
        class="flex items-center bg-[var(--md-sys-color-surface-container-high)] rounded-2xl p-1.5 border border-[var(--md-sys-color-outline-variant)]/30">
        <button
            wire:click="prevMonth"
            class="w-10 h-10 flex items-center justify-center hover:bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] rounded-xl transition-all active:scale-90"
        >
            <span class="material-symbols-rounded">chevron_right</span>
        </button>

        <button
            wire:click="goToToday"
            class="px-4 text-sm font-bold text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/5 rounded-lg py-2 transition-colors"
        >
            امروز
        </button>

        <button
            wire:click="nextMonth"
            class="w-10 h-10 flex items-center justify-center hover:bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] rounded-xl transition-all active:scale-90"
        >
            <span class="material-symbols-rounded">chevron_left</span>
        </button>
    </div>
</div>
