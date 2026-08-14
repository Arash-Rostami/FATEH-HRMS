@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ اینفولیست واحد را باز می‌کند: کد، نام، تعداد پرسنل، توضیحات، واحدها، بخش‌ها و تاریخ‌های ایجاد/ویرایش — همگی شمسی و راست‌چین.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم واحد را باز می‌کند: کد (تنها هنگام ساخت)، نام، توضیحات، واحدها، بخش‌ها و گزینه‌های تیکت. توجه کنید کد کلید اصلی است و بعد از ساخت قابل تغییر نیست. زیرِ صفحهٔ ویرایش سه مدیریت ارتباط ظاهر می‌شود: «پرسنل»، «سمت‌ها» و «گزارش‌ها».',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» رکورد واحد را کاملاً برمی‌دارد. قبل از حذف مطمئن شوید پرسنلی به این کد وصل نیست — در غیر این صورت پروفایلِ آن‌ها از واحد جدا می‌ماند. بعد از حذف، کش گزینه‌های واحدها به‌صورت خودکار پاک می‌شود.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده، خروجی اکسل می‌گیرید (DepartmentExporter). ستون‌ها: شناسه، کد، نام، توضیحات، تعداد پرسنل، تاریخ ایجاد. تاریخ‌ها با toJalaliSmart شمسی‌سازی می‌شوند. اعمال روی کل فهرست فیلترشده نیز ممکن است.',
        ],
    ];
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام واحدها — بدون فیلتر اضافه.'],
        ['label' => 'دارای گزینه تیکت', 'hint' => 'فقط واحدهایی که حداقل یک گزینهٔ تیکت سفارشی دارند. تعداد به‌صورت نشان روی زبانه می‌نشیند.'],
        ['label' => 'بدون گزینه تیکت', 'hint' => 'فقط واحدهایی که گزینهٔ تیکت تعریف نکرده‌اند — فرم تیکتشان روی فهرست پیش‌فرض است.'],
    ];
    $filters = [
        ['label' => 'دارای پرسنل', 'hint' => 'فیلتر سه‌حالته: فقط واحدهای دارای پرسنل / فقط واحدهای بدون پرسنل.'],
        ['label' => 'بازه تاریخ ایجاد', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد واحد.'],
    ];
    $groups = [
        ['label' => 'بر اساس تعداد پرسنل', 'hint' => 'واحدها را به ازای تعداد پرسنل گروه‌بندی می‌کند — برای دیدن واحدهای خالی در برابر واحدهای پر.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: تعریف واحدها، تنظیم گزینه‌های تیکت و نظارت بر پرسنل</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در جدول یک واحد سازمانی است. سه دکمهٔ عملیات روی هر ردیف موجود است (پس از سلول‌ها): مشاهده، ویرایش، حذف. دکمهٔ «ساخت واحد» در هدر صفحه قرار دارد. جستجوی سراسری پنل نیز واحدها را با کد، نام یا توضیحات پیدا می‌کند و مستقیم به ویرایش می‌رود.
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
                روی صفحهٔ ویرایش هر واحد، مدیریت ارتباط «پرسنل» ظاهر می‌شود — همان‌جا می‌توانید کاربرانِ وصل‌شده به واحد را ببینید و ویرایش کنید، بدون اینکه به صفحهٔ «کاربران» بروید.
            </p>
        </div>
    </div>
</div>