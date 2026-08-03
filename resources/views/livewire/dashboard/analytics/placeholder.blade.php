<div class="w-full h-full flex flex-col gap-5 lg:gap-6 animate-pulse" dir="rtl" role="status" aria-label="در حال بارگذاری تحلیل‌های سازمانی">
    <div class="flex flex-wrap gap-2">
        @for ($i = 0; $i < 4; $i++)
            <div class="h-10 w-40 rounded-2xl bg-[var(--md-sys-color-surface-variant)]/40"></div>
        @endfor
    </div>

    <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)] shadow-sm min-h-[420px] flex flex-col items-center justify-center gap-3">
        <span class="material-symbols-rounded text-5xl text-[var(--md-sys-color-on-surface-variant)] animate-spin" aria-hidden="true">progress_activity</span>
        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">در حال بارگذاری تحلیل‌های منابع انسانی…</p>
    </div>
</div>
