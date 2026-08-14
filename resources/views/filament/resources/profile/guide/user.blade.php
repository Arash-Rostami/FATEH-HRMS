@php
    $d2 = convertToPersian('2');
    $d5 = convertToPersian('5');
    $d11 = convertToPersian('11');
    $d90 = convertToPersian('90');
    $d100 = convertToPersian('100');

    $panels = [
        ['icon' => 'settings_account_box', 'label' => 'اطلاعات فردی', 'hint' => 'کاربر مشخصات هویتی، تماس، رنگ‌های موردعلاقه، تاریخ تولد و تصویر پروفایل خود را ویرایش می‌کند. ایمیل فقط خواندنی است (از حساب کاربر می‌آید). حذف تصویر با تأیید انجام می‌شود و در صورت نبود تصویر، آواتارِ حروفِ اول نام نشان داده می‌شود.'],
        ['icon' => 'list_alt', 'label' => 'اطلاعات تکمیلی', 'hint' => 'کاربر فقط فیلدهای غیرادمینی کاتالوگ را ویرایش می‌کند. «واحد» و «بخش» فقط توسط ادمین تنظیم می‌شوند و در هدرِ صفحهٔ کاربر فقط نمایش داده می‌شوند. ذخیرهٔ این زبانه نیازمند تکمیلِ «اطلاعات فردی» است.'],
        ['icon' => 'workspace_premium', 'label' => 'استعدادها (مهارت‌ها)', 'hint' => 'کاربر از فهرست مهارت‌های شرکت انتخاب یا مهارت جدیدی پیشنهاد می‌دهد → درخواست در صف تأیید ادمین می‌رود (Pending). رد شدن با دلیل و «درخواست مجدد». هر مهارت پس از تأیید یک «سطح» می‌گیرد: Endorsed (۴ تأیید یا بیشتر)، Active (استفاده در ' . $d90 . ' روز اخیر)، یا Unused. دکمه‌های «استفاده اخیر»، «خصوصی/عمومی» و «آماده راهنمایی» روی هر مهارت هست. نشان «جدید» موقتاً روی این زبانه است.'],
        ['icon' => 'psychology', 'label' => 'درباره من', 'hint' => '۶ کلید ثابت (bio/movies/music/hobbies/food/sports) به‌علاوهٔ کلیدهای اضافه که کاربر خودش اضافه می‌کند. خلاصهٔ این متن در هدرِ پروفایل زیر نام نمایش داده می‌شود.'],
        ['icon' => 'cloud_upload', 'label' => 'مدارک و اسناد', 'hint' => 'مدارک استاندارد (حداکثر ' . $d2 . ' مگابایت) و مدارک سفارشی (حداکثر ' . $d5 . ' مگابایت). هر فایل قبل از ثبت نهایی با یک تأییدیهٔ «صحت فایل» پذیرفته می‌شود؛ پس از ثبت نهایی ویرایش ممکن نیست. وضعیت هر مدرک: بارگذاری‌نشده / در انتظار تأیید / تأیید شده.'],
        ['icon' => 'vpn_key', 'label' => 'دسترسی و امنیتی', 'hint' => 'مدیریت رمزهای ذخیره‌شده (Credentials). جستجو با نام برنامه یا نام کاربری؛ در «حالت تمرکز» (انتخاب از پالت فرمان) فهرست روی یک رکورد پین می‌شود و جستجو جای آن را می‌گیرد.'],
        ['icon' => 'apartment', 'label' => 'آنبوردینگ', 'hint' => 'زبانهٔ آشنایی با شرکت — از بقیه با یک خط جداکننده متمایز است. محتوای آن مستقل از پروفایل است.'],
    ];
    $header = [
        ['icon' => 'person', 'label' => 'سمت نمایشی', 'hint' => 'اگر در اطلاعات تکمیلی «عنوان نمایشی» پر شده باشد، به‌جای سمت سازمانی (Position) در هدر نشان داده می‌شود.'],
        ['icon' => 'domain', 'label' => 'واحد / بخش / مدت همکاری', 'hint' => 'هدر، نام واحد، واحد/بخش (اگر ادمین تنظیم کرده باشد) و مدت همکاری را از start_date یا تاریخ ساخت حساب محاسبه می‌کند.'],
        ['icon' => 'schedule', 'label' => 'درصد تکمیل', 'hint' => 'فقط ' . $d11 . ' فیلد خاص (جنسیت، وضعیت تأهل، شماره ملی، مدرک، رشته، تولد، موبایل، آدرس، واحد، بیمه، تماس اضطراری) درصد را تشکیل می‌دهند — نه همهٔ فیلدها. زیر ' . $d100 . ' درصد، یک نشان قرمز روی زبانهٔ «اطلاعات فردی» ظاهر می‌شود.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ /profile چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ پروفایل کاربر هفت زبانه دارد: اطلاعات فردی، اطلاعات تکمیلی، استعدادها، درباره من، مدارک و اسناد، دسترسی و امنیتی، آنبوردینگ. وقتی کاربری از وضعیت پروفایلش شکایت می‌کند، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های پنل کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($panels as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $p['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">auto_stories</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">هدرِ پروفایل و درصد تکمیل</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($header as $h)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $h['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $h['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $h['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                روی زبانهٔ مهارت‌ها و دکمهٔ زنگِ هدر، دو راهنمای کوچک (سطحِ مهارت‌ها و نشانگرهای اعلان) از قبل وجود دارد؛ راهنمای جامعِ همین ماژول با دکمهٔ «?» کنار عنوان صفحه باز می‌شود.
            </p>
        </div>
    </div>
</div>