<div class="flex items-center justify-between gap-3 mb-4 p-5" dir="rtl">
    <div class="flex items-center gap-3">
        <div class="relative shrink-0">
            <img
                class="h-10 w-10 rounded-xl object-cover ring-1 ring-[var(--md-sys-color-outline-variant)]"
                src="{{ $presenter->avatarUrl($feed->user) }}"
                alt="{{ $feed->user?->name ?? 'Guest' }}"
            >
        </div>

        <div>
            <div class="flex items-center gap-2">
                <h4 class="text-sm font-semibold leading-tight text-[var(--md-sys-color-on-surface)]">
                    {{ $feed->user?->name ?? 'کاربر ناشناس' }}
                </h4>

                <span
                    title="{{ $feed->category ?? 'عمومی' }}"
                    class="flex cursor-pointer items-center justify-center rounded-full bg-[var(--md-sys-color-surface)] ring-2 ring-[var(--md-sys-color-surface)] text-[10px] shadow-sm"
                >
                    {{ $presenter->categoryEmoji($feed) }}
                </span>
            </div>

            <p class="mt-0.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                {{ $feed->created_at ? jdate($feed->created_at)->ago() : '' }}
            </p>
        </div>
    </div>

    <button
        @click="toggleMaximize(feed($el))"
        :title="maximizedFeed === feed($el) ? 'کوچک کردن' : 'بزرگ کردن'"
        :class="{ '!bg-[var(--md-sys-color-primary-container)] !text-[var(--md-sys-color-on-primary-container)]': maximizedFeed === feed($el) }"
        class="ripple-effect shrink-0 flex h-9 w-9 items-center justify-center rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] transition-all duration-200 active:scale-95"
    >
        <span
            class="material-symbols-rounded text-[18px]"
            x-text="maximizedFeed === feed($el) ? 'close_fullscreen' : 'open_in_full'"
        ></span>
    </button>
</div>