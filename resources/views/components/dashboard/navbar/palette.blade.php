<div class="relative" x-data="{ open: false, page: 0, perPage: 5, groupTitles: ['تیره', 'میانه', 'روشن'] }">
    <button @click="open = !open"
            class="group relative w-10 h-10 rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 active:scale-95 transition-all duration-200 flex items-center justify-center"
            :class="open ? 'bg-[var(--md-sys-color-on-primary)]/10' : ''">
        <span class="material-symbols-rounded text-[22px]">palette</span>
        <x-dashboard.tooltip text="شخصی‌سازی ظاهر" position="bottom"/>
    </button>

    <div x-show="open" @click.outside="open = false"
         class="absolute left-0 mt-3 p-3 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-2xl z-50 flex flex-col gap-2 min-w-[72px] animate-slide-down"
         style="display: none;">

        <button @click="$store.theme.toggleMode()"
                class="group relative w-10 h-10 rounded-full flex items-center justify-center bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] hover:brightness-95 transition-all mx-auto">
            <span class="material-symbols-rounded text-[22px]"
                  x-text="$store.theme.isDark ? 'dark_mode' : 'light_mode'"></span>
            <x-dashboard.tooltip text="تغییر حالت شب/روز" position="right"/>
        </button>

        <div class="h-px bg-[var(--md-sys-color-outline-variant)] opacity-50"></div>

        <button
            @click="page = (page - 1 + groupTitles.length) % groupTitles.length"
            :title="['روشن','تیره','میانه'][page]"
            class="w-6 h-6 rounded-full flex items-center justify-center mx-auto text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all active:scale-90">
            <span class="material-symbols-rounded text-[16px]">keyboard_arrow_up</span>
        </button>

        <span x-text="groupTitles[page]"
              class="text-[10px] font-medium text-center text-[var(--md-sys-color-on-surface-variant)] tracking-widest leading-none mx-auto opacity-60"></span>
        <hr class="mb-1 pt-0 mt-0">
        <template x-for="color in $store.theme.colors.slice(page * perPage, page * perPage + perPage)"
                  :key="color.name">
            <button @click="$store.theme.set(color.name); open = false"
                    :title="color.title"
                    class="relative w-8 h-8 rounded-full mx-auto shadow-md transition-all duration-200 hover:scale-110 active:scale-95 flex items-center justify-center"
                    :style="'background:' + color.color"
                    :class="$store.theme.current === color.name
                        ? 'scale-110 ring-2 ring-white/60 ring-offset-2 ring-offset-[var(--md-sys-color-surface)] shadow-xl animate-pulse'
                        : 'ring-1 ring-black/10'">
                <span x-show="$store.theme.current === color.name"
                      class="material-symbols-rounded text-[var(--header-border-color)] !text-sm drop-shadow">check</span>
            </button>
        </template>

        <button
            @click="page = (page + 1) % groupTitles.length"
            :title="['میانه','روشن','تیره'][page]"
            class="w-6 h-6 rounded-full flex items-center justify-center mx-auto text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all active:scale-90">
            <span class="material-symbols-rounded text-[16px]">keyboard_arrow_down</span>
        </button>
    </div>
</div>
