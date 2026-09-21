<div class="relative" x-data="{ open: false }">
    <button @click="open = !open"
            @class([
                'group relative w-10 h-10 active:scale-95 transition items-center justify-center',
                'hidden md:flex rounded-xl hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)] right-2' => request()->is('admin*'),
                'flex' => !request()->is('admin*'),
                'text-[var(--md-sys-color-on-primary-container)]' => request()->is('*login*'),
            ])
            :class="open ? 'bg-[var(--md-sys-color-on-primary)]/10' : ''">
        <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100"
              data-icon="palette">
            palette
        </span>
        <x-ui.modals.tooltip text="شخصی‌سازی ظاهر" position="bottom"/>
    </button>

    <div x-show="open" @click.outside="open = false"
         class="absolute left-0 mt-3 p-3 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-2xl z-50 flex flex-col gap-2 min-w-[72px] animate-slide-down"
         style="display: none;">
        <x-dashboard.settings.theme-swatches close-action="open = false"/>
    </div>
</div>
