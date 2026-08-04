<button type="button" x-on:click="openMessageSearch()" aria-label="جستجوی پیام" title="جستجوی پیام"
        class="flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
        :class="searchMessages ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]'">
    <span class="material-symbols-rounded text-base">search</span>
</button>

{{ $sound ?? '' }}

<button type="button" @click="toggleHighlight()" aria-label="پیش زمینه چت" title="پیش زمینه چت"
        class="flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
        :class="isHighlighted ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]'">
    <span class="material-symbols-rounded text-base" x-text="isHighlighted ? 'hide_image' : 'texture'"></span>
</button>

<button type="button" @click="toggleMaximize()"
        :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
        aria-label="تغییر اندازه"
        class="flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
        :class="max ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]'">
    <span class="material-symbols-rounded text-base" x-text="max ? 'close_fullscreen' : 'open_in_full'" aria-hidden="true"></span>
</button>

<button type="button" x-on:click="showInfo = !showInfo" aria-label="اطلاعات بیشتر" title="اطلاعات بیشتر"
        class="flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-95">
    <span class="material-symbols-rounded text-base">info</span>
</button>