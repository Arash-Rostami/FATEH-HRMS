<div class="flex justify-center w-full">
    <div class="flex p-1 bg-[var(--md-sys-color-surface-container-high)] rounded-xl w-full sm:w-fit shadow-sm text-center">
        <button
            wire:click="switchTab('my-tasks')"
            class="relative flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg text-xs sm:text-sm font-bold transition-all duration-300 z-10 flex items-center justify-center gap-2 min-h-[44px]
            {{ $activeTab === 'my-tasks'
                ? 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] shadow-sm'
                : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container)]' }}"
        >
            <span class="material-symbols-rounded text-base sm:text-lg">person</span>
            <span class="whitespace-nowrap">وظایف من</span>
        </button>

        <button
            wire:click="switchTab('assigned-tasks')"
            class="relative flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg text-xs sm:text-sm font-bold transition-all duration-300 z-10 flex items-center justify-center gap-2 min-h-[44px]
            {{ $activeTab === 'assigned-tasks'
                ? 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] shadow-sm'
                : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container)]' }}"
        >
            <span class="material-symbols-rounded text-base sm:text-lg">assignment_ind</span>
            <span class="whitespace-nowrap">محول شده</span>
        </button>
    </div>
</div>
