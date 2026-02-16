<div class="relative" x-data="{ open: false }">
    <button @click="open = !open"
            class="w-10 h-10 rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 active:scale-95 transition-all duration-200 flex items-center justify-center"
            :class="open ? 'bg-[var(--md-sys-color-on-primary)]/10' : ''">
        <span class="material-symbols-rounded text-[22px]">palette</span>
    </button>

    <div x-show="open" @click.outside="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         class="absolute left-0 mt-3 p-3 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-2xl z-50 flex flex-col gap-3 min-w-[50px]"
         style="display: none;">

        <button @click="$store.theme.toggleMode()"
                title="تغییر حالت شب/روز"
                class="w-10 h-10 rounded-full flex items-center justify-center bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] hover:brightness-95 transition-all mx-auto">
                    <span class="material-symbols-rounded text-[22px]"
                          x-text="$store.theme.isDark ? 'dark_mode' : 'light_mode'"></span>
        </button>

        <div class="h-px bg-[var(--md-sys-color-outline-variant)] opacity-50"></div>

        <template x-for="color in $store.theme.colors" :key="color.name">
            <button @click="$store.theme.set(color.name); open = false"
                    :title="color.name"
                    class="w-8 h-8 rounded-full border border-[var(--md-sys-color-outline-variant)] hover:scale-110 transition-transform relative mx-auto shadow-sm"
                    :style="'background: ' + color.color"></button>
        </template>
    </div>
</div>
