@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ اینفولیست اعلان را باز می‌کند: عنوان، محتوا، تصویر، نویسنده، پرچم سنجاق و تاریخ‌های انتشار/بروزرسانی — همگی شمسی و راست‌چین. سنجاق در اینفولیست با آیکون نشان داده می‌شود (روشن = نارنجی، خاموش = خاکستری).',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم اعلان را باز می‌کند: عنوان و محتوا (دو RichEditor) در ستون چپ، و نویسنده، پرچم سنجاق و تصویر در ستون راست. نویسنده قفل است و قابل تغییر نیست. دکمهٔ «ساخت اعلان» در هدر صفحه قرار دارد.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» رکورد اعلان را کاملاً برمی‌دارد. حذف یک اعلان سنجاق‌شده باعث می‌شود نوار سنجاق‌شدهٔ پنل کاربری در رندر بعدی خالی شود. پس از حذف، کش منو به‌صورت خودکار پاک می‌شود (HasMenuState).',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده خروجی اکسل می‌گیرید (PostExporter). ستون‌ها: شناسه، عنوان، محتوا (بدون تگ HTML)، نویسنده، سنجاق (بله/خیر)، تاریخ انتشار (شمسی). اعمال روی کل فهرست فیلترشده نیز ممکن است.',
        ],
    ];
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام اعلانات — بدون فیلتر اضافه. زبانهٔ پیش‌فرض.'],
        ['label' => 'سنجاق شده', 'hint' => 'فقط اعلانات سنجاق‌شده؛ تعداد به‌صورت نشان زرد روی زبانه می‌نشیند.'],
        ['label' => 'عادی', 'hint' => 'اعلانات غیرسنجاق؛ تعداد به‌صورت نشان خاکستری روی زبانه می‌نشیند.'],
    ];
    $filters = [
        ['label' => 'سنجاق‌شده', 'hint' => 'فیلتر دوحالته: فقط اعلانات سنجاق‌شده را نشان می‌دهد.'],
        ['label' => 'دارای تصویر', 'hint' => 'فقط اعلانات دارای تصویر معتبر (image پر و غیر از NULL) را نشان می‌دهد.'],
        ['label' => 'بازه تاریخ انتشار', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد اعلان (createdAtFilter سراسری).'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: ساخت اعلان، سنجاق و نظارت بر انتشار</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در جدول یک اعلان است. سه دکمهٔ عملیات روی هر ردیف موجود است (پس از سلول‌ها): مشاهده، ویرایش، حذف. دکمهٔ «ساخت اعلان» در هدر صفحه قرار دارد. ستون «سنجاق» در جدول یک ToggleColumn است؛ می‌توانید بدون ورود به ویرایش، با یک کلیک اعلان را سنجاق یا لغو سنجاق کنید. جستجوی سراسری پنل اعلانات را با عنوان، محتوا یا نام نویسنده پیدا می‌کند و مستقیم به ویرایش می‌رود.
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                زبانه‌ها فقط با ترجیح «show_list_tabs» کاربر نمایش می‌آیند؛ اگر خاموش باشد، فهرست مسطح است.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها</p>
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
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ترتیب پیش‌فرض جدول: سنجاق‌ها بالاتر، سپس جدیدترین — هر دو از طریق defaultSort روی ستون‌های pinned و created_at اعمال می‌شود.
            </p>
        </div>
    </div>
</div>