<section x-show="recent.length" x-cloak class="relative">
    <div class="flex items-center justify-between mb-4 px-1">
        <div class="flex items-center gap-3">
            <div
                class="w-9 h-9 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center shadow-sm">
                <span class="material-symbols-rounded text-lg">history</span>
            </div>
            <h3 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">اخیراً باز شده</h3>
        </div>
        <button @click="clearRecent()"
                class="text-xs text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] transition-colors flex items-center gap-1">
            <span class="material-symbols-rounded text-[16px] leading-none">delete_sweep</span>
            پاک کردن
        </button>
    </div>
    <div class="flex overflow-x-auto gap-3 pb-2 scrollbar-hide px-1">
        <template x-for="item in recent" :key="item.id">
            <a :href="item.url"
               :target="item.internal ? '_self' : '_blank'"
               @click="recordClick(item)"
               class="snap-start shrink-0 w-32 group/rc cursor-pointer focus:outline-none">
                <div
                    class="w-full rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 px-3 py-3 flex flex-col items-center gap-2 shadow-sm transition-all duration-300 group-hover/rc:shadow-md group-hover/rc:-translate-y-0.5 group-hover/rc:border-[var(--md-sys-color-tertiary)]/40">
                    <div
                        class="p-2 rounded-lg bg-[var(--md-sys-color-tertiary-container)]/60 text-[var(--md-sys-color-on-tertiary-container)]">
                        <span class="material-symbols-rounded text-xl leading-none">star</span>
                    </div>
                    <span
                        class="text-xs font-medium text-[var(--md-sys-color-on-surface)] line-clamp-1 text-center w-full"
                        x-text="item.title"></span>
                </div>
            </a>
        </template>
    </div>
</section>
