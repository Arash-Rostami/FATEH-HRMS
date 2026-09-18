<div class="flex items-center justify-end w-full">
    <button type="button"
            @click="toggleSettings()"
            title="تنظیمات جدول"
            :class="openSettings
                    ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)]'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]'"
            class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case">
        <span class="material-symbols-rounded text-[18px]">tune</span>
    </button>

    <button type="button"
            @click="toggleMaximize()"
            :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
            :class="{ 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]': max, 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]': !max }"
            class="inline-flex items-center justify-center p-1 pr-3 rounded-lg transition-colors normal-case">
        <span class="material-symbols-rounded text-[18px]" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
    </button>
</div>