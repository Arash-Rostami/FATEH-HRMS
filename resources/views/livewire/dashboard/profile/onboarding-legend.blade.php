@php
    $rows = [
        ['icon' => 'apartment', 'color' => 'primary', 'label' => 'فقط خواندنی، محتوای ادمین', 'text' => 'تمام بخش‌های آنبوردینگ (خوش‌آمدگویی، ویدیوها، ماموریت، چشم‌انداز، راهنماها، برنامه روز اول و نکات تکمیلی) توسط مدیر سیستم نوشته می‌شود؛ شما فقط آن‌ها را می‌بینید و ویرایش از طرف شما ممکن نیست.'],
        ['icon' => 'person', 'color' => 'tertiary', 'label' => 'نسخهٔ اختصاصی جای همه‌گیر', 'text' => 'اگر مدیر یک آنبوردینگ اختصاصی برای شما تعریف کرده باشد، آن جای نسخهٔ عمومی شرکت نمایش داده می‌شود؛ در غیر این صورت نسخهٔ مشترک (بدون کاربر اختصاصی) می‌آید.'],
        ['icon' => 'menu_book', 'color' => 'secondary', 'label' => 'PDF باز، بقیه دانلود', 'text' => 'در بخش «راهنماها و مستندات»، فایل‌های PDF در تب جدید باز می‌شوند ولی سایر فرمت‌ها مستقیم دانلود می‌شوند.'],
        ['icon' => 'campaign', 'color' => 'gold', 'label' => 'نکات سفارشی با نشان نقطه‌چین', 'text' => 'در بخش «اطلاعات تکمیلی»، نکاتِ ازپیش‌تعریف‌شده با حاشیهٔ معمولی می‌آیند ولی نکاتِ سفارشی (که مدیر با کلید دلخواه اضافه کرده) نشانِ «سفارشی» و حاشیهٔ نقطه‌چین دارند.'],
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
