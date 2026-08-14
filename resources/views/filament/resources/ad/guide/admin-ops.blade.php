@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ اینفولیست آگهی را باز می‌کند: شناسه، عنوان، جنسیت، وضعیت، لینک، مدرک، مهارت، سابقه، توضیحات تکمیلی و تاریخ‌های ایجاد/ویرایش — همگی در سه زبانه و شمسی‌سازی‌شده.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم آگهی را باز می‌کند: سه بخش «اطلاعات اصلی»، «شرایط احراز» و «سایر توضیحات». جنسیت و لینک اجباری‌اند؛ عنوان، مدرک، مهارت و سابقه اختیاری.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» رکورد را کاملاً برمی‌دارد (حذف سخت). چون «غیرفعال» کردن همان پنهان‌سازی است، حذف فقط برای آگهی‌های واقعاً نامعتبر استفاده کنید.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده، خروجی اکسل می‌گیرید (AdExporter). ستون‌ها: شناسه، عنوان، جنسیت (برچسب enum)، وضعیت (برچسب enum)، لینک، مدرک، مهارت، سابقه، توضیحات تکمیلی (key: value)، تاریخ ایجاد (toJalaliSmart). اعمال روی کل فهرست فیلترشده نیز ممکن است.',
        ],
        [
            'icon' => 'verified',
            'label' => 'تاگلِ ستون وضعیت',
            'hint' => 'ستون «وضعیت» در جدول یک ToggleColumn است — می‌توانید بدون ورود به فرم، مستقیم از جدول آگهی را فعال/غیرفعال کنید. آیکون روشن (check-circle سبز) و خاموش (x-circle قرمز).',
        ],
        [
            'icon' => 'search',
            'label' => 'جستجوی سراسری پنل',
            'hint' => 'جستجوی سراسری پنل آگهی‌ها را با «عنوان، مهارت، مدرک» پیدا می‌کند و مستقیم به ویرایش می‌رود. در کارتِ نتیجه، جنسیت و وضعیت به‌صورت برچسب نمایش داده می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: تعریف آگهی، کنترل وضعیت و خروجی اکسل</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        سه دکمهٔ عملیات روی هر ردیف موجود است (پس از سلول‌ها): مشاهده، ویرایش، حذف. دکمهٔ «ساخت فرصت شغلی» در هدر صفحه قرار دارد. ستون «وضعیت» مستقیماً در جدول قابل تاگل است و نیازی به باز کردن فرم نیست.
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
                برای پنهان کردن موقتِ آگهی از پنل کاربر، ستون «وضعیت» را خاموش کنید — حذف نکنید. حذف رکورد را دائمی برمی‌دارد.
            </p>
        </div>
    </div>
</div>