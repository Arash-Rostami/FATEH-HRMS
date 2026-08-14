@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ اینفولیست ردیف را باز می‌کند: کاربر، وضعیت مدیر ارشد، لیست ماژول‌های اعطاشده (abilities) یا استثناها (excluded_modules) و تاریخ‌های ایجاد/ویرایش — همگی شمسی و راست‌چین.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم ردیف را باز می‌کند: انتخاب کاربر (فقط هنگام ساخت قابل تغییر نیست — در واقع قابل ویرایش است اما یکتاست)، Toggle مدیر ارشد، و بسته به وضعیت Toggle، either excluded_modules یا abilities. همین فرم زیرِ صفحهٔ ویرایش کاربر هم به‌کار می‌رود.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» رکورد دسترسی را کاملاً برمی‌دارد. بعد از حذف، کشِ دسترسیِ آن کاربر به‌صورت خودکار پاک می‌شود. اگر کاربر نقش admin داشته باشد و ردیفش حذف شود، دیگر به پنل دسترسی نخواهد داشت.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده، خروجی اکسل می‌گیرید (PermissionExporter). ستون‌ها: شناسه، کاربر، وضعیت مدیر ارشد، فهرست ماژول‌های مجاز (allowedModules)، تاریخ ایجاد. اعمال روی کل فهرست فیلترشده نیز ممکن است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: اختصاص دسترسی به کاربرانِ ادمین</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در جدول یک کاربر admin و سطح دسترسی اوست. سه دکمهٔ عملیات روی هر ردیف موجود است (پس از سلول‌ها): مشاهده، ویرایش، حذف. دکمهٔ «ساخت دسترسی» در هدر صفحه قرار دارد. جستجوی سراسری پنل نیز ردیف‌ها را با نام یا ایمیل کاربر پیدا می‌کند و مستقیم به ویرایش می‌رود.
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                فیلد «کاربر» فقط گزینه‌های با نقش admin را نشان می‌دهد (نه developer و نه user عادی) و یکتاست — نمی‌توان به یک کاربر دو ردیف دسترسی داد.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکتهٔ مهم دربارهٔ کش</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">bolt</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">پاک شدن خودکار کش</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">دسترسیِ هر کاربر زیر کلید user_permission:{id} کش می‌شود. بعد از هر ذخیره یا حذف، این کش به‌صورت خودکار پاک می‌شود تا تغییر بلافاصله اثر کند — نیازی به پاک‌کردن دستی نیست.</p>
                </div>
            </div>
        </div>
    </div>
</div>