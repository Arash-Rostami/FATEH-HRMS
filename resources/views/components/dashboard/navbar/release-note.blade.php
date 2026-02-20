<div class="relative group">
    <button wire:click="$dispatch('open-release-note')"
            class="w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)]/50 active:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 transition-all duration-200 flex items-center justify-center relative"
            title="یادداشت‌های انتشار">
        <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100 transition-opacity">new_releases</span>
        <span class="absolute top-2 right-2 w-2 h-2 bg-[var(--md-sys-color-primary)] rounded-full animate-pulse shadow-[0_0_8px_rgba(var(--md-sys-color-primary),0.6)]"></span>
    </button>
</div>
