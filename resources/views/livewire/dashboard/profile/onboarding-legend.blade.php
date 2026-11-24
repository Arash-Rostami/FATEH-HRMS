@php
    $rows = [
        ['icon' => 'apartment', 'color' => 'primary', 'label' => 'راهنمای شروع همکاری', 'text' => 'در این بخش، ویدیوها، برنامهٔ روز اول و راهنماهایی را ببینید که مدیر برای شروع همکاری آماده کرده است.'],
        ['icon' => 'person', 'color' => 'tertiary', 'label' => 'راهنمای عمومی و اختصاصی', 'text' => 'اگر راهنمایی برای شما آماده شده باشد، همان را می‌بینید. در غیر این صورت، راهنمای عمومی شرکت نمایش داده می‌شود.'],
        ['icon' => 'menu_book', 'color' => 'secondary', 'label' => 'باز کردن فایل‌های راهنما', 'text' => 'در «راهنماها و مستندات»، فایل PDF در زبانهٔ جدید باز می‌شود. برای فایل‌های دیگر، دریافت فایل شروع می‌شود.'],
        ['icon' => 'campaign', 'color' => 'gold', 'label' => 'نکات تکمیلی', 'text' => 'مدیر می‌تواند نکته‌های دیگری به «اطلاعات تکمیلی» اضافه کند. این نکته‌ها با برچسب «سفارشی» مشخص می‌شوند.'],
    ];

    $chipClasses = fn(string $color): string => match ($color) {
        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
        'gold' => 'bg-[var(--tool-gold-bg)] text-[var(--tool-gold-color)]',
    };
@endphp

<div class="space-y-2">
    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">آنبوردینگ فقط خواندنی است؛ نسخهٔ اختصاصی شما جای نسخهٔ عمومی می‌نشیند.</p>
    @foreach($rows as $row)
        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($row['color']) }}">
                <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
            </div>
        </div>
    @endforeach
</div>
