<div class="px-4 md:px-12 pt-2 md:pt-4 flex items-center justify-end mb-2 md:mb-4 relative z-10">
    <div
        class="hidden md:flex bg-[var(--md-sys-color-surface-container-high)] p-1 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm">
        <button @click="view = 'card'; $wire.toggleView('card')"
                :class="view === 'card' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                class="p-2 rounded-lg transition-all duration-300 flex items-center justify-center w-10 h-10">
            <span class="material-symbols-rounded">grid_view</span>
        </button>
        <button @click="view = 'list'; $wire.toggleView('list')"
                :class="view === 'list' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                class="p-2 rounded-lg transition-all duration-300 flex items-center justify-center w-10 h-10">
            <span class="material-symbols-rounded">view_list</span>
        </button>
    </div>
</div>
