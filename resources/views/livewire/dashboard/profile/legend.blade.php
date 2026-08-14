@php
    $d2 = convertToPersian('2');
    $d5 = convertToPersian('5');
    $d11 = convertToPersian('11');
    $d90 = convertToPersian('90');
    $d100 = convertToPersian('100');

    $tabs = [
        ['id' => 'profile', 'icon' => 'settings_account_box', 'label' => 'پروفایل'],
        ['id' => 'skills', 'icon' => 'workspace_premium', 'label' => 'استعدادها'],
        ['id' => 'docs', 'icon' => 'cloud_upload', 'label' => 'مدارک و دسترسی'],
        ['id' => 'credentials', 'icon' => 'vpn_key', 'label' => 'دسترسی و امنیتی'],
        ['id' => 'onboarding', 'icon' => 'apartment', 'label' => 'آنبوردینگ'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $profileRows = [
        ['icon' => 'schedule', 'color' => 'primary', 'label' => 'درصد تکمیل فقط از ' . $d11 . ' فیلد', 'text' => 'درصد تکمیل در هدر، فقط از این فیلدها محاسبه می‌شود: جنسیت، وضعیت تأهل، شماره ملی، مدرک، رشته، تولد، موبایل، آدرس، واحد، بیمه و تماس اضطراری. بقیهٔ فیلدها (مثل رنگ‌های موردعلاقه یا درباره من) روی درصد اثر ندارند.'],
        ['icon' => 'person', 'color' => 'tertiary', 'label' => 'عنوان نمایشی جای سمت', 'text' => 'اگر در «اطلاعات تکمیلی» فیلد «عنوان نمایشی» را پر کنید، در هدرِ صفحه به‌جای سمت سازمانی (کارمند/کارشناس/مدیر…) نمایش داده می‌شود.'],
        ['icon' => 'domain', 'color' => 'secondary', 'label' => 'واحد و بخش فقط ادمینی', 'text' => '«واحد» و «بخش» در اطلاعات تکمیلی فقط توسط ادمین تنظیم می‌شوند؛ شما آن‌ها را در هدرِ صفحه می‌بینید ولی در زبانهٔ اطلاعات تکمیلی نمی‌توانید ویرایش کنید.'],
        ['icon' => 'schedule', 'color' => 'error', 'label' => 'نشان قرمز زبانهٔ اطلاعات', 'text' => 'وقتی درصد تکمیل زیر ' . $d100 . ' است، یک نشان قرمز روی زبانهٔ «اطلاعات فردی» در نوار کناری ظاهر می‌شود؛ با تکمیلِ فیلدهای همان ' . $d11 . ' گانه، نشان از بین می‌رود.'],
    ];

    $skillRows = [
        ['icon' => 'verified_user', 'color' => 'primary', 'label' => 'سطح مهارت خودکار است', 'text' => 'سطح هر مهارت (Endorsed / Active / Unused) خودکار تعیین می‌شود: Endorsed با ' . convertToPersian('4') . ' تأیید یا بیشتر، Active با ثبت «استفاده اخیر» در ' . $d90 . ' روز اخیر، در غیر این صورت Unused.'],
        ['icon' => 'update', 'color' => 'tertiary', 'label' => 'ثبت استفاده اخیر', 'text' => 'دکمهٔ «استفاده اخیر» تاریخ امروز را ثبت می‌کند و می‌توانید زمینهٔ استفاده را (اختیاری، تا ۲۵۵ کاراکتر) بنویسید. این کار مهارت را Active می‌کند و در دایرکتوری همکاران به‌روز می‌ماند.'],
        ['icon' => 'visibility_off', 'color' => 'secondary', 'label' => 'خصوصی/عمومی', 'text' => 'با دکمهٔ چشم، یک مهارت را خصوصی می‌کنید — از دایرکتوری همکاران پنهان می‌شود ولی در صفحهٔ خودتان می‌ماند.'],
        ['icon' => 'school', 'color' => 'tertiary', 'label' => 'آماده راهنمایی', 'text' => 'با فعال‌کردن این پرچم، به همکاران نشان داده می‌شود که برای این مهارت آمادهٔ راهنمایی هستید.'],
        ['icon' => 'hourglass_top', 'color' => 'gold', 'label' => 'درخواست → تأیید ادمین', 'text' => 'مهارتِ انتخاب‌شده از فهرست یا پیشنهادِ جدید، ابتدا در وضعیت Pending ثبت می‌شود و پس از تأیید ادمین در دایرکتوری ظاهر می‌شود. رد شدن با دلیل نشان داده می‌شود و «درخواست مجدد» دارد.'],
    ];

    $docRows = [
        ['icon' => 'cloud_upload', 'color' => 'primary', 'label' => 'اندازهٔ فایل', 'text' => 'مدارک استاندارد حداکثر ' . $d2 . ' مگابایت و مدارک سفارشی حداکثر ' . $d5 . ' مگابایت (PDF/JPG/PNG). فایل بزرگتر از سقف، در مرورگر رد می‌شود.'],
        ['icon' => 'verified_user', 'color' => 'tertiary', 'label' => 'تأیید نهایی قبل از ثبت', 'text' => 'هر فایل بعد از انتخاب، با یک تأییدیهٔ «صحت فایل» ثبت نهایی می‌شود. پس از ثبت نهایی، ویرایش یا حذفِ مدرک از طرف کاربر ممکن نیست.'],
        ['icon' => 'check_circle', 'color' => 'primary', 'label' => 'سه وضعیت مدرک', 'text' => 'بارگذاری‌نشده (آپلود خالی) / در انتظار تأیید (فایل انتخاب‌شده) / تأیید شده (در پرونده ثبت شده). فقط مدارک تأییدشده قابل مشاهده با لینک هستند.'],
        ['icon' => 'vpn_key', 'color' => 'secondary', 'label' => 'حالت تمرکز در دسترسی‌ها', 'text' => 'در زبانهٔ «دسترسی و امنیتی»، وقتی از پالت فرمان یک رکورد را باز می‌کنید، فهرست روی همان یک رکورد پین می‌شود و جستجو جای آن را می‌گیرد؛ پاک کردن جستجو، حالت تمرکز را می‌بندد.'],
    ];

    $credentialRows = [
        ['icon' => 'lock', 'color' => 'error', 'label' => 'فقط خواندنی، ادمین‌تعریف‌شده', 'text' => 'سامانه‌ها و رمزهای این زبانه توسط ادمین برای شما تعریف می‌شوند؛ شما فقط آن‌ها را می‌بینید، رمز را ظاهر یا کپی می‌کنید و با لینک وارد سامانه می‌شوید — افزودن، ویرایش یا حذف از طرف شما ممکن نیست.'],
        ['icon' => 'visibility', 'color' => 'primary', 'label' => 'رمز رمزنگاری‌شده در پایگاه‌داده', 'text' => 'رمز هر سامانه در پایگاه‌داده رمزنگاری (Crypt) ذخیره می‌شود و فقط هنگام نمایش با دکمهٔ چشم باز می‌گردد؛ دکمهٔ کپی، رمزِ بازشده را در کلیپ‌بورد می‌گذارد.'],
        ['icon' => 'search', 'color' => 'tertiary', 'label' => 'جستجو = خروج از حالت تمرکز', 'text' => 'جستجو روی نام سامانه یا نام کاربری اجرا می‌شود (با تأخیر ' . convertToPersian('300') . ' میلی‌ثانیه). هر بار که در جعبهٔ جستجو تایپ کنید، حالت تمرکز (پین روی یک رکورد) به‌صورت خودکار بسته می‌شود.'],
        ['icon' => 'content_copy', 'color' => 'secondary', 'label' => 'کپی با یک کلیک + یادداشت کوتاه', 'text' => 'نام کاربری و رمز هرکدام دکمهٔ کپی دارند. یادداشتِ سامانه، اگر باشد، تا ' . convertToPersian('80') . ' کاراکتر نمایش داده می‌شود.'],
    ];

    $onboardingRows = [
        ['icon' => 'apartment', 'color' => 'primary', 'label' => 'فقط خواندنی، محتوای ادمین', 'text' => 'تمام بخش‌های آنبوردینگ (خوش‌آمدگویی، ویدیوها، ماموریت، چشم‌انداز، راهنماها، برنامه روز اول و نکات تکمیلی) توسط مدیر سیستم نوشته می‌شود؛ شما فقط آن‌ها را می‌بینید و ویرایش از طرف شما ممکن نیست.'],
        ['icon' => 'person', 'color' => 'tertiary', 'label' => 'نسخهٔ اختصاصی جای همه‌گیر', 'text' => 'اگر مدیر یک آنبوردینگ اختصاصی برای شما تعریف کرده باشد، آن جای نسخهٔ عمومی شرکت نمایش داده می‌شود؛ در غیر این صورت نسخهٔ مشترک (بدون کاربر اختصاصی) می‌آید.'],
        ['icon' => 'menu_book', 'color' => 'secondary', 'label' => 'PDF باز، بقیه دانلود', 'text' => 'در بخش «راهنماها و مستندات»، فایل‌های PDF در تب جدید باز می‌شوند ولی سایر فرمت‌ها مستقیم دانلود می‌شوند.'],
        ['icon' => 'campaign', 'color' => 'gold', 'label' => 'نکات سفارشی با نشان نقطه‌چین', 'text' => 'در بخش «اطلاعات تکمیلی»، نکاتِ ازپیش‌تعریف‌شده با حاشیهٔ معمولی می‌آیند ولی نکاتِ سفارشی (که مدیر با کلید دلخواه اضافه کرده) نشانِ «سفارشی» و حاشیهٔ نقطه‌چین دارند.'],
    ];

    $notes = [
        'مهارت‌ها روی حسابِ کاربرِ شما ذخیره می‌شوند، نه روی پروفایل — حذفِ یک مهارت از زبانهٔ استعدادها آن را از SkillUser هم پاک می‌کند.',
        'ذخیرهٔ «اطلاعات تکمیلی» و «مدارک» نیازمند این است که ابتدا «اطلاعات فردی» تکمیل و ذخیره شده باشد؛ در غیر این صورت یک پیام خطا نمایش داده می‌شود.',
        'نشان «جدید» روی زبانهٔ استعدادها موقتی است و پس از پایان بازهٔ معرفی از بین می‌رود.',
    ];

    $chipClasses = fn(string $color): string => match ($color) {
        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
        'gold' => 'bg-[var(--tool-gold-bg)] text-[var(--tool-gold-color)]',
    };
@endphp

<div x-data="{ tab: 'profile' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $tab)
            <button
                type="button"
                @click="tab = '{{ $tab['id'] }}'"
                :class="tab === '{{ $tab['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex flex-col items-center justify-center gap-0.5 px-1.5 py-2 rounded-xl text-[11px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[18px]">{{ $tab['icon'] }}</span>
                <span class="leading-tight text-center">{{ $tab['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'profile'" x-cloak class="space-y-2">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">نکاتِ غیربدیهی دربارهٔ هدر و زبانهٔ اطلاعات فردی.</p>
        @foreach($profileRows as $row)
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($row['color']) }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'skills'" x-cloak class="space-y-2">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">سطح هر مهارت خودکار تعیین می‌شود؛ شما فقط استفاده و حریم خصوصی را کنترل می‌کنید.</p>
        @foreach($skillRows as $row)
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($row['color']) }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'docs'" x-cloak class="space-y-2">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">مدارک پس از ثبت نهایی قفل می‌شوند و دسترسی‌ها حالت تمرکز دارند.</p>
        @foreach($docRows as $row)
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($row['color']) }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'credentials'" x-cloak class="space-y-2">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">سامانه‌ها و رمزها فقط خواندنی هستند؛ رمزها رمزنگاری‌شده ذخیره می‌شوند.</p>
        @foreach($credentialRows as $row)
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($row['color']) }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'onboarding'" x-cloak class="space-y-2">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">آنبوردینگ فقط خواندنی است؛ نسخهٔ اختصاصی شما جای نسخهٔ عمومی می‌نشیند.</p>
        @foreach($onboardingRows as $row)
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($row['color']) }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>