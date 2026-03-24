<div class="flex p-1 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 w-fit mb-6 shadow-sm overflow-hidden relative">
    <button wire:click="switchTab('my-tasks')" class="relative px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 z-10 flex items-center gap-2
        {{ $activeTab === 'my-tasks'
            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]'
            : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant)]/60' }}">
        <span class="material-symbols-rounded text-lg">person</span>
        وظایف من
    </button>
    <button wire:click="switchTab('assigned-tasks')" class="relative px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 z-10 flex items-center gap-2
        {{ $activeTab === 'assigned-tasks'
            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]'
            : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant)]/60' }}">
        <span class="material-symbols-rounded text-lg">assignment_ind</span>
        محول شده
    </button>
</div>
