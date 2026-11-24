@php
    $panels = [
        [
            'icon' => 'account_tree',
            'label' => 'دو زبانه: اسناد / سوابق',
            'hint' => 'در پنل کاربر، «اسناد» و «سوابق» جدا هستند. کاربر اسناد فعالِ منتشرشده برای خودش، واحدش یا همهٔ واحدها را می‌بیند. با تغییر زبانه، جست‌وجو و فیلتر دوباره تنظیم می‌شوند.',
        ],
        [
            'icon' => 'edit_document',
            'label' => 'دو تأیید جداگانه',
            'hint' => 'کاربر ابتدا «تأیید دریافت» را می‌زند. سپس از «مشاهده و تأیید»، فایل را باز می‌کند و پس از خواندن، «تأیید و ثبت مطالعه» را می‌زند. تا هر دو مرحله ثبت شوند، سند در «اقدام مورد نیاز» می‌ماند.',
        ],
        [
            'icon' => 'error',
            'label' => 'بنر اقدام مورد نیاز',
            'hint' => 'وقتی سندِ تأییدنشده در کارتابل باشد، بنر قرمز بالای صفحه با دو شمارش نمایش داده می‌شود: «نیازمند تأیید دریافت» و «نیازمند تأیید مطالعه». کلیک روی هر شمارش، فهرست را به همان دسته فیلتر می‌کند. این بنر به‌جای اعلان زنگوله‌ای است — مدیریت اسناد از اعلان‌های پنل استفاده نمی‌کند.',
        ],
        [
            'icon' => 'sell',
            'label' => 'فیلتر با کلیک روی تگ/دسته',
            'hint' => 'با انتخاب دسته یا برچسب یک سند، فهرست بر اساس همان مورد فیلتر می‌شود. جست‌وجو عنوان، کد، نسخه، توضیحات و اطلاعات تکمیلی و برچسب‌ها را بررسی می‌کند.',
        ],
        [
            'icon' => 'history',
            'label' => 'مشاهده‌های اخیر و اولویت‌بندی',
            'hint' => 'اسنادی که به تأیید نیاز دارند بالاتر در فهرست دیده می‌شوند. بخش «اخیراً مشاهده‌شده» آخرین سندهای بازشده را در همین مرورگر نگه می‌دارد. کاربر می‌تواند این فهرست را پاک کند.',
        ],
        [
            'icon' => 'view_column',
            'label' => 'تنظیمات جدول: ستون‌ها، تراکم، بزرگ‌نمایی',
            'hint' => 'با دکمهٔ تنظیمات در سرستون: نمایش/پنهان‌کردن ستون‌ها، تراکم ردیف‌ها (فشرده/عادی)، بازنشانی ترتیب و بزرگ‌نمایی جدول. این ترجیحات در مرورگر کاربر ذخیره می‌شود و برای سایر جداول هم به‌کار می‌رود.',
        ],
    ];

    $security = [
        [
            'icon' => 'verified_user',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'دسترسی فایل از طریق مسیر امن',
            'hint' => 'دسترسی کاربر به فایل اصلی و پیوست‌ها هنگام باز کردن بررسی می‌شود. باز شدن فایل برای مدیر سامانه، دسترسی کاربر را تأیید نمی‌کند. اگر کاربر سند را می‌بیند و فایل باز نمی‌شود، وجود فایل اصلی یا پیوست را بررسی کنید.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ مدیریت اسناد چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربران در «مدیریت اسناد» فایل‌هایی را که برایشان منتشر کرده‌اید دریافت و مطالعه می‌کنند. از این راهنما برای بررسی دسترسی و وضعیت تأیید استفاده کنید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">قابلیت‌های پنل کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($panels as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $p['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">shield_person</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">دسترسی فایل از طریق مسیر امن</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($security as $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $s['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $s['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر به سندی دسترسی ندارد، وضعیت انتشار و مخاطبان آن را بررسی کنید. اگر سند در فهرست او دیده می‌شود اما فایل باز نمی‌شود، وجود فایل را هم بررسی کنید.
            </p>
        </div>
    </div>
</div>