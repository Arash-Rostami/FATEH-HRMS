@php
    $areas = [
        [
            'icon' => 'keep',
            'label' => 'نوار سنجاق‌شده',
            'hint' => 'اعلانات سنجاق‌شده در یک نوار ویژه (تنها ' . convertToPersian('1') . ' مورد، جدیدترین) بالای فهرست نمایش داده می‌شوند — با نوار رنگی بالای کارت و برچسب «مهم». اگر هیچ اعلانی سنجاق نشده باشد، به‌جای آن یک پیام خوشامد نمایش داده می‌شود.',
        ],
        [
            'icon' => 'feed',
            'label' => 'فهرست تازه‌ترین‌ها',
            'hint' => 'اعلانات غیرسنجاق از جدیدترین به قدیمی‌ترین در یک شبکهٔ کارتی نمایش داده می‌شوند — هر کارت تصویر، عنوان (پاک‌شده با superClean)، خلاصهٔ محتوا و تاریخ نسبی (toJalaliRelative) دارد. دکمهٔ «نمایش بیشتر» هر بار ' . convertToPersian('3') . ' کارت دیگر بارگذاری می‌کند.',
        ],
        [
            'icon' => 'new_releases',
            'label' => 'برچسب «جدید» و «دیده شد»',
            'hint' => 'اعلان تازه (کمتر از ' . convertToPersian('30') . ' روز) یک برچسب «جدید» می‌گیرد؛ پس از باز کردن، به «دیده شد» تغییر می‌کند. اعلان قدیمی‌تر از ' . convertToPersian('30') . ' روز هیچ برچسبی نمی‌گیرد.',
        ],
        [
            'icon' => 'open_in_full',
            'label' => 'صفحهٔ جزئیات و تصویر بزرگ',
            'hint' => 'با کلیک روی کارت، یک پنل از سمت کنار باز می‌شود: تصویر بزرگ، عنوان، تاریخ و نویسنده، و متن کامل. دکمهٔ بزرگ‌نمایی تصویر را تمام‌صفحه نشان می‌دهد و دکمهٔ «اشتراک‌گذاری» امکان کپی متن یا ارسال ایمیل را می‌دهد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در زبانهٔ «اعلانات» چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانهٔ «اعلانات» در پنل کاربر از دو بخش می‌سازد: نوار سنجاق‌شده در کنار و فهرست تازه‌ترین‌ها. وقتی کاربر شکایت می‌کند — «اعلان مهم را نمی‌بینم»، «برچسب جدید نرفته»، «نشان هنوز روشن است» — این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">view_carousel</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">رفتارهای کلیدی از دید کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($areas as $a)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $a['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $a['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $a['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                نوار سنجاق فقط یک اعلان (جدیدترین سنجاق) نشان می‌دهد؛ اگر بیش از یک اعلان سنجاق شده باشد، بقیه در فهرست عادی می‌مانند. برای برجسته‌کردن چند اعلان همزمان، سنجاق کافی نیست.
            </p>
        </div>
    </div>
</div>