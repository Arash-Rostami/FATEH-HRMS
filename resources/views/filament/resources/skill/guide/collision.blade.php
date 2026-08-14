@php
    $flow = [
        ['icon' => 'label', 'label' => 'نام را وارد کنید', 'hint' => 'در فرم «ساخت مهارت» نام فارسی (و در صورت تمایل انگلیسی) را وارد می‌کنید. نام پیش از ذخیره توسط قانون UniqueActiveSkillName بررسی می‌شود.'],
        ['icon' => 'search', 'label' => 'تطبیق با lockForUpdate', 'hint' => 'در یک تراکنش، Skill::matchingName نام را روی هر دو ستون name و name_en با قفلِ ردیف جستجو می‌کند تا در حالِ هم‌زمانی رکوردِ دوگانه ساخته نشود.'],
        ['icon' => 'auto_awesome', 'label' => 'اگر ghost بود → ارتقا', 'hint' => 'اگر مهارتی با آن نام پیدا شود و ghost (is_active=false) باشد، همان رکورد ارتقا پیدا می‌کند: is_active=true و is_ghost=false و فیلدهای name_en/category/description/icon شما روی آن نوشته می‌شود. رکوردِ دوگانه ساخته نمی‌شود.'],
        ['icon' => 'report', 'label' => 'اگر هیچ نبود → ساخت', 'hint' => 'اگر تطبیقی یافت نشد، یک رکوردِ جدید و فعال (is_ghost=false) ساخته می‌شود. اگر در فاصلهٔ بررسی تا ذخیره رکوردِ هم‌زمانی ساخته شد، استثنای Duplicate گرفته و مسیرِ ارتقا دوباره اجرا می‌شود.'],
        ['icon' => 'warning', 'label' => 'اگر فعال بود → خطا', 'hint' => 'اگر مهارتی با آن نام هم‌اکنون فعال باشد، ساخت رد می‌شود و پیام «مهارتی با این نام هم‌اکنون فعال است؛ آن را ویرایش کنید» نشان داده می‌شود — نباید دو مهارتِ هم‌نامِ فعال داشته باشید.'],
    ];

    $guards = [
        ['icon' => 'visibility_off', 'label' => 'ویرایشِ ghost مخفی است', 'hint' => 'دکمهٔ «ویرایش» برای رکوردهای ghost در جدول پنهان می‌شود — ghost فقط باید فعال شود، نه ویرایش. راهِ فعال‌سازی، ساختِ مهارت با همان نام است (see بالا).'],
        ['icon' => 'delete', 'label' => 'حذف وقتی دارندگان دارد مسدود است', 'hint' => 'دکمهٔ «حذف» برای مهارت‌هایی که skill_users_count > ۰ باشد پنهان می‌شود. حذفِ گروهی نیز خودکار رکوردهای دارایِ دارندگان را فیلتر می‌کند و فقط بقیه را حذف می‌کند.'],
        ['icon' => 'warning', 'label' => 'محافظِ مدل (booted)', 'hint' => 'به‌عنوانِ خطِ دفاعیِ دوم، مدل Skill در رویداد deleting بررسی می‌کند اگر skillUsers وجود دارد و در صورتِ وجود RuntimeException پرتاب می‌کند — حتی اگر مسیری از کنارِ جدول بگذرد، حذفِ مهارتِ درحالِاستفاده ممکن نیست.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">verified_user</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">ساختِ مهارتِ هم‌نام، ghost را فعال می‌کند — رکورد دوگانه نمی‌سازد</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        وقتی مهارتی را می‌سازید که نامش با یک ghost یکی باشد، سیستم به‌جای ساختِ رکوردِ جدید، همان ghost را به یک مهارتٔ فعال ارتقا می‌دهد — تا شمارشِ جستجوها و تاریخچهٔ آن نام حفظ شود. یکتاییِ نام هم روی فارسی و هم روی انگلیسی بررسی می‌شود؛ نمی‌شود نام فارسیِ یک مهارت با نام انگلیسیِ مهارتی دیگر یکی باشد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">rule</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">جریانِ ساخت و قانونِ یکتایی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($flow as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                قانون UniqueActiveSkillName در زمانِ «ساخت» فقط با مهارت‌های فعال تصادف می‌گیرد (ghostها رد نمی‌شوند)؛ اما در زمانِ «ویرایش» هر تصادفی جز خودِ رکورد رد می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">shield</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">محافظت‌های خودکار</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($guards as $g)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $g['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>