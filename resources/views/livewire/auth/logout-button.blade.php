<button
    wire:click="logout"
    type="button"
    class="md3-btn-text group !text-[var(--md-sys-color-error)] hover:!bg-[var(--md-sys-color-error-container)]/20 relative overflow-hidden"
    x-data="{ hover: false }"
    @mouseenter="hover = true"
    @mouseleave="hover = false">
    <span class="material-symbols-rounded text-xl transition-all duration-300"
          :class="{ '-translate-x-1 rotate-12': hover }">
        logout
    </span>
    <span class="font-semibold">خروج از سیستم</span>
    <div class="absolute inset-0 bg-[var(--md-sys-color-error)] opacity-0 transition-opacity duration-200"
         :class="{ 'opacity-[0.08]': hover }"></div>
</button>
