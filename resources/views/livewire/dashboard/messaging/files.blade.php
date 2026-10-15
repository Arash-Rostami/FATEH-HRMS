<div x-cloak x-show="filesOpen" class="flex flex-1 min-h-0 flex-col overflow-hidden">
    <div class="flex-shrink-0 px-4 pt-3 pb-2">
        <div class="grid grid-cols-4 gap-1 rounded-xl bg-[var(--md-sys-color-surface-container)] p-1" role="tablist" aria-label="فیلتر بر اساس نوع فایل">
            @foreach([['all', 'همه', 'grid_view'], ['image', 'عکس‌ها', 'image'], ['video', 'ویدیوها', 'movie'], ['doc', 'اسناد', 'description']] as $_filter)
                <button type="button"
                        role="tab"
                        x-on:click="filesFilter = '{{ $_filter[0] }}'"
                        :aria-selected="filesFilter === '{{ $_filter[0] }}'"
                        :class="filesFilter === '{{ $_filter[0] }}'
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm'
                            : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/50 hover:text-[var(--md-sys-color-on-surface)]'"
                        class="flex flex-col items-center justify-center gap-0.5 rounded-lg py-1.5 text-[10px] font-bold transition-all active:scale-95">
                    <span class="material-symbols-rounded text-[15px]" aria-hidden="true">{{ $_filter[2] }}</span>
                    <span>{{ $_filter[1] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="flex-1 min-h-0 overflow-y-auto contact-scrollbar px-4 pb-4 pt-1">
        <div x-show="filesLoading" class="flex h-full items-center justify-center py-12">
            <x-ui.loaders.spin-badge text="در حال بارگذاری پیوست‌ها…"/>
        </div>

        <div x-show="!filesLoading && !filesList.length" class="flex h-full flex-col items-center justify-center gap-2 py-12 text-center">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] opacity-70">
                <span class="material-symbols-rounded text-2xl">folder_off</span>
            </div>
            <p class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">هنوز فایلی مبادله نشده است.</p>
        </div>

        <div x-show="!filesLoading && filesList.length && !filesList.some(i => filesFilter === 'all' || filesFilter === i.kind)"
             class="flex h-full flex-col items-center justify-center gap-2 py-12 text-center">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] opacity-70">
                <span class="material-symbols-rounded text-2xl">filter_alt_off</span>
            </div>
            <p class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">با این فیلتر چیزی یافت نشد.</p>
        </div>

        <div class="space-y-3" x-show="!filesLoading && filesList.length">
            <div class="grid grid-cols-3 gap-2"
                 x-show="filesList.some(i => i.kind !== 'doc' && (filesFilter === 'all' || filesFilter === i.kind))">
                <template x-for="item in filesList.filter(i => i.kind !== 'doc' && (filesFilter === 'all' || filesFilter === i.kind))"
                          :key="item.key">
                    <a :href="item.url"
                       data-fancybox="conv-files"
                       :data-caption="item.name + (item.time ? ' · ' + item.time : '')"
                       :title="item.name"
                       class="group relative aspect-square overflow-hidden rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface-container-low)]">
                        <img x-show="item.kind === 'image'"
                             :src="item.url"
                             :alt="item.name"
                             loading="lazy"
                             class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"/>
                        <template x-if="item.kind === 'video'">
                            <span class="relative flex h-full w-full items-center justify-center bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]">
                                <span class="material-symbols-rounded text-2xl opacity-50" aria-hidden="true">movie</span>
                                <span class="absolute inset-0 m-auto flex h-7 w-7 items-center justify-center rounded-full bg-black/45 text-white transition-transform group-hover:scale-110"
                                      aria-hidden="true">
                                    <span class="material-symbols-rounded text-base">play_arrow</span>
                                </span>
                            </span>
                        </template>
                    </a>
                </template>
            </div>

            <div class="space-y-1.5"
                 x-show="filesList.some(i => i.kind === 'doc' && (filesFilter === 'all' || filesFilter === 'doc'))">
                <template x-for="item in filesList.filter(i => i.kind === 'doc' && (filesFilter === 'all' || filesFilter === 'doc'))"
                          :key="item.key">
                    <div class="flex items-center gap-2.5 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] px-2.5 py-2 transition-colors hover:bg-[var(--md-sys-color-surface-container)]">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-base" aria-hidden="true">description</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[11px] font-bold text-[var(--md-sys-color-on-surface)]" x-text="item.name"></p>
                            <p class="truncate text-[10px] text-[var(--md-sys-color-on-surface-variant)]"
                               x-text="[item.size_label, item.time, item.sender_name].filter(Boolean).join(' · ')"></p>
                        </div>
                        <button type="button"
                                x-on:click="downloadGalleryFile(item.msg_id, item.index)"
                                :aria-label="'دانلود ' + item.name"
                                title="دانلود"
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-surface-variant)]/60 text-[var(--md-sys-color-on-surface-variant)] transition-all hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] active:scale-90">
                            <span class="material-symbols-rounded text-[15px]">download</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
