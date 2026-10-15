<div x-show="bookmarksOpen" x-transition x-on:keydown.escape.window="bookmarksOpen = false" style="display:none;" class="absolute left-0 right-10 z-40 max-md:right-[-2rem]">
    <div class="rounded-2xl bg-[var(--md-sys-color-surface)] shadow-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] overflow-hidden">
        <div class="flex items-center gap-2 px-3 h-10 text-[12px] font-bold text-[var(--md-sys-color-on-surface)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_60%,transparent)]">
            <span class="material-symbols-rounded text-[16px] font-fill text-[var(--md-sys-color-primary)]">bookmarks</span>
            <span>{{ $title }}</span>
            <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]" x-show="bookmarkList.length" x-text="bookmarkList.length"></span>
        </div>
        <div class="max-h-72 overflow-y-auto contact-scrollbar border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
            <template x-for="b in bookmarkList" :key="b.id">
                <div class="flex items-stretch border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)] last:border-0 hover:bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_60%,transparent)] transition-colors">
                    <button type="button" x-on:click="focusBookmark(b.id)" class="flex-1 min-w-0 text-right px-3 py-2 cursor-pointer">
                        <span class="block text-[12px] leading-relaxed text-[var(--md-sys-color-on-surface)] line-clamp-2" x-text="b.body"></span>
                        <span class="block text-[10px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)]">
                            <span :class="b.is_mine ? 'font-semibold text-[var(--md-sys-color-primary)]' : ''" x-text="b.sender_name"></span>
                            <span x-show="b.time" class="opacity-50 mx-1">·</span>
                            <span x-show="b.time" dir="ltr" x-text="b.time"></span>
                        </span>
                    </button>
                    <button type="button" x-on:click="removeBookmark(b.id)" aria-label="برداشتن نشان پیام" title="برداشتن نشان پیام"
                            class="w-9 shrink-0 flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-error)] transition-colors">
                        <span class="material-symbols-rounded text-[16px]">bookmark_remove</span>
                    </button>
                </div>
            </template>
            <div x-show="!bookmarkList.length" class="px-3 py-5 text-center text-[11px] text-[var(--md-sys-color-on-surface-variant)]">{{ $empty }}</div>
        </div>
    </div>
</div>
