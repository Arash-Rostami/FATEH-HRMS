@php
    $rows = [
        [
            'icon' => 'note_add',
            'label' => 'نوع سربرگ را انتخاب کنید',
            'text' => 'برای نامهٔ خروجی «سربرگ» و برای ثبت و شماره‌گذاری نامهٔ دریافتی «دبیرخانه» را انتخاب کنید. دبیرخانه تنظیمات ظاهر صفحه ندارد. در فهرست می‌توانید سربرگ‌ها را بر اساس نوع، وضعیت یا تاریخ ایجاد پیدا کنید.',
        ],
        [
            'icon' => 'tag',
            'label' => 'الگوی شماره را تعیین کنید',
            'text' => 'برای نمونه {YYYY}/{SEQ:4} شماره‌ای مانند ۱۴۰۵/۰۰۰۱ می‌سازد. الگو باید {SEQ} یا شکلی مانند {SEQ:4} داشته باشد. {PREFIX} پیشوند، {DEPT} کد واحد، {TYPE} کد نوع نامه، {YYYY} و {YY} سال و {MM} ماه شمسی است. نمونهٔ شماره زیر کادر الگو نمایش داده می‌شود.',
        ],
        [
            'icon' => 'pin',
            'label' => 'ترتیب شماره‌ها را بررسی کنید',
            'text' => 'اگر سال در الگو باشد، هر سال شمارندهٔ جدا دارد. پیشوند، ماه، واحد یا نوع نامه هم فقط وقتی در الگو باشند شمارنده را جدا می‌کنند. «شروع از» حداقل عدد بعدی را تعیین می‌کند. در سربرگ ذخیره‌شده، «ادامه از آخرین» عدد بعد از آخرین شمارهٔ ثبت‌شده را در این کادر می‌گذارد. سامانه از شماره‌های قبلاً استفاده‌شده می‌گذرد.',
        ],
        [
            'icon' => 'account_tree',
            'label' => 'شماره‌گذاری مشترک برای دو نسخه',
            'text' => 'اگر نسخهٔ A5 باید شماره‌گذاری نسخهٔ A4 را ادامه دهد، در کادر «سربرگ والد» نسخهٔ A4 را انتخاب کنید. سربرگ اصلی باید هم‌نوع باشد. الگو و شمارنده بین این دو مشترک است و زیرمجموعه نمی‌تواند زیرمجموعهٔ دیگری داشته باشد.',
        ],
        [
            'icon' => 'tune',
            'label' => 'ظاهر نامه را آماده کنید',
            'text' => 'اندازهٔ کاغذ را پیش از تنظیم حاشیه‌ها انتخاب کنید، چون تغییر اندازه حاشیه‌ها را به مقدار پیش‌فرض برمی‌گرداند. سپس قلم، جای شماره و تاریخ، امضا و مهر را تنظیم کنید. سقف هر تصویر ' . convertToPersian(config('ai.correspondence.upload.image_kb')) . ' کیلوبایت و پهنای مجاز آن ' . convertToPersian(config('ai.correspondence.upload.image_max_width')) . ' پیکسل است. عنوان‌های شماره، تاریخ و پیوست را روی تصویر نگذارید. سامانه آن‌ها را اضافه می‌کند.',
        ],
        [
            'icon' => 'image',
            'label' => 'استفاده از تصویر سربرگ دیگر',
            'text' => 'می‌توانید سرصفحه یا پاصفحه را از یک سربرگ فعال دیگر بگیرید. فهرست انتخاب فقط سربرگ‌های فعالی را نشان می‌دهد که از سربرگ دیگری تصویر نمی‌گیرند. پیش از انتخاب، مطمئن شوید سربرگ منبع تصویر موردنظر را دارد.',
        ],
        [
            'icon' => 'inventory_2',
            'label' => 'غیرفعال‌کردن یا حذف',
            'text' => 'برای برداشتن سربرگ از فهرست انتخاب کاربران، آن را غیرفعال کنید. سربرگی که نامه یا زیرمجموعه دارد حذف نمی‌شود. اگر سربرگ دیگری از تصویر آن استفاده می‌کند، پیش از غیرفعال‌کردن یا حذف، منبع تصویر را در سربرگ دیگر تغییر دهید.',
        ],
    ];
@endphp

<div dir="rtl">
    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">co_present</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکته‌های مهم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $row)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $row['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
