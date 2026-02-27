<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)]">
            <span class="material-symbols-rounded text-[28px]">dashboard</span>
        </div>
        <div class="flex flex-col">
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">
                برد وظایف
            </h1>
            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-0.5">
                مدیریت وظایف و پیگیری وضعیت کارها
            </p>
        </div>
    </div>
    <button x-data @click="$dispatch('open-modal', 'create-task-modal')" class="relative inline-flex items-center justify-center gap-2 px-6 h-11 rounded-xl font-medium overflow-hidden bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm hover:brightness-105 hover:-translate-y-0.5 hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)] active:scale-[0.98] transition-all duration-300">
        <span class="material-symbols-rounded text-xl transition-transform duration-300">add</span>
        <span class="font-bold tracking-wide">وظیفه جدید</span>
    </button>
</div>
