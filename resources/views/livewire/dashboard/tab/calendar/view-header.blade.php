<div class="flex items-center justify-between w-full mb-4 md:mb-5"
     :class="$wire.view === 'month' ? 'p-0' : 'p-4'"
>
    <h2 class="flex items-center justify-center h-10 md:h-12 px-4 md:px-5 bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-high)_70%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] rounded-xl md:rounded-2xl shadow-sm text-base sm:text-lg md:text-xl font-black text-[var(--md-sys-color-on-surface)] tracking-tight shrink-0 slide-up">
        <span>{{ convertToPersian($this->rangeLabel) }}</span>
    </h2>

    <div class="flex items-center gap-2 md:gap-3 slide-up" style="animation-delay: 0.05s">
        <div class="flex items-center h-10 md:h-12 bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-high)_70%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] rounded-xl md:rounded-2xl shadow-sm p-1">
            <button
                wire:click="prevPeriod"
                class="flex items-center justify-center w-8 h-8 md:w-10 md:h-10 text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] rounded-lg transition-all duration-200 active:scale-95"
            >
                <span class="material-symbols-rounded text-[20px] md:text-[24px]">chevron_right</span>
            </button>

            <button
                wire:click="goToToday"
                class="flex items-center justify-center h-8 md:h-10 px-3 md:px-4 mx-0.5 text-xs md:text-sm font-bold text-[var(--md-sys-color-primary)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] rounded-lg transition-all duration-200 active:scale-95"
            >
                امروز
            </button>

            <button
                wire:click="nextPeriod"
                class="flex items-center justify-center w-8 h-8 md:w-10 md:h-10 text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] rounded-lg transition-all duration-200 active:scale-95"
            >
                <span class="material-symbols-rounded text-[20px] md:text-[24px]">chevron_left</span>
            </button>
        </div>

        <button
            type="button"
            @click="toggleMaximize()"
            :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
            :class="max ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent shadow-md' : 'bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-high)_70%,transparent)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] shadow-sm'"
            class="flex items-center justify-center shrink-0 w-10 h-10 md:w-12 md:h-12 border rounded-xl md:rounded-2xl transition-all duration-200 active:scale-95"
        >
            <span class="material-symbols-rounded text-[20px] md:text-[24px]" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
        </button>
    </div>
</div>
