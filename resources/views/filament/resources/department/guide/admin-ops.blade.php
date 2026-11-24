@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ جزئیات واحد را باز می‌کند: کد، نام، تعداد پرسنل، توضیحات، واحدها، زیربخش‌ها و تاریخ‌های ایجاد/ویرایش — همگی شمسی و راست‌چین. «تعداد پرسنل» فقط همکاران فعلی را می‌شمارد (فعال، غیرمهمان، بدون پایان همکاری)، اما بخش «پرسنل» پایین صفحهٔ ویرایش همه را نشان می‌دهد.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم واحد را باز می‌کند: کد (تنها هنگام ساخت)، نام، توضیحات، واحدها، زیربخش‌ها و گزینه‌های تیکت (فقط وقتی ماژول تیکت برای شرکت روشن است). توجه کنید کد شناسهٔ اصلی است و بعد از ساخت قابل تغییر نیست. در پایین صفحهٔ ویرایش سه بخش دیده می‌شود: «پرسنل»، «سمت‌ها» و «گزارش‌ها».',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» واحد را کاملاً برمی‌دارد. تا وقتی پرسنل، زیرواحد یا محتوایی (گزارش، پرسش‌های متداول، گالری، اختیارات، وظایف، ارزیابی، پست، خبر، پروژه، پیشنهاد، سند، تیکت، قواعد اطلاع‌رسانی) به این کد وصل باشد، حذف انجام نمی‌شود و پیام خطا می‌آید؛ ابتدا آن‌ها را به واحد دیگری منتقل کنید. در «حذف گروهی» فقط شمار حذف‌شده‌ها اعلام می‌شود و متن علت نمایش داده نمی‌شود؛ برای دیدن علت، واحد را تکی حذف کنید. بعد از حذف، گزینه‌های واحدها خودکار به‌روز می‌شوند.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'واحدهای مورد نظر را انتخاب کنید و از منوی عملیات گروهی خروجی اکسل بگیرید. می‌توانید از کل فهرست فیلترشده نیز خروجی بگیرید. فایل شامل شناسه، کد، نام، توضیحات، تعداد پرسنل و تاریخ شمسی ایجاد است.',
        ],
    ];
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام واحدها — بدون فیلتر اضافه.'],
        ['label' => 'دارای گزینهٔ تیکت', 'hint' => 'فقط واحدهایی که حداقل یک گزینهٔ تیکت سفارشی دارند. تعداد به‌صورت نشان روی زبانه می‌نشیند.'],
        ['label' => 'بدون گزینهٔ تیکت', 'hint' => 'فقط واحدهایی که گزینهٔ تیکت تعریف نکرده‌اند — فرم تیکتشان روی فهرست پیش‌فرض است.'],
    ];
    $filters = [
        ['label' => 'دارای پرسنل', 'hint' => 'فقط واحدهای دارای پرسنل، فقط واحدهای بدون پرسنل، یا هر دو.'],
        ['label' => 'بازهٔ تاریخ ایجاد', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد واحد.'],
    ];
    $groups = [
        ['label' => 'بر اساس تعداد پرسنل', 'hint' => 'واحدها را بر اساس تعداد پرسنل گروه‌بندی می‌کند — برای دیدن واحدهای خالی در برابر واحدهای پر.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: تعریف واحدها، تنظیم گزینه‌های تیکت و نظارت بر پرسنل</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر مورد در جدول یک واحد سازمانی است. سه دکمهٔ عملیات روی هر ردیف موجود است (پس از سلول‌ها): مشاهده، ویرایش، حذف. دکمهٔ «ساخت واحد» در بالای صفحه قرار دارد. جستجوی سراسری پنل نیز واحدها را با کد، نام یا توضیحات پیدا می‌کند و مستقیم به ویرایش می‌رود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات روی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ops as $op)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $op['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tab</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های فهرست</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">tab</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها و گروه‌بندی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">filter_list</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
            @foreach($groups as $g)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">folder</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                روی صفحهٔ ویرایش هر واحد، بخش «پرسنل» ظاهر می‌شود — همان‌جا می‌توانید کاربرانِ وصل‌شده به واحد را ببینید و ویرایش کنید، بدون اینکه به صفحهٔ «کاربران» بروید.
            </p>
        </div>
    </div>
</div>