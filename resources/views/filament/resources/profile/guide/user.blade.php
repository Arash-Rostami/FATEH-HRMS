@php
    $d2 = convertToPersian('2');
    $d5 = convertToPersian('5');
    $d11 = convertToPersian('11');
    $d90 = convertToPersian('90');
    $d100 = convertToPersian('100');

    $panels = [
        ['icon' => 'settings_account_box', 'label' => 'اطلاعات فردی', 'hint' => 'در این بخش، کاربر می‌تواند مشخصات هویتی، اطلاعات تماس، رنگ‌های دلخواه، تاریخ تولد و تصویر نمایه خود را ویرایش نماید. فیلد ایمیل تنها قابل‌مشاهده است (از اطلاعات حساب کاربری فراخوانی می‌شود). حذف تصویر نمایه نیازمند تأیید بوده و در صورت عدم وجود تصویر، آواتار پیش‌فرض شامل حروف نخست نام کاربر نمایش داده خواهد شد.'],
        ['icon' => 'list_alt', 'label' => 'اطلاعات تکمیلی', 'hint' => 'کاربر تنها قادر به ویرایش فیلدهای عمومی (غیرمدیریتی) در این بخش می‌باشد. مقادیر «واحد» و «بخش» منحصراً توسط مدیر سیستم تعیین شده و صرفاً در سربرگ صفحه کاربر به نمایش درمی‌آیند. ثبت اطلاعات این زبانه، مشروط به تکمیل بخش «اطلاعات فردی» است.'],
        ['icon' => 'workspace_premium', 'label' => 'استعدادها (مهارت‌ها)', 'hint' => 'کاربر می‌تواند از میان مهارت‌های سازمانی موجود انتخاب نموده و یا مهارت جدیدی پیشنهاد دهد؛ درخواست‌های جدید در صف بررسی مدیر سیستم قرار می‌گیرند (در انتظار تأیید). در صورت رد درخواست، دلیل آن ذکر شده و امکان «درخواست مجدد» وجود دارد. هر مهارت پس از تأیید، سطحی دریافت می‌کند: «تأییدشده» (دارای ۴ تأیید یا بیشتر)، «فعال» (استفاده در ' . $d90 . ' روز گذشته)، و یا «بدون استفاده». گزینه‌های «استفاده اخیر»، «وضعیت نمایش (خصوصی/عمومی)» و «آمادگی برای راهنمایی» برای هر مهارت تعبیه شده است. نشان «جدید» به‌طور موقت بر روی این زبانه نمایش داده می‌شود.'],
        ['icon' => 'psychology', 'label' => 'درباره من', 'hint' => 'این بخش شامل ۶ فیلد پیش‌فرض (بیوگرافی، فیلم، موسیقی، سرگرمی‌ها، غذا و ورزش) به همراه فیلدهای سفارشی است که توسط کاربر افزوده می‌شوند. خلاصه‌ای از این اطلاعات در سربرگ نمایه و در پایین نام کاربر به نمایش درمی‌آید.'],
        ['icon' => 'cloud_upload', 'label' => 'مدارک و اسناد', 'hint' => 'شامل مدارک استاندارد (با حجم حداکثر ' . $d2 . ' مگابایت) و مدارک سفارشی (با حجم حداکثر ' . $d5 . ' مگابایت). پیش از ثبت نهایی، هر فایل نیازمند یک تأییدیه «صحت فایل» می‌باشد؛ پس از ثبت نهایی، امکان ویرایش وجود نخواهد داشت. وضعیت مدارک شامل سه حالتِ «بارگذاری‌نشده»، «در انتظار تأیید» و «تأییدشده» است.'],
        ['icon' => 'vpn_key', 'label' => 'دسترسی و امنیتی', 'hint' => 'بخش مدیریت داده‌های اعتباری و گذرواژه‌های ذخیره‌شده. امکان جستجو بر اساس نام سامانه یا نام کاربری فراهم است؛ در «حالت تمرکز» (از طریق پالت فرمان)، فهرست روی یک رکورد خاص پین شده و نوار جستجو جایگزین آن می‌گردد.'],
        ['icon' => 'apartment', 'label' => 'آنبوردینگ', 'hint' => 'زبانه آشنایی با سازمان (آنبوردینگ) — این زبانه از سایر بخش‌ها توسط یک خط جداکننده متمایز گردیده و محتوای آن کاملاً مستقل از اطلاعات نمایه کاربر می‌باشد.'],
    ];
    $header = [
        ['icon' => 'person', 'label' => 'سمت نمایشی', 'hint' => 'در صورتی که فیلد «عنوان نمایشی» در بخش اطلاعات تکمیلی تکمیل شده باشد، این عنوان در سربرگ جایگزینِ سمت سازمانی (Position) خواهد شد.'],
        ['icon' => 'waving_hand', 'label' => 'نام صمیمانه', 'hint' => 'در صورتی که فیلد «نام صمیمانه» در بخش اطلاعات تکمیلی (توسط کارمند یا مدیر سیستم) تکمیل شده باشد، این مقدار در پیام خوش‌آمدگویی صفحه اصلی جایگزین نام کامل می‌گردد؛ در غیر این صورت، سامانه به صورت پیش‌فرض از نام کامل کاربر استفاده خواهد کرد.'],        ['icon' => 'domain', 'label' => 'واحد / بخش / مدت همکاری', 'hint' => 'سربرگ نمایه، نام واحد سازمانی و بخش (در صورت تعیین توسط مدیر سیستم) را نمایش داده و مدت‌زمان همکاری را بر اساس تاریخ شروع به کار (Start Date) و یا تاریخ ایجاد حساب کاربری محاسبه می‌نماید.'],
        ['icon' => 'schedule', 'label' => 'درصد تکمیل', 'hint' => 'درصد تکمیل نمایه تنها بر اساس ' . $d11 . ' فیلد کلیدی (جنسیت، وضعیت تأهل، کدملی، مدرک تحصیلی، رشته، تاریخ تولد، شماره موبایل، نشانی، واحد سازمانی، وضعیت بیمه و تماس اضطراری) محاسبه می‌گردد. در صورت کمتر بودن این مقدار از ' . $d100 . ' درصد، یک نشانگر هشدار قرمز بر روی زبانه «اطلاعات فردی» به نمایش درمی‌آید.'],
        ['icon' => 'cake', 'label' => 'تولد و سالگرد کاری در پنل کاربر', 'hint' => 'در صورتی که «تاریخ تولد» یا «تاریخ استخدام» کاربر دقیقاً با روز جاری (بدون در نظر گرفتن سال) مطابقت داشته باشد، نام کارمند در بخش «مناسبت‌های امروز» در صفحه وضعیت همکاران درج می‌گردد. افزون بر این، در پیام‌رسان داخلی یک نشانگر پویا در کنار نام وی ظاهر شده و در آمار پیام‌رسان (در کنار شاخص‌های «همکاران»، «حاضر» و «خوانده‌نشده»)، شمارشگری با عنوان «رویدادها» افزوده خواهد شد. کارمندان با وضعیت استخدامی «خاتمه‌یافته» و کاربران با سطح دسترسی «مهمان» از این قاعده مستثنی می‌باشند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ نمایه (profile/) چه اطلاعاتی را مشاهده می‌کند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحه نمایه کاربری شامل هفت زبانه مجزا می‌باشد: اطلاعات فردی، اطلاعات تکمیلی، استعدادها (مهارت‌ها)، درباره من، مدارک و اسناد، دسترسی و امنیتی، و آشنایی با سازمان (آنبوردینگ). این راهنما به عنوان مرجعی جامع عمل می‌کند تا مدیران درک دقیقی از رابط کاربری و اطلاعاتِ در دسترسِ کاربران داشته باشند و در صورت بروز پرسش یا ابهام، به درستی آن را بررسی نمایند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های پنل کاربری</p>
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
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">سربرگ نمایه و شاخص درصد تکمیل</p>
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
                در زبانه مهارت‌ها و همچنین دکمه اعلان‌ها در سربرگ، دو راهنمای مختصر (مختص به ارزیابی سطح مهارت‌ها و نشانگرهای هشدار) تعبیه شده است؛ علاوه بر این، راهنمای جامع این ماژول از طریق آیکون «؟» در مجاورت عنوان صفحه قابل‌دسترسی می‌باشد.
            </p>
        </div>
    </div>
</div>
