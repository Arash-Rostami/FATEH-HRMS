@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'صفحهٔ جزئیات فقط‌خواندنی: شناسه، کاربر، عنوان، یادداشت، سررسید، تکرار، زمان انجام، «به‌تعویق‌افتاده تا»، تاریخ ایجاد و روش‌های اعلان. تاریخ‌ها شمسی نمایش داده می‌شوند.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'فقط شش فیلد قابل ویرایش است: کاربر، تکرار، عنوان، تاریخ و ساعت سررسید، یادداشت. عنوان تا ۱۹۱ نویسه و یادداشت تا ۱۰۰۰ نویسه است. ساعت پیش‌فرض ۰۹:۰۰ و سال‌های قابل انتخاب از ۱۴۰۰ تا پنج سال بعد است.',
        ],
        [
            'icon' => 'add_circle',
            'label' => 'ساخت یادآوری',
            'hint' => 'یادآوری ساخته‌شده از این صفحه به هیچ وظیفه، تیکت یا پروژه‌ای وصل نیست (یادداشت آزاد است) و روش اعلانش همان پیش‌فرض سیستم، یعنی نشانگر، پیام اعلانات و پیام ثابت، می‌ماند. نشانگر منو هم روشن می‌ماند و در نقطهٔ نوار پیمایشِ صاحب یادآوری شمرده می‌شود، هرچند در فرم خودِ کاربر این گزینه فقط برای یادآوریِ وصل‌به‌رکورد فعال است. ایمیل و پیامک از اینجا قابل تنظیم نیست.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» (با تأیید) ردیف را برای همیشه برمی‌دارد؛ سطل بازیابی وجود ندارد. «حذف گروهی» هم روی ردیف‌های انتخاب‌شده همین کار را می‌کند.',
        ],
        [
            'icon' => 'lock',
            'label' => 'چه چیزی از اینجا قابل تغییر نیست',
            'hint' => 'روش‌های اعلان، زمان انجام و «به‌تعویق‌افتاده تا» فقط در صفحهٔ مشاهده دیده می‌شوند. «انجام شد»، به‌تعویق‌انداختن و توقف تکرار هم اقدامی در پنل ادمین ندارند و فقط صاحب یادآوری از پنل خودش آن‌ها را انجام می‌دهد.',
        ],
        [
            'icon' => 'shield_person',
            'label' => 'دسترسی',
            'hint' => 'مشاهده، ساخت، ویرایش و حذف هرکدام با دسترسی جداگانهٔ ماژول «یادآوری» کنترل می‌شود؛ دکمه‌ای که دسترسی‌اش را ندارید دیده نمی‌شود.',
        ],
    ];

    $cautions = [
        ['icon' => 'notifications_off', 'label' => 'تغییر سررسید و ایمیل ارسال‌شده', 'hint' => 'اگر ایمیل یا پیامک یک یادآوری قبلاً ارسال شده باشد، تغییر سررسید از این فرم علامت «ارسال شد» را پاک نمی‌کند و دوباره ارسال نمی‌شود. فقط تغییر سررسید توسط خود کاربر در پنلش، یا به‌تعویق‌انداختن، این علامت را بازنشانی می‌کند.'],
        ['icon' => 'auto_delete', 'label' => 'پاک‌شدن خودکار', 'hint' => 'یادآوری‌های انجام‌شده هر روز بررسی می‌شوند و ۹۰ روز پس از زمان انجام حذف می‌شوند. یادآوری‌های انجام‌نشده، هر قدر قدیمی، هرگز خودکار پاک نمی‌شوند.'],
        ['icon' => 'bolt', 'label' => 'اثر ذخیره', 'hint' => 'اگر سررسید (یا پایان به‌تعویق‌افتادگی) تا ۲۴ ساعت آینده باشد، هر ذخیره کار لحظهٔ سررسید را برای این یادآوری دوباره در صف می‌گذارد؛ این کار پیام‌های اعلانات و ثابت را می‌سازد و ایمیل/پیامک را در صورت وجود ارسال می‌کند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: نظارت، اصلاح عنوان و سررسید، حذف</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        این بخش برای نظارت طراحی شده، نه جایگزین پنل کاربر. سه دکمهٔ عملیات روی هر ردیف (پس از سلول‌ها) مشاهده، ویرایش و حذف هستند و دکمهٔ ساخت در هدر صفحه قرار دارد. تکمیل و به‌تعویق‌انداختن عمداً در پنل ادمین نیست، چون هرکدام آثار جانبی دارند (ساخت یادآوری بعدی در تکرار، بازنشانی ارسال).
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات و محدودیت‌ها</p>
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-error-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-error-container)]">warning</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-error-container)]">نکات مهم پیش از ویرایش یا حذف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($cautions as $c)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $c['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $c['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $c['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برای یادآوری‌ای که فقط مزاحم است، حذف کافی است؛ لازم نیست منتظر پاک‌شدن خودکار بمانید.
            </p>
        </div>
    </div>
</div>
