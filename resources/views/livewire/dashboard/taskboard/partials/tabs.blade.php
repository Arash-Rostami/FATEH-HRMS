<div class="flex p-1 bg-[var(--md-sys-color-surface-container-high)] rounded-2xl w-fit">
    <button
        wire:click="switchTab('my-tasks')"
        class="relative px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 z-10 flex items-center gap-2
        {{ $activeTab === 'my-tasks'
            ? 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] shadow-sm'
            : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-highest)]' }}"
    >
        <span class="material-symbols-rounded text-lg">person</span>
        وظایف من
    </button>

    <button
        wire:click="switchTab('assigned-tasks')"
        class="relative px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 z-10 flex items-center gap-2
        {{ $activeTab === 'assigned-tasks'
            ? 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] shadow-sm'
            : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-highest)]' }}"
    >
        <span class="material-symbols-rounded text-lg">assignment_ind</span>
        محول شده
    </button>
</div>
