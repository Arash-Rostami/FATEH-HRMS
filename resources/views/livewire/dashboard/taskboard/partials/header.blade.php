<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <div class="p-3 rounded-2xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-lg shadow-[var(--md-sys-color-primary)]/20">
            <span class="material-symbols-rounded text-3xl">dashboard</span>
        </div>
        <div class="flex flex-col">
            <h1 class="text-2xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight">
                برد وظایف
            </h1>
            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">
                مدیریت وظایف و پیگیری وضعیت کارها
            </p>
        </div>
    </div>

    <button
        x-data
        @click="$dispatch('open-modal', 'create-task-modal')"
        class="group relative flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-lg hover:shadow-xl hover:shadow-[var(--md-sys-color-primary)]/30 active:scale-95 transition-all duration-300"
    >
        <span class="material-symbols-rounded text-xl group-hover:rotate-90 transition-transform duration-300">add</span>
        <span class="font-bold tracking-wide">وظیفه جدید</span>
    </button>
</div>
