@php
    $fields = [
        [
            'icon' => 'apps',
            'label' => 'نام برنامه یا سامانه',
            'tag' => 'الزامی',
            'hint' => 'نام کامل سامانه‌ای که این اطلاعات ورود برای آن است (مثلاً «سامانه مالی»، «پورتال سازمان»). عنوان جستجوی سراسری و ستون اصلی جدول همین فیلد است. حداکثر ۲۵۵ نویسه.',
        ],
        [
            'icon' => 'person',
            'label' => 'کاربر',
            'tag' => 'فقط ادمین',
            'hint' => 'صاحبِ این اعتبارنامه. فقط ادمین‌ها این فیلد را در فرم می‌بینند و می‌توانند آن را به هر کاربری منتسب کنند؛ کاربر عادی هنگام ساخت، خودش به‌صورت خودکار به‌عنوان صاحب ثبت می‌شود و فیلد برایش پنهان است.',
        ],
        [
            'icon' => 'alternate_email',
            'label' => 'نام کاربری',
            'tag' => 'الزامی',
            'hint' => 'نام کاربریِ دقیق در آن سامانه (حروف کوچک و بزرگ فرق دارند). در جدول و صفحهٔ جزئیات قابل کپی است.',
        ],
        [
            'icon' => 'lock',
            'label' => 'رمز عبور',
            'tag' => 'رمزنگاری‌شده',
            'hint' => 'رمز هنگام ذخیره رمزگذاری می‌شود و هنگام نمایش دوباره خوانا می‌شود؛ در پایگاه داده به‌صورت متن نامفهوم ذخیره می‌شود، نه متن ساده. رمز در جدول و صفحهٔ جزئیات قابل نمایش و کپی است.',
        ],
        [
            'icon' => 'link',
            'label' => 'لینک ورود',
            'tag' => 'اختیاری',
            'hint' => 'آدرس صفحهٔ ورود به سامانه (حداکثر ۵۰۰ کاراکتر، فقط آدرس معتبر). در جدول و صفحهٔ جزئیات قابل کلیک است و در زبانهٔ تازهٔ مرورگر باز می‌شود و فیلتر «دارای لینک» و زبانه‌های فهرست روی همین فیلد کار می‌کنند.',
        ],
        [
            'icon' => 'note',
            'label' => 'یادداشت',
            'tag' => 'اختیاری',
            'hint' => 'محدودیت‌های دسترسی، دستورالعمل‌های خاص یا نکات امنیتی مرتبط. در کارت کاربر کوتاه‌شده نمایش داده می‌شود، ولی در صفحهٔ جزئیات کامل است.',
        ],
    ];
    $surfaces = [
        [
            'icon' => 'admin_panel_settings',
            'label' => 'بخش مدیریت',
            'hint' => 'منبع اصلی مدیریت: ساخت، ویرایش، حذف، خروجی اکسل و جستجوی سراسری. ادمین همهٔ اطلاعات ورود را می‌بیند و می‌تواند به هر کاربری منتسب کند.',
        ],
        [
            'icon' => 'groups',
            'label' => 'صفحهٔ کاربر',
            'hint' => 'در پایین صفحهٔ ویرایش هر کاربر، بخش «اطلاعات ورود» همان موردها را نشان می‌دهد، بدون فیلد کاربر (چون صاحب از روی همان کاربر مشخص است). تغییر از هر دو جا روی همان موردها اثر می‌گذارد.',
        ],
        [
            'icon' => 'visibility',
            'label' => 'بخش کاربر (پروفایل — دسترسی و امنیتی)',
            'hint' => 'کاربر در زبانهٔ «دسترسی و امنیتی» پروفایل خود، فقط اعتبارنامه‌های خودش را می‌بیند (فقط‌خواندنی): نام سامانه، نام کاربری و رمز با قابلیت کپی/بازنمایی، لینک ورود و یادداشت. کاربر نمی‌تواند موردی بسازد یا حذف کند — همه‌چیز از سمت ادمین تعریف می‌شود.',
        ],
    ];
    $d255 = convertToPersian('255');
    $d500 = convertToPersian('500');
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">vpn_key</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">هر مورد، اطلاعات ورود یک کاربر به یک سامانهٔ بیرونی است</p>
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
                سقفِ نام برنامه یا سامانه و نام کاربری {{ $d255 }} و سقفِ لینک {{ $d500 }} نویسه است؛ رمز محدودیتی ندارد.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">این موردها کجا ظاهر می‌شوند؟</p>
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
                کاربر عادی در پنل ادمین هم فقط موردهای خودش را می‌بیند.
            </p>
        </div>
    </div>
</div>