@php
    $pills = [
        ['id' => 'card', 'icon' => 'flip_camera_android', 'label' => 'کارت و جزئیات'],
        ['id' => 'filter', 'icon' => 'filter_alt', 'label' => 'فیلتر و تمرکز'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $cardRows = [
        ['icon' => 'campaign', 'color' => 'primary', 'label' => 'رویِ کارت', 'text' => 'عنوان موقعیت، مدرک تحصیلی و توضیحات تکمیلی (extra) روی رویِ کارت دیده می‌شوند. آیکونِ بالا بر اساس جنسیت است: آقایان (manage_accounts)، خانم‌ها (badge)، همه (group).'],
        ['icon' => 'flip_camera_android', 'color' => 'tertiary', 'label' => 'برگشت کارت', 'text' => 'دکمهٔ «مشاهده جزئیات» کارت را برمی‌گرداند و سابقه کاری و مهارت‌ها ظاهر می‌شود. این برگشت با CSS و یک چک‌باکسِ مخفی انجام می‌شود — نه جاوااسکریپت. با دکمهٔ بستن (close) برمی‌گردید.'],
        ['icon' => 'content_copy', 'color' => 'secondary', 'label' => 'کپی لینک', 'text' => 'روی پشتِ کارت، لینکِ ثبت‌نام در یک فیلد فقط‌خواندنی نشان داده می‌شود و دکمهٔ کپی آن را در کلیپ‌بورد می‌گذارد. لینک مستقیم کلیک‌شدنی نیست — کپی کنید و در مرورگر باز کنید.'],
        ['icon' => 'star', 'color' => 'tertiary', 'label' => 'ستارهٔ تازگی', 'text' => 'آگهی‌های ثبت‌شده در ۴۸ ساعت گذشته یک ستارهٔ کوچک روی گوشهٔ کارت می‌گیرند (با نگه‌داشتنِ نشانگر، معنی را می‌بینید). آگهی‌های قدیمی‌تر یا بدونِ تاریخِ ثبت ستاره نمی‌گیرند.'],
    ];

    $filterRows = [
        ['icon' => 'verified', 'color' => 'primary', 'label' => 'فعال', 'text' => 'فقط آگهی‌های فعال (active=true). شمارشِ زندهٔ روی دکمه از یک کوئریِ stats می‌آید. این حالت پیش‌فرض است.'],
        ['icon' => 'archive', 'color' => 'secondary', 'label' => 'بایگانی شده', 'text' => 'فقط آگهی‌های غیرفعال (active=false). این آگهی‌ها حذف نشده‌اند — فقط توسط ادمین پنهان شده‌اند. با فعال‌کردنِ دوباره توسط ادمین، به فهرست فعال برمی‌گردند.'],
        ['icon' => 'search', 'color' => 'tertiary', 'label' => 'جستجو', 'text' => 'جستجوی آزاد در «عنوان، مهارت و مدرک». تایپ کردن، حالتِ تمرکز را لغو می‌کند و فهرست کامل برمی‌گردد.'],
        ['icon' => 'next_plan', 'color' => 'primary', 'label' => 'حالت تمرکز', 'text' => 'وقتی از پالتِ فرمان یک آگهی را باز می‌کنید، فهرست به همان یک آگهی پین می‌شود. تغییر فیلتر یا جستجو، تمرکز را لغو می‌کند.'],
    ];

    $notes = [
        '«بایگانی شده» همان آگهی‌های غیرفعالِ ادمین است — حذف نشده‌اند و با فعال‌شدن دوباره برمی‌گردند.',
        'تمام متن‌های آگهی (عنوان، مدرک، مهارت، سابقه، توضیحات تکمیلی) قبل از نمایش با strip_tags پاکسازی می‌شوند؛ HTML در کارت اجرا نمی‌شود.',
        'اگر هیچ آگهی‌ای در فیلتر فعلی نباشد، کارتِ خالی با آیکون search_off و پیام «هیچ فرصت شغلی در این بخش یافت نشد» نمایش داده می‌شود.',
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
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">هر آگهی یک کارتِ برگشت‌پذیر است — روی و پشتِ کارت اطلاعات متفاوتی دیده می‌شود.</p>
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
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">دو دکمهٔ فیلتر بالا فهرست را محدود می‌کنند؛ جستجو و پالتِ فرمان هم رفتار خاصی دارند.</p>
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