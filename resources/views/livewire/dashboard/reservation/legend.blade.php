@php
    $rows = [
        ['icon' => 'check_circle', 'color' => 'sapphire', 'label' => 'فعال', 'text' => 'رزرو شما فعال است و تا پایان بازهٔ رزروشده معتبر می‌ماند.'],
        ['icon' => 'autorenew', 'color' => 'sage', 'label' => 'آزادشده', 'text' => 'این رزرو پیش از پایان بازه توسط مدیریت آزاد شده است؛ همچنان در سهمیهٔ ماهانهٔ شما محاسبه می‌شود، ولی دیگر قابل لغو نیست.'],
        ['icon' => 'event_busy', 'color' => 'amethyst', 'label' => 'لغو توسط کاربر', 'text' => 'خودتان این رزرو را لغو کرده‌اید.'],
        ['icon' => 'gpp_bad', 'color' => 'gold', 'label' => 'لغو توسط ادمین', 'text' => 'این لغو را یک کاربر با دسترسی مدیریتی (ادمین یا توسعه‌دهنده) انجام داده است؛ معمولاً برای رفع تداخل یا تغییر برنامه.'],
    ];
@endphp

<div class="space-y-2">
    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">هر رزرو یکی از این چهار وضعیت را دارد؛ رنگ و آیکون هرکدام همین‌جا مشخص است.</p>

    @foreach($rows as $row)
        @php
            $chipClasses = match ($row['color']) {
                'sapphire' => 'bg-[var(--tool-sapphire-bg)] text-[var(--tool-sapphire-color)]',
                'gold' => 'bg-[var(--tool-gold-bg)] text-[var(--tool-gold-color)]',
                'amethyst' => 'bg-[var(--tool-amethyst-bg)] text-[var(--tool-amethyst-color)]',
                'sage' => 'bg-[var(--tool-sage-bg)] text-[var(--tool-sage-color)]',
            };
        @endphp
        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
            </div>
        </div>
    @endforeach

    <div class="mt-4 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/40">
        <div class="flex items-start gap-2 px-1">
            <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
            <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">رزرو «فعال» و «آزادشده» هر دو در سقف رزرو ماهانهٔ شما محاسبه می‌شوند؛ فقط رزرو «فعال» را می‌توانید خودتان لغو کنید.</p>
        </div>
    </div>
</div>
