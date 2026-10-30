@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ جزئیات گزارش را باز می‌کند: تصویر، عنوان، توضیحات، واحد، نویسنده، نوع فایل، وضعیت و تاریخ‌های شمسی.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم گزارش را باز می‌کند با دو زبانه: «اطلاعات اصلی» (عنوان، واحد، نویسنده، وضعیت، توضیحات) و «فایل‌ها» (تصویر جلد + فایل گزارش). زبانهٔ باز هنگام بارگذاری دوبارهٔ صفحه حفظ می‌شود.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» گزارش و فایل‌های آن را پاک می‌کند. توجه: کاربران در زبانهٔ گزارشات فقط گزارش‌های فعال را می‌بینند — اگر می‌خواهید گزارش را موقتاً مخفی کنید، به جای حذف، «وضعیت» را غیرفعال کنید.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی عملیات گروهی، روی موردهای انتخاب‌شده خروجی اکسل بگیرید. ستون‌ها: شناسه، عنوان، توضیحات، واحد، نویسنده، نوع فایل، وضعیت، تاریخ ایجاد.',
        ],
        [
            'icon' => 'visibility_off',
            'label' => 'فعال‌سازی/غیرفعال‌سازی گروهی',
            'hint' => 'از منوی عملیات گروهی، «فعال‌سازی انتخاب‌شده‌ها» یا «غیرفعال‌سازی انتخاب‌شده‌ها» فقط برای دارندگان مجوز ویرایش دیده می‌شود. هر گزارش جداگانه به‌روز می‌شود تا اعلان‌ها و تغییرات وابسته درست انجام شوند؛ ستون «وضعیت» جدول هم یک کلید روشن/خاموش مستقیم است. غیرفعال‌بودن فقط بر پنل کاربر اثر دارد؛ ادمین همیشه همهٔ گزارش‌ها را می‌بیند.',
        ],
    ];
    $form = [
        [
            'icon' => 'label',
            'label' => 'عنوان + نویسنده + واحد',
            'hint' => 'عنوان الزامی است. نویسنده را از فهرست کاربران جستجو و انتخاب کنید. انتخاب واحد اختیاری است و فقط موضوع گزارش را نشان می‌دهد؛ مخاطبان را در «دسترسی واحدها» تعیین کنید.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'وضعیت انتشار',
            'hint' => 'پیش‌فرض «فعال». غیرفعال کردن گزارش را بدون حذف از زبانهٔ «گزارش‌ها» کاربر مخفی می‌کند. این مهم‌ترین راه کنترل انتشار است.',
        ],
        [
            'icon' => 'image',
            'label' => 'تصویر جلد',
            'hint' => 'فقط تصویر، حداکثر ۵ مگابایت؛ می‌توانید آن را ویرایش، باز یا دانلود کنید.',
        ],
        [
            'icon' => 'file_present',
            'label' => 'فایل گزارش',
            'hint' => 'فقط PDF یا Word، حداکثر ۱۰ مگابایت، الزامی. کاربر همین فایل را از زبانهٔ گزارشات دانلود می‌کند.',
        ],
        [
            'icon' => 'subject',
            'label' => 'توضیحات',
            'hint' => 'ویرایشگر متن با رنگ، عنوان، جدول و لینک. هنگام ذخیره، محتوای خطرناک به‌طور خودکار پاک می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما: ساخت گزارش، بارگذاری فایل و انتشار</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        سه دکمه در انتهای هر ردیف قرار دارد: مشاهده، ویرایش، حذف. دکمهٔ «ساخت گزارش» در هدر صفحه است. جستجوی سراسری پنل گزارش‌ها را با عنوان یا توضیحات پیدا می‌کند و مستقیم به ویرایش می‌رود.
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
                هر دو فایل در فضای عمومی ذخیره می‌شوند؛ یعنی هر کسی نشانی مستقیم فایل را داشته باشد می‌تواند آن را باز کند. دسترسی از راه «وضعیت» کنترل می‌شود: دانلود گزارش غیرفعال در پنل کاربر مسدود است.
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">tips_and_updates</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            برای انتشارِ فوری، فقط کافی است «وضعیت» را فعال بگذارید — گزارش بلافاصله در زبانهٔ «گزارش‌ها» کاربر ظاهر می‌شود. برای بازنگریِ یک گزارشِ زنده، آن را غیرفعال کنید، ویرایش کنید، و دوباره فعال کنید.
        </p>
    </div>
</div>