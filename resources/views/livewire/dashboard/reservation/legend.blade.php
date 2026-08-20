@php
    $tabs = [
        ['id' => 'statuses', 'icon' => 'flag', 'label' => 'وضعیت‌ها'],
        ['id' => 'history', 'icon' => 'history', 'label' => 'تاریخچه'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $statusRows = [
        ['icon' => 'check_circle', 'color' => 'sapphire', 'label' => 'فعال', 'text' => 'رزرو شما فعال است و تا پایان بازهٔ رزروشده معتبر می‌ماند.'],
        ['icon' => 'autorenew', 'color' => 'sage', 'label' => 'آزادشده', 'text' => 'این رزرو پیش از پایان بازه توسط مدیریت آزاد شده است؛ همچنان در سهمیهٔ ماهانهٔ شما محاسبه می‌شود، ولی دیگر قابل لغو نیست.'],
        ['icon' => 'event_busy', 'color' => 'amethyst', 'label' => 'لغو توسط کاربر', 'text' => 'خودتان این رزرو را لغو کرده‌اید.'],
        ['icon' => 'gpp_bad', 'color' => 'gold', 'label' => 'لغو توسط ادمین', 'text' => 'این لغو را یک کاربر با دسترسی مدیریتی (ادمین یا توسعه‌دهنده) انجام داده است؛ معمولاً برای رفع تداخل یا تغییر برنامه.'],
    ];

    $historyRows = [
        ['icon' => 'event_upcoming', 'color' => 'primary', 'label' => 'پیش‌رو', 'text' => 'رزروهای فعالِ آینده؛ تنها از این زبانه می‌توانید رزرو را لغو کنید.'],
        ['icon' => 'history', 'color' => 'tertiary', 'label' => 'قبلی', 'text' => 'رزروهای گذشتهٔ انجام‌شده؛ امکان لغو ندارند.'],
        ['icon' => 'event_busy', 'color' => 'error', 'label' => 'لغو شده', 'text' => 'رزروهای لغوشده (توسط شما یا ادمین)؛ نام رزرو به‌صورت خط‌خورده نمایش داده می‌شود.'],
        ['icon' => 'autorenew', 'color' => 'secondary', 'label' => 'آزادشده', 'text' => 'رزروهایی که پیش از پایان بازه آزاد کرده‌اید.'],
    ];

    $notes = [
        'رزرو «فعال» و «آزادشده» هر دو در سقف رزرو ماهانهٔ شما محاسبه می‌شوند؛ فقط رزرو «فعال» را می‌توانید خودتان لغو کنید.',
        'رزروهای تکرارشوندهٔ یک سری در تاریخچه زیر یک ردیف با نشان «repeat + N» جمع می‌شوند؛ اگر لغو یک‌تکه فعال نباشد، لغوی یک عضو، کلِ سری را لغو می‌کند.',
        'برخی منابع علاوه بر سیاست کلی نوع، محدودیت روز یا ساعت اختصاصی خودِ همان منبع را هم دارند؛ اگر گزینه‌ای در تاریخ/ساعت انتخابی شما در فهرست دیده نمی‌شود یا رزرو آن رد می‌شود، همین محدودیت اختصاصی دلیلش است، نه لزوماً سیاست کلی نوع منبع.',
    ];
@endphp

<div x-data="{ tab: 'statuses' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $tab)
            <button
                type="button"
                @click="tab = '{{ $tab['id'] }}'"
                :class="tab === '{{ $tab['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex flex-col items-center justify-center gap-0.5 px-1.5 py-2 rounded-xl text-[11px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[18px]">{{ $tab['icon'] }}</span>
                <span class="leading-tight text-center">{{ $tab['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'statuses'" x-cloak class="space-y-2">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">هر رزرو یکی از این چهار وضعیت را دارد؛ رنگ و آیکون هرکدام همین‌جا مشخص است.</p>
        @foreach($statusRows as $row)
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
    </div>

    <div x-show="tab === 'history'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">رزروهای شما در بخش «تاریخچه من» در چهار زبانه دسته‌بندی می‌شوند.</p>
        @foreach($historyRows as $s)
            @php
                $historyChip = match ($s['color']) {
                    'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                    'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $historyChip }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
        @if(($activeTab ?? null) === 'meeting')
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">person</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">منابع نوع «ملاقات» نمایندهٔ همکاران شما هستند؛ با رزرو، یک دعوتنامهٔ جلسه در تقویم آن شخص ثبت و به او اطلاع‌رسانی می‌شود.</p>
            </div>
        @endif
    </div>
</div>