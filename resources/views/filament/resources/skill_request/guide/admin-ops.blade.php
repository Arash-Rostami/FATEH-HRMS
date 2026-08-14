@php
    $d500 = convertToPersian('500');

    $ops = [
        [
            'icon' => 'check_circle',
            'label' => 'تأیید (approve)',
            'hint' => 'دکمهٔ سبز روی ردیف — فقط روی درخواست‌های «در حال بررسی» ظاهر می‌شود. داخل یک تراکنش و با قفلِ سطر (lockForUpdate) اجرا می‌شود: وضعیت→ Approved، تاریخ و تأییدکننده ثبت، دلیل رد پاک می‌شود. اگر درخواست «پیشنهاد نام جدید» بوده، مهارتِ پیشنهادی هم فعال می‌شود.',
        ],
        [
            'icon' => 'cancel',
            'label' => 'رد (reject)',
            'hint' => 'دکمهٔ قرمز روی ردیف — فقط روی «در حال بررسی». مودالِ دلایل باز می‌شود؛ دلیل اختیاری تا سقف ' . $d500 . ' کاراکتر. وضعیت→ Rejected و دلیل ثبت می‌شود؛ تاریخ و تأییدکننده پاک می‌شود.',
        ],
        [
            'icon' => 'done_all',
            'label' => 'تأیید/رد گروهی',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده. فقط ردیف‌های «در حال بررسی» داخل دسته پردازش می‌شوند — بقیه بی‌توجه رد می‌شوند. شمارشِ انجام‌شده در اعلان نشان داده می‌شود؛ اگر هیچ معوقی انتخاب نشده باشد، اعلان هشدار می‌دهد.',
        ],
    ];
    $rules = [
        [
            'icon' => 'lock',
            'label' => 'درگاه دسترسی (canEdit)',
            'hint' => 'تأیید و رد فقط با دسترسی update روی ماژول skill_request ممکن است. isDeveloper بدون شرط عبور می‌کند؛ سایر ادمین‌ها نیاز به Permission متناظر دارند. روی ردیفِ غیرمجاز دکمه‌ها پنهان است و فراخوانی مستقیم هم ۴۰۳ می‌شود.',
        ],
        [
            'icon' => 'verified_user',
            'label' => 'تأییدِ کاتالوگ، مهارتِ غیرفعال را احیا نمی‌کند',
            'hint' => 'اگر کاربر مهارت را از کاتالوگ انتخاب کرده (نه پیشنهاد نام جدید)، تأییدِ شما فقط ردیف skill_user را تأیید می‌کند — مهارتِ کاتالوگ را فعال نمی‌کند. فقط تأییدِ «پیشنهاد نام جدید» مهارتِ پیشنهادی را فعال می‌کند. به این ترتیب مهارتی که ادمین عمداً غیرفعال کرده، با تأییدِ درخواستِ کاتالوگ دوباره فعال نمی‌شود.',
        ],
        [
            'icon' => 'notifications',
            'label' => 'اعلان در زبانهٔ مهارت کاربر، نه زنگ',
            'hint' => 'پیامِ تأیید/رد در کشِ اختصاصیِ کاربر (۷ روز) نگه‌داری می‌شود و فقط در زبانهٔ «استعدادها»ی او نمایش داده می‌شود — در زنگِ اعلانِ سراسری ظاهر نمی‌شود. محدودیتِ شناخته‌شده: کاربری که به آن زبانه برنگردد، اعلان را نمی‌بیند.',
        ],
        [
            'icon' => 'lock',
            'label' => 'قفلِ سطر و تراکنش',
            'hint' => 'تأیید و رد داخل DB::transaction با lockForUpdate اجرا می‌شود و وضعیتِ Pending دوباره داخلِ قفل بررسی می‌شود — اگر دو ادمین هم‌زمان روی یک ردیف تأیید بزنند، فقط اولی اجرا می‌شود و دومی no-op است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: تأیید یا ردِ درخواست‌های در حال بررسی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        دو دکمهٔ عملیات روی هر ردیفِ «در حال بررسی» ظاهر می‌شود: تأیید و رد. عملیات گروهی نیز برای انتخاب‌ها موجود است. هیچ مسیرِ «ساخت» یا «ویرایش» در این صفحه نیست — درخواست‌ها از پنل کاربر می‌آیند.
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
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">قواعد و تله‌های تأیید/رد</p>
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
                تأیید یا رد فقط روی ردیف‌های «در حال بررسی» اثر دارد — روی ردیفِ تأییدشده یا ردشده no-op است و دکمه‌ها پنهان می‌شوند.
            </p>
        </div>
    </div>
</div>