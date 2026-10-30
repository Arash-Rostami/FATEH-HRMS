@php
    $d500 = convertToPersian('500');

    $ops = [
        [
            'icon' => 'check_circle',
            'label' => 'تأیید',
            'hint' => 'دکمهٔ سبز روی ردیف — فقط روی درخواست‌های «در حال بررسی» ظاهر می‌شود. داخل یک تراکنش و با قفلِ سطر (lockForUpdate) اجرا می‌شود: وضعیت→ Approved، تاریخ و تأییدکننده ثبت، دلیل رد پاک می‌شود. اگر درخواست «پیشنهاد مهارت جدید» بوده، همان مهارت هم فعال می‌شود.',
        ],
        [
            'icon' => 'cancel',
            'label' => 'رد',
            'hint' => 'دکمهٔ قرمز کنار هر درخواست — فقط روی «در حال بررسی». پنجرهٔ دلیل رد باز می‌شود؛ نوشتن دلیل اختیاری و حداکثر ' . $d500 . ' نویسه است. وضعیت به «رد شده» تغییر می‌کند و دلیل ثبت می‌شود؛ تاریخ و تأییدکننده پاک می‌شود.',
        ],
        [
            'icon' => 'done_all',
            'label' => 'تأیید/رد گروهی',
            'hint' => 'درخواست‌ها را انتخاب کنید و از منوی عملیات گروهی استفاده کنید. فقط درخواست‌های «در حال بررسی» پردازش می‌شوند و بقیه نادیده گرفته می‌شوند. تعداد انجام‌شده در پیام نمایش داده می‌شود؛ اگر هیچ درخواست در حال بررسی انتخاب نشده باشد، پیام هشدار می‌آید.',
        ],
    ];
    $rules = [
        [
            'icon' => 'lock',
            'label' => 'دسترسی لازم',
            'hint' => 'تأیید و رد فقط برای کسانی ممکن است که مجوز ویرایش بخش «تأیید مهارت» را دارند. توسعه‌دهنده همیشه دسترسی دارد و سایر مدیران باید مجوز مربوطه را داشته باشند. بدون مجوز دکمه‌ها نمایش داده نمی‌شوند.',
        ],
        [
            'icon' => 'verified_user',
            'label' => 'تأیید مهارت کاتالوگ، مهارت غیرفعال را دوباره فعال نمی‌کند',
            'hint' => 'اگر کاربر مهارت را از کاتالوگ انتخاب کرده (نه پیشنهاد مهارت جدید)، تأیید شما فقط مهارتِ آن کاربر را تأیید می‌کند و مهارت کاتالوگ را فعال نمی‌کند. فقط تأیید «پیشنهاد مهارت جدید» آن مهارت را فعال می‌کند. به این ترتیب مهارتی که شما عمداً غیرفعال کرده‌اید، با تأیید یک درخواست دوباره فعال نمی‌شود.',
        ],
        [
            'icon' => 'notifications',
            'label' => 'پیام در زبانهٔ مهارت‌های کاربر، نه زنگ اعلان',
            'hint' => 'پیام تأیید یا رد تا ۷ روز برای همان کاربر نگه داشته می‌شود و فقط در زبانهٔ «استعدادها»ی او دیده می‌شود — در زنگ اعلان‌های سراسری نمی‌آید. اگر کاربر به آن زبانه سر نزند، پیام را نمی‌بیند.',
        ],
        [
            'icon' => 'lock',
            'label' => 'جلوگیری از تأیید دوباره',
            'hint' => 'هنگام تأیید یا رد، سیستم دوباره بررسی می‌کند که درخواست هنوز «در حال بررسی» باشد — اگر دو مدیر هم‌زمان روی یک درخواست تأیید بزنند، فقط اولی اثر می‌کند و دومی بی‌اثر است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: تأیید یا ردِ درخواست‌های در حال بررسی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        دو دکمه کنار هر درخواستِ «در حال بررسی» دیده می‌شود: تأیید و رد. عملیات گروهی هم برای موارد انتخاب‌شده وجود دارد. در این صفحه امکان ساخت یا ویرایش نیست — درخواست‌ها از پنل کاربر می‌آیند.
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
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">rule</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکته‌های مهم تأیید و رد</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rules as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                تأیید یا رد فقط روی درخواست‌های «در حال بررسی» اثر دارد — روی درخواست تأییدشده یا ردشده کاری انجام نمی‌شود و دکمه‌ها دیده نمی‌شوند.
            </p>
        </div>
    </div>
</div>