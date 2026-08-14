@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ اینفولیست گزارش را باز می‌کند: پیش‌نمایش تصویر، عنوان، توضیحات (HTML)، واحد، نویسنده، نوع فایل، وضعیت و تاریخ‌های شمسی.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم گزارش را باز می‌کند با دو زبانه: «اطلاعات اصلی» (عنوان، واحد، نویسنده، وضعیت، توضیحات) و «فایل‌ها» (تصویر جلد + فایل گزارش). زبانه‌ها در query string ذخیره می‌شوند.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» رکورد و فایل‌های متصل را برمی‌دارد. توجه: کاربران در زبانهٔ گزارشات فقط رکوردهای active می‌بینند — اگر می‌خواهید گزارش را موقتاً مخفی کنید، به جای حذف، «وضعیت» را غیرفعال کنید.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده خروجی اکسل بگیرید (ReportExporter). ستون‌ها: شناسه، عنوان، توضیحات، واحد (displayLabel)، نویسنده، نوع فایل، وضعیت، تاریخ ایجاد. رابطه‌های user و department به‌صورت eager-load بارگذاری می‌شوند.',
        ],
    ];
    $form = [
        [
            'icon' => 'label',
            'label' => 'عنوان + نویسنده + واحد',
            'hint' => 'عنوان الزامی است. نویسنده از لیست کاربران (relationship) با preload و search انتخاب می‌شود. واحد اختیاری است و از گزینه‌های کش‌شدهٔ واحدها می‌آید — خالی بگذارید تا گزارش عمومی شود.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'تاگلِ انتشار (active)',
            'hint' => 'پیش‌فرض «فعال». غیرفعال کردن گزارش را بدون حذف از زبانهٔ «گزارشات» کاربر مخفی می‌کند. این تنها اهرمِ کنترلِ انتشار است.',
        ],
        [
            'icon' => 'image',
            'label' => 'تصویر جلد (cover_image)',
            'hint' => 'FileUpload روی دیسک public در مسیر reports/covers. فقط تصویر، حداکثر ۲ مگابایت، با ویرایشگر تصویر. قابل دانلود، باز شدن و پیش‌نمایش.',
        ],
        [
            'icon' => 'file_present',
            'label' => 'فایل گزارش (file_path)',
            'hint' => 'FileUpload روی دیسک public در مسیر reports/files. فقط PDF یا Word (pdf/doc/docx)، حداکثر ۵ مگابایت، الزامی. این فایل است که کاربر از زبانهٔ گزارشات دانلود می‌کند.',
        ],
        [
            'icon' => 'subject',
            'label' => 'توضیحات (RichEditor)',
            'hint' => 'ویرایشگر غنی با رنگ‌های سفارشی متن، عنوان‌ها، جدول، بلوک‌کد و floating toolbars. هنگام ذخیره توسط ContentSanitizerService پاک‌سازی می‌شود تا HTML خطرناک وارد سیستم نشود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما: ساخت گزارش، بارگذاری فایل و انتشار</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        سه دکمهٔ عملیات روی هر ردیف (پس از سلول‌ها) قرار دارد: مشاهده، ویرایش، حذف. دکمهٔ «ساخت گزارش» در هدر صفحه است. جستجوی سراسری پنل گزارش‌ها را با عنوان یا توضیحات پیدا می‌کند و مستقیم به ویرایش می‌رود.
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
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">edit</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فرم ویرایش — دو زبانه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($form as $f)
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
                هر دو فایل روی دیسک «public» ذخیره می‌شوند — یعنی با URL مستقیم در دسترس‌اند. کنترلِ دسترسی از طریق «وضعیت فعال» و نه سطحِ فایل انجام می‌شود: غیرفعال کردن گزارش، دانلود آن را در پنل کاربر با ۴۰۳ مسدود می‌کند.
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">tips_and_updates</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            برای انتشارِ فوری، فقط کافی است «وضعیت» را فعال بگذارید — گزارش بلافاصله در زبانهٔ «گزارشات» کاربر ظاهر می‌شود. برای بازنگریِ یک گزارشِ زنده، آن را غیرفعال کنید، ویرایش کنید، و دوباره فعال کنید.
        </p>
    </div>
</div>