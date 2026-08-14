@php
    $rules = [
        [
            'icon' => 'public',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'مخاطب عمومی (user_id خالی)',
            'hint' => 'اگر فیلد «کاربر اختصاصی» را خالی بگذارید، رکورد برای همهٔ کاربران قابل نمایش است. در جدول، ستون «کاربر» برای این ردیف‌ها «همه کاربران» نشان می‌دهد.',
        ],
        [
            'icon' => 'person',
            'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'label' => 'مخاطب اختصاصی (user_id مشخص)',
            'hint' => 'اگر یک کاربر را انتخاب کنید، رکورد فقط برای همان کاربر است. در پنل کاربری، نسخهٔ اختصاصی به‌محضِ وجود، بر نسخهٔ عمومی اولویت می‌گیرد — حتی اگر هر دو فعال باشند.',
        ],
    ];

    $invariant = [
        [
            'icon' => 'rule',
            'label' => 'فقط یک رکورد فعال برای هر مخاطب',
            'hint' => 'هنگام ذخیرهٔ فرم با is_active = true، همهٔ رکوردهای فعالِ دیگرِ همان مخاطب (عمومی یا همان کاربر) خودکار غیرفعال می‌شوند. این اعمال در afterCreate و afterSave اجرا می‌شود — نه هنگام تغییر سریعِ ستونِ toggle در جدول.',
        ],
        [
            'icon' => 'priority',
            'label' => 'اولویت نسخهٔ اختصاصی در پنل کاربر',
            'hint' => 'کوئریِ پنل کاربری رکوردهای فعالِ کاربر را با «CASE WHEN user_id = ? THEN ۰ ELSE ۱» مرتب می‌کند — نسخهٔ اختصاصیِ کاربر بالاتر از نسخهٔ عمومی می‌نشیند و only first() برمی‌گردد. پس فقط یک آنبوردینگ به کاربر نمایش داده می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">groups</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">مدل مخاطب: عمومی در برابر اختصاصی، با قانونِ یک‌نسخهٔ فعال</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر رکورد آنبوردینگ یا به «همه کاربران» تعلق دارد (user_id خالی) یا به یک کاربر خاص. کاربر در پروفایل خود فقط یک نسخه می‌بیند: نسخهٔ اختصاصیِ خودش اگر باشد، وگرنه نسخهٔ عمومی. فعال‌سازیِ رکوردی که مخاطبِ آن از قبل رکوردِ فعالی دارد، رکوردِ قبلی را غیرفعال می‌کند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">groups</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">دو نوع مخاطب</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rules as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $r['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">rule</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">قوانین قطعی سیستم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($invariant as $inv)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $inv['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $inv['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $inv['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر از جدول ستون «فعال» را سریع تغییر دهید، اعمالِ یک‌رکوردِ فعال به‌ازایِ مخاطب اجرا نمی‌شود — این تغییر فقط از فرمِ ویرایش اعمال می‌شود. برای تضمینِ یکتایی، هم از فرم فعال کنید.
            </p>
        </div>
    </div>
</div>