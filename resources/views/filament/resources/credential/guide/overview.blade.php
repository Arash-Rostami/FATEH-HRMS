@php
    $fields = [
        [
            'icon' => 'apps',
            'label' => 'نام برنامه / سامانه (app_name)',
            'tag' => 'الزامی',
            'hint' => 'نام کامل سامانه‌ای که این اعتبارنامه برای آن است (مثلاً «سامانه مالی»، «پورتال سازمان»). عنوان جستجوی سراسری و ستون اصلی جدول همین فیلد است. حداکثر ۲۵۵ کاراکتر.',
        ],
        [
            'icon' => 'person',
            'label' => 'کاربر (user_id)',
            'tag' => 'ادمینی',
            'hint' => 'صاحبِ این اعتبارنامه. فقط ادمین‌های با نقش ارتقا‌یافته این فیلد را در فرم می‌بینند و می‌توانند آن را به هر کاربری منتسب کنند؛ کاربر عادی هنگام ساخت، خودش به‌صورت خودکار به‌عنوان صاحب ثبت می‌شود و فیلد برایش پنهان است.',
        ],
        [
            'icon' => 'alternate_email',
            'label' => 'نام کاربری (username)',
            'tag' => 'الزامی',
            'hint' => 'نام کاربری دقیق در آن سامانه (حساس به حروف کوچک/بزرگ). در جدول، اینفولیست و خروجی قابل کپی است.',
        ],
        [
            'icon' => 'lock',
            'label' => 'رمز عبور (password)',
            'tag' => 'رمزنگاری‌شده',
            'hint' => 'رمز هنگام ذخیره با Crypt::encryptString کُدگذاری می‌شود و هنگام خواندن با Crypt::decryptString باز می‌گردد — در پایگاه داده به‌صورت متنِ نامفهوم ذخیره می‌شود، نه Plain-text. فیلد password قابل بازنمایی (reveal) است و در جدول/اینفولیست با فونت مونو و قابلیت کپی نمایش داده می‌شود.',
        ],
        [
            'icon' => 'link',
            'label' => 'لینک ورود (link)',
            'tag' => 'اختیاری',
            'hint' => 'آدرس صفحهٔ ورود به سامانه (حداکثر ۵۰۰ کاراکتر، فقط URL معتبر). در جدول و اینفولیست قابل کلیک و بازشدن در تب جدید است و فیلتر «دارای لینک» و زبانه‌های فهرست روی همین فیلد کار می‌کنند.',
        ],
        [
            'icon' => 'note',
            'label' => 'یادداشت (note)',
            'tag' => 'اختیاری',
            'hint' => 'محدودیت‌های دسترسی، دستورالعمل‌های خاص یا نکات امنیتی مرتبط. در کارت کاربر با truncation نمایش داده می‌شود ولی در اینفولیست کامل است.',
        ],
    ];
    $surfaces = [
        [
            'icon' => 'admin_panel_settings',
            'label' => 'پنل ادمین (این صفحه)',
            'hint' => 'منبع اصلی مدیریت: ساخت، ویرایش، حذف، خروجی اکسل و جستجوی سراسری. ادمین همهٔ اعتبارنامه‌ها را می‌بیند و می‌تواند به هر کاربری منتسب کند.',
        ],
        [
            'icon' => 'groups',
            'label' => 'صفحهٔ کاربر (UserResource)',
            'hint' => 'زیرِ صفحهٔ ویرایش هر کاربر، مدیریت ارتباط «اطلاعات ورود» همان رکوردها را نشان می‌دهد — بدون فیلد کاربر (چون صاحب از روی رکوردِ والد مشخص است). تغییر از یکجا به اضافه‌کردن/ویرایش همان رکوردهاست.',
        ],
        [
            'icon' => 'visibility',
            'label' => 'پنل کاربر (Profile — دسترسی و امنیتی)',
            'hint' => 'کاربر در زبانهٔ «دسترسی و امنیتی» پروفایل خود، فقط اعتبارنامه‌های خودش را می‌بیند (فقط‌خواندنی): نام سامانه، نام کاربری و رمز با قابلیت کپی/بازنمایی، لینک ورود و یادداشت. کاربر نمی‌تواند رکوردی بسازد یا حذف کند — همه‌چیز از سمت ادمین تعریف می‌شود.',
        ],
    ];
    $d255 = convertToPersian('255');
    $d500 = convertToPersian('500');
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">vpn_key</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">هر رکورد، یک مجموعهٔ ورود به یک سامانهٔ بیرونی برای یک کاربر است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        «اطلاعات ورود» مخزنِ متمرکزِ نام کاربری و رمزِ سامانه‌های سازمانی است که به کاربران منتسب می‌شود. رمزها در پایگاه داده رمزنگاری‌شده ذخیره می‌شوند و کاربر از زبانهٔ «دسترسی و امنیتی» پروفایل خود فقط‌محدود به رکوردهای خودش — آن‌ها را می‌بیند. این یک ماژول کاملاً مدیریتی است؛ کاربر رکوردی نمی‌سازد — ادمین تعریف می‌کند و کاربر مصرف می‌کند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر اعتبارنامه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($fields as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                                {{ $f['tag'] }}
                            </span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                سقفِ نام برنامه و نام کاربری {{ $d255 }} و سقفِ لینک {{ $d500 }} کاراکتر است؛ رمز محدودیتی ندارد.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">این رکوردها کجا ظاهر می‌شوند؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($surfaces as $s)
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
                کاربر عادی در پنل ادمین هم فقط رکوردهای خودش را می‌بیند — کوئری وقتی نقش ارتقا‌یافته نیست به user_idِ همان کاربر محدود می‌شود.
            </p>
        </div>
    </div>
</div>