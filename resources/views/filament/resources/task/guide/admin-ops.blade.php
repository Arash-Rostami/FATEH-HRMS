@php
    $formTabs = [
        [
            'icon' => 'assignment',
            'label' => 'زبانهٔ اصلی',
            'hint' => 'ایجادکننده، مسئول انجام، عنوان، توضیح، مهلت و وضعیت را وارد کنید. ایجادکننده پس از ثبت تغییر نمی‌کند. پیش از انتخاب «انجام‌شده»، نتیجهٔ کار را در اطلاعات تکمیلی مشخص کنید.',
        ],
        [
            'icon' => 'bar_chart',
            'label' => 'زبانهٔ اطلاعات تکمیلی',
            'hint' => 'بخش سازمانی و زیرواحدها، منبع درخواست، همکاران، مسئول پیگیری، نتیجهٔ کار و پیوست‌ها در این بخش‌اند. برای چک‌لیست، مراحل را بنویسید و سهم هر مرحله از پیشرفت را تعیین کنید؛ مجموع سهم‌ها بهتر است ۱۰۰ درصد باشد.',
        ],
        [
            'icon' => 'label',
            'label' => 'زبانهٔ پروژه',
            'hint' => 'پروژه، اولویت و برچسب‌ها را انتخاب کنید. حداکثر ۱۰ برچسب، هرکدام تا ۳۰ کاراکتر. در پروژهٔ دارای فهرست برچسب، برچسب تازه باید از همان فهرست باشد؛ برچسب‌های قبلی وظیفه حفظ می‌شوند.',
        ],
        [
            'icon' => 'tune',
            'label' => 'اطلاعات دلخواه',
            'hint' => 'این بخش برای اطلاعات اضافه، مثل شمارهٔ پیگیری است. نام را با حروف کوچک انگلیسی، رقم و زیرخط (_) و متن را تا ۱۰۰۰ کاراکتر بنویسید. اگر پروژه نام فارسی تعریف کرده، همان نام نمایش داده می‌شود.',
        ],
    ];

    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'وضعیت، افراد، جزئیات، مهلت، تاریخ‌ها، درصد پیشرفت و پیام‌های ثبت‌شده را در صفحهٔ جزئیات ببینید.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'مهلت را به شمسی وارد کنید؛ نمی‌تواند از پایان پروژه دیرتر باشد. در پروژهٔ دارای زمان مجاز انجام، مهلت خالی ممکن است خودکار تعیین شود. افراد تازهٔ انتخاب‌شده باید عضو فعال و مجاز پروژه باشند. وظیفهٔ ساخته‌شده از تیکت را در خود تیکت تغییر دهید. وظیفهٔ مرحلهٔ فعلی چرخهٔ فعال را نمی‌توان حذف کرد یا به پروژهٔ دیگر برد.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف قابل بازیابی',
            'hint' => 'حذف معمولی قابل بازیابی است. وظیفهٔ بدون پروژه، پس از ۳۰ روز از حذف، همراه پیوست‌هایش برای همیشه پاک می‌شود؛ برای برگرداندن آن پیش از این زمان اقدام کنید.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'وظایف موردنظر را انتخاب و از عملیات گروهی، خروجی Excel را بگیرید. فایل، اطلاعات وظیفه و تاریخ‌های شمسی آن را برای بررسی و گزارش‌گیری در اختیار شما می‌گذارد.',
        ],
        [
            'icon' => 'check_badge',
            'label' => 'تأیید انجام',
            'hint' => 'اگر پروژه تأیید را لازم کرده باشد، کار انجام‌شده توسط اعضا منتظر تأیید می‌ماند. با دکمهٔ تأیید، نام و زمان تأییدکننده ثبت می‌شود. پس از تأیید، کاربران عادی نمی‌توانند وظیفه را تغییر دهند؛ مدیر پروژه و مدیر مجاز پنل می‌توانند. «بازگشایی وظیفه» در پنل کاربر، تأیید را برمی‌دارد و کار را به «در حال انجام» برمی‌گرداند.',
        ],
        [
            'icon' => 'assignment_turned_in',
            'label' => 'مشاهده تسک‌شیت',
            'hint' => 'گزارش عملکرد مسئول انجام را در صفحه‌ای تازه باز می‌کند؛ اگر مسئول مشخص نشده، گزارش ایجادکننده باز می‌شود. دسترسی شما به گزارش جداگانه بررسی می‌شود.',
        ],
    ];

    $attachments = [
        'حداکثر ' . convertToPersian('5') . ' فایل؛ هر فایل تا ' . convertToPersian('10') . ' مگابایت (تصویر/PDF/Word/Excel).',
        'برای پیدا کردن پیوست، خود وظیفه را باز کنید؛ نام ذخیره‌شدهٔ فایل ممکن است با نام اولیه فرق داشته باشد.',
        'برای هر پیوست، نام، نوع و اندازهٔ فایل هم ثبت می‌شود.',
        'هنگام حذف دائمی وظیفه، فایل‌های پیوست آن هم پاک می‌شوند.',
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما: نظارت، ویرایش، حذف/بازیابی و خروجی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        وظایف حذف‌شده را هم می‌توانید در این صفحه پیدا و پیش از حذف دائمی بازیابی کنید. جستجو با عنوان، توضیح و نام ایجادکننده یا مسئول انجام کار می‌کند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فرم و زبانه‌ها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($formTabs as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $f['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">attach_file</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">پیوست‌ها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($attachments as $a)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">file_present</span>
                    </div>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium flex-1">{!! $a !!}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">reply</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">عملیات روی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ops as $op)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{!! $op['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ضرب‌الاجل در ستون و صفحهٔ جزئیات رنگ می‌گیرد: انجام‌شده=سبز، در انتظار یا گذشته=قرمز، {{ convertToPersian('2') }} روز مانده=زرد، بقیه=آبی.
            </p>
        </div>
    </div>
</div>
