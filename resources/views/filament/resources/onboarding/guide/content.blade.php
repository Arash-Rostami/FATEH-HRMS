@php
    $tabs = [
        [
            'icon' => 'waving_hand',
            'label' => 'خوش‌آمدگویی',
            'hint' => 'تنها زبانهٔ اجباری. ویرایشگر متن شامل رنگ، تیتر، جدول، نقل‌قول، فهرست و خط جداکننده است.',
        ],
        [
            'icon' => 'flag',
            'label' => 'مأموریت / چشم‌انداز / برنامه زمانی',
            'hint' => 'سه زبانهٔ اختیاری با همان ویرایشگر غنی. هر کدام که خالی بماند، کارتِ آن در پنل کاربر خودکار حذف می‌شود — نیازی به فعال/غیرفعال کردن جداگانه نیست.',
        ],
        [
            'icon' => 'videocam',
            'label' => 'ویدیوها',
            'hint' => 'فهرستی از ویدیوها؛ هر ویدیو شامل عنوان، فایل ویدیو (حداکثر ۹۵ مگابایت)، تصویر پیش‌نمایش اختیاری (حداکثر ۵ مگابایت) و مدت زمان (مثل ۳:۴۵) است.',
        ],
        [
            'icon' => 'menu_book',
            'label' => 'راهنماها',
            'hint' => 'فهرستی از فایل‌های راهنما؛ هر مورد شامل عنوان و فایل (PDF/Word/Excel، حداکثر ۱۰ مگابایت) است. نوع و حجم فایل هنگام ذخیره خودکار ثبت می‌شود و در خروجی اکسل و صفحهٔ مشاهده دیده می‌شود.',
        ],
        [
            'icon' => 'dashboard_customize',
            'label' => 'بخش‌های دیگر',
            'hint' => 'فهرستی از بخش‌ها؛ هر بخش سه قسمت دارد: نام لاتین (مثل parking)، عنوان اختیاری و متن. نام لاتین آیکون و عنوان پیش‌فرض را تعیین می‌کند؛ اگر عنوان را خالی بگذارید، عنوان پیش‌فرض همان نام نمایش داده می‌شود.',
        ],
    ];

    $templates = [
        [
            'icon' => 'auto_awesome',
            'label' => 'افزودن سریع از الگوها',
            'hint' => 'در زبانهٔ «بخش‌های اضافی» از میان ۴۵ نمونه در ۹ دسته انتخاب کنید. نمونه‌ها با عنوان و توضیح آماده اضافه می‌شوند و می‌توانید آن‌ها را ویرایش کنید.',
        ],
        [
            'icon' => 'style',
            'label' => 'نمونهٔ آماده یا بخش سفارشی',
            'hint' => 'بخش‌های سفارشی در پنل کاربر با حاشیهٔ نقطه‌چین و برچسب «سفارشی» از نمونه‌های آماده متمایز می‌شوند. هنگام ویرایش نیز بخش‌های سفارشی ذخیره‌شده در فهرست انتخاب دیده می‌شوند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">edit_note</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">فرم ویرایش: یک بخش تنظیمات + هفت زبانهٔ محتوا</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فرم از دو ستون تشکیل شده: ستونِ کوچکِ «تنظیمات» (کاربر مشخص + فعال) و ستونِ بزرگ شامل هفت زبانهٔ محتوایی. زبانه‌ها مستقل از هم ذخیره می‌شوند — پر کردنِ همهٔ آن‌ها الزامی نیست؛ فقط «خوش‌آمدگویی» اجباری است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tab</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های محتوای فرم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">auto_awesome</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">الگوهای بخش اضافی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($templates as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                «افزودن سریع از الگوها» فقط یک کمک برای ویرایش است و خودش ذخیره نمی‌شود؛ محتوای نهایی در فهرست بخش‌های پایین آن قرار می‌گیرد.
            </p>
        </div>
    </div>
</div>