@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ اینفولیست گالری را باز می‌کند: عنوان، توضیحات، استک تصاویر (حداکثر ۱۲ مورد با شمارش بقیه)، پخش‌کنندهٔ ویدیو، واحد سازمانی، شمارش موارد، تاریخ رویداد و تاریخ‌های ایجاد/ویرایش — همگی شمسی و راست‌چین.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم گالری را باز می‌کند: عنوان، تاریخ رویداد (شمسی)، واحد سازمانی/واحدهای چندگانه، توضیحات و بارگذاری محتوا. توجه: انتخاب یکی از دو فیلدِ واحد، دیگری را خودکار خالی می‌کند (زبانهٔ «دسترسی و اشتراک»).',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» رکورد گالری را کاملاً برمی‌دارد؛ فایل‌های روی دیسک public به‌صورت خودکار پاک نمی‌شوند — فقط رکورد پایگاه‌داده حذف می‌شود.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده، خروجی اکسل می‌گیرید (GalleryExporter). ستون‌ها: شناسه، عنوان، توضیحات، واحد سازمانی (از all_department_models)، تاریخ رویداد، تعداد موارد، تاریخ ایجاد. تاریخ‌ها با toJalaliSmart شمسی‌سازی می‌شوند. اعمال روی کل فهرست فیلترشده نیز ممکن است.',
        ],
    ];
    $search = [
        ['icon' => 'search', 'label' => 'جستجوی سراسری پنل', 'hint' => 'گالری‌ها در جستجوی سراسری با عنوان، توضیحات، و نام/توضیحات/کد واحد سازمانی پیدا می‌شوند و مستقیم به صفحهٔ ویرایش می‌روند. در نتیجه، واحد سازمانی و شمارش موارد هم نشان داده می‌شود.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: بارگذاری گالری، تعیین دسترسی و نظارت بر محتوا</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در جدول یک گالری است. سه دکمهٔ عملیات روی هر ردیف موجود است (پس از سلول‌ها): مشاهده، ویرایش، حذف. دکمهٔ «ساخت گالری» در هدر صفحه قرار دارد و خروجی اکسل از منوی bulk actions در دسترس است.
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">search</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">جستجوی سراسری پنل</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($search as $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                کوئری پایهٔ جدول رابطهٔ <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">department</code> را eager-load می‌کند تا ستون واحد سازمانی بدون پرس‌وجوی اضافیِ هر ردیف پر شود.
            </p>
        </div>
    </div>
</div>