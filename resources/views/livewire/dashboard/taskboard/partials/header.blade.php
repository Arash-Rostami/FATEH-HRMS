<div class="flex flex-col sm:flex-row sm:items-center justify-around gap-4 md:gap-5 pt-4 md:pt-8">
    <!-- Title Section -->
    <div class="flex items-center gap-3 md:gap-4">
        <!-- Tonal Icon Chip -->
        <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-sm">
            <span class="material-symbols-rounded text-2xl md:text-3xl">dashboard</span>
        </div>

        <!-- Title & Subtitle -->
        <div class="flex flex-col">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight">
                برد وظایف
            </h1>
            <p class="text-[11px] sm:text-xs text-[var(--md-sys-color-on-surface-variant)] mt-0.5">
                مدیریت وظایف و پیگیری وضعیت کارها
            </p>
        </div>
    </div>

    <!-- Action Button -->
    <button
        wire:click="openCreateModal"
        class="group flex items-center justify-center gap-2 min-h-[44px] sm:min-h-[48px] px-5 md:px-6 py-3 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] active:scale-[0.97] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40 focus:ring-offset-2"
    >
        <span class="material-symbols-rounded text-xl group-hover:rotate-90 transition-transform duration-300">add</span>
        <span class="font-bold text-sm tracking-wide">وظیفه جدید</span>
    </button>
</div>
