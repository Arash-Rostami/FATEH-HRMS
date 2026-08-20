<section x-data="{ open: @js($openDefault ?? false) }"
         x-show="recent.length"
         x-cloak class="relative mb-6 rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] transition-all duration-300">
    <div class="flex items-center justify-between p-4">
        <button @click="open = !open" type="button" class="flex items-center gap-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)] rounded-lg text-right group">
            <div class="w-9 h-9 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center shadow-sm transition-transform duration-300 group-hover:scale-105">
                <span class="material-symbols-rounded text-lg">history</span>
            </div>
            <h3 class="text-base font-bold text-[var(--md-sys-color-on-surface)] flex items-center gap-2">
                اخیراً باز شده
                <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-on-surface-variant)] transition-transform duration-300" :class="open ? 'rotate-180' : ''">expand_more</span>
            </h3>
        </button>

        <button @click.stop="clearRecent()" type="button" class="text-xs text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] transition-colors duration-200 flex items-center gap-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)] rounded-md px-2 py-1">
            <span class="material-symbols-rounded text-[16px] leading-none">delete_sweep</span>
            پاک کردن
        </button>
    </div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="px-4 pb-4">
        <div class="flex overflow-x-auto gap-3 pb-2 scrollbar-hide">
            <template x-for="item in recent" :key="item.id">
                <a :href="item.url"
                   :target="item.internal ? '_self' : '_blank'"
                   :rel="item.internal ? '' : 'noopener noreferrer'"
                   @click="recordClick(item)"
                   class="snap-start shrink-0 w-32 group/rc cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)] rounded-xl">
                    <div class="w-full rounded-xl bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] px-3 py-3 flex flex-col items-center gap-2 shadow-sm transition-all duration-300 group-hover/rc:shadow-md group-hover/rc:-translate-y-0.5 group-hover/rc:border-[var(--md-sys-color-tertiary)]/40">
                        <div class="p-2 rounded-lg bg-[var(--md-sys-color-tertiary-container)]/60 text-[var(--md-sys-color-on-tertiary-container)]">
                            <span class="material-symbols-rounded text-xl leading-none">star</span>
                        </div>
                        <span :title="item.title"
                              class="text-xs font-medium text-[var(--md-sys-color-on-surface)] line-clamp-1 text-center w-full"
                              x-text="item.title"></span>
                    </div>
                </a>
            </template>
        </div>
    </div>
</section>