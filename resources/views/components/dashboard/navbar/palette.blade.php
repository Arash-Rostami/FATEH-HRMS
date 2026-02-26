<div class="relative" x-data="{ open: false }">
    <button @click="open = !open"
            class="group relative w-10 h-10 rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 active:scale-95 transition-all duration-200 flex items-center justify-center"
            :class="open ? 'bg-[var(--md-sys-color-on-primary)]/10' : ''">
        <span class="material-symbols-rounded text-[22px]">palette</span>
        <x-dashboard.tooltip text="شخصی‌سازی ظاهر" position="bottom" />
    </button>

    <div x-show="open" @click.outside="open = false"
         class="absolute left-0 mt-3 p-3 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-2xl z-50 flex flex-col gap-3 min-w-[50px] animate-slide-down"
         style="display: none;">

        <button @click="$store.theme.toggleMode()"
                class="group relative w-10 h-10 rounded-full flex items-center justify-center bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] hover:brightness-95 transition-all mx-auto">
                    <span class="material-symbols-rounded text-[22px]"
                          x-text="$store.theme.isDark ? 'dark_mode' : 'light_mode'"></span>
            <x-dashboard.tooltip text="تغییر حالت شب/روز" position="right" />
        </button>

        <div class="h-px bg-[var(--md-sys-color-outline-variant)] opacity-50"></div>

        <template x-for="color in $store.theme.colors" :key="color.name">
            <button @click="$store.theme.set(color.name); open = false"
                    class="group relative w-8 h-8 rounded-full border border-[var(--md-sys-color-outline-variant)] hover:scale-110 transition-transform mx-auto shadow-sm"
                    :style="'background: ' + color.color">
                <x-dashboard.tooltip x-text="color.title" position="right" />
            </button>
        </template>
    </div>
</div>
