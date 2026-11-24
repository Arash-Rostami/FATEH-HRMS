@php
    $pills = [
        ['id' => 'card', 'icon' => 'flip_camera_android', 'label' => 'کارت و جزئیات'],
        ['id' => 'filter', 'icon' => 'filter_alt', 'label' => 'فیلتر و تمرکز'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $cardRows = [
        ['icon' => 'campaign', 'color' => 'primary', 'label' => 'اطلاعات آگهی', 'text' => 'عنوان موقعیت شغلی، مدرک تحصیلی و توضیحات تکمیلی روی کارت نمایش داده می‌شود. نماد بالای کارت، جنسیت مورد نظر برای استخدام را مشخص می‌کند.'],
        ['icon' => 'flip_camera_android', 'color' => 'tertiary', 'label' => 'مشاهدهٔ جزئیات', 'text' => 'با انتخاب «مشاهده جزئیات»، سابقهٔ کاری و مهارت‌های مورد نیاز در پشت کارت نمایش داده می‌شود. برای بازگشت، دکمهٔ بستن را بزنید.'],
        ['icon' => 'content_copy', 'color' => 'secondary', 'label' => 'پیوند ثبت‌نام', 'text' => 'در پشت کارت، دکمهٔ کپی کنار پیوند ثبت‌نام را بزنید. پیوند را در نوار نشانی مرورگر بچسبانید یا برای فرد دیگری بفرستید.'],
        ['icon' => 'star', 'color' => 'tertiary', 'label' => 'آگهی تازه', 'text' => 'آگهی‌های ثبت‌شده در ۴۸ ساعت گذشته با ستاره مشخص می‌شوند. آگهی‌های قدیمی‌تر یا بدون تاریخ ثبت، این نشان را ندارند.'],
    ];

    $filterRows = [
        ['icon' => 'verified', 'color' => 'primary', 'label' => 'فعال', 'text' => 'در حالت پیش‌فرض، فقط آگهی‌های فعال نمایش داده می‌شوند. عدد روی دکمه، تعداد این آگهی‌هاست.'],
        ['icon' => 'archive', 'color' => 'secondary', 'label' => 'بایگانی شده', 'text' => 'آگهی‌هایی که مدیر سامانه غیرفعال کرده است. این موارد حذف نشده‌اند و با فعال‌سازی مجدد به فهرست فعال برمی‌گردند.'],
        ['icon' => 'search', 'color' => 'tertiary', 'label' => 'جستجو', 'text' => 'عنوان، مهارت‌ها و مدرک تحصیلی را جستجو کنید. با شروع جستجو، نمایش تک‌آگهی لغو می‌شود و نتایج مطابق عبارت شما نمایش داده می‌شوند.'],
        ['icon' => 'next_plan', 'color' => 'primary', 'label' => 'نمایش تک‌آگهی', 'text' => 'با انتخاب آگهی از جست‌وجوی سراسری، همان آگهی باز می‌شود. برای دیدن آگهی‌های دیگر، جست‌وجو یا فیلتر را تغییر دهید.'],
    ];

    $notes = [
        'آگهی‌های «بایگانی شده» حذف نشده‌اند؛ مدیر سامانه می‌تواند آن‌ها را دوباره فعال کند.',
        'اطلاعات موقعیت شغلی در روی کارت و شرایط تفصیلی و پیوند ثبت‌نام در پشت کارت قرار دارد.',
        'اگر جستجو یا فیلتر نتیجه‌ای نداشته باشد، پیام «هیچ فرصت شغلی در این بخش یافت نشد» نمایش داده می‌شود.',
    ];
@endphp

<div x-data="{ tab: 'card' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($pills as $pill)
            <button
                type="button"
                @click="tab = '{{ $pill['id'] }}'"
                :class="tab === '{{ $pill['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[17px]">{{ $pill['icon'] }}</span>
                {{ $pill['label'] }}
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'card'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">عنوان و خلاصهٔ آگهی روی کارت است. برای دیدن شرایط و پیوند ثبت‌نام، «مشاهده جزئیات» را بزنید.</p>
        @foreach($cardRows as $s)
            @php
                $chipClasses = match ($s['color']) {
                    'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'filter'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">با دکمه‌های بالای صفحه آگهی‌های فعال یا بایگانی‌شده را ببینید.</p>
        @foreach($filterRows as $s)
            @php
                $chipClasses = match ($s['color']) {
                    'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
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
    </div>
</div>
