@php
    $d10 = convertToPersian('10');
    $d11 = convertToPersian('11');
    $d90 = convertToPersian('90');
    $d100 = convertToPersian('100');

    $tabs = [
        ['id' => 'profile', 'icon' => 'settings_account_box', 'label' => 'پروفایل'],
        ['id' => 'skills', 'icon' => 'workspace_premium', 'label' => 'استعدادها'],
        ['id' => 'docs', 'icon' => 'cloud_upload', 'label' => 'مدارک و دسترسی'],
        ['id' => 'credentials', 'icon' => 'vpn_key', 'label' => 'دسترسی و امنیتی'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $profileRows = [
        ['icon' => 'schedule', 'color' => 'primary', 'label' => 'درصد تکمیل فقط از ' . $d11 . ' فیلد', 'text' => 'درصد تکمیل در هدر، فقط از این فیلدها محاسبه می‌شود: جنسیت، وضعیت تأهل، شماره ملی، مدرک، رشته، تولد، موبایل، آدرس، واحد، بیمه و تماس اضطراری. بقیهٔ فیلدها (مثل رنگ‌های موردعلاقه یا درباره من) روی درصد اثر ندارند. وقتی درصد کمتر از ' . $d100 . ' است، آیکون ⓘ کنار «تکمیل پروفایل» با کلیک یا نگه‌داشتن اشاره‌گر، فهرست دقیق همین فیلدهای ناقص را نشان می‌دهد.'],
        ['icon' => 'person', 'color' => 'tertiary', 'label' => 'عنوان نمایشی', 'text' => 'عنوان نمایشی را مدیر سامانه تعیین می‌کند. اگر برای شما ثبت شده باشد، بالای صفحه به‌جای سمت سازمانی دیده می‌شود. این عنوان از این صفحه قابل ویرایش نیست.'],
        ['icon' => 'waving_hand', 'color' => 'primary', 'label' => 'نام صمیمانه در سلام صفحهٔ اصلی', 'text' => 'اگر در «اطلاعات تکمیلی» فیلد «نام صمیمانه» را پر کنید، صفحهٔ اصلی (خانه) با همان نام به شما سلام می‌کند؛ در غیر این صورت نام کامل شما نشان داده می‌شود.'],
        ['icon' => 'domain', 'color' => 'secondary', 'label' => 'واحد و بخش سازمانی', 'text' => 'مدیر سامانه «واحد» و «بخش» شما را تعیین می‌کند. این اطلاعات بالای صفحه دیده می‌شوند و از زبانهٔ اطلاعات تکمیلی قابل ویرایش نیستند.'],
        ['icon' => 'schedule', 'color' => 'error', 'label' => 'نشان قرمز زبانهٔ اطلاعات', 'text' => 'وقتی درصد تکمیل زیر ' . $d100 . ' است، یک نشان قرمز روی زبانهٔ «اطلاعات فردی» در نوار کناری ظاهر می‌شود؛ با تکمیلِ فیلدهای همان ' . $d11 . ' گانه، نشان از بین می‌رود.'],
    ];

    $skillRows = [
        ['icon' => 'verified_user', 'color' => 'primary', 'label' => 'تعیین سطح مهارت', 'text' => 'سطح مهارت خودکار تعیین می‌شود: با ' . convertToPersian('4') . ' تأیید یا بیشتر، «تأییدشده»؛ با ثبت استفاده در ' . $d90 . ' روز اخیر، «فعال»؛ و در غیر این صورت، «آماده مشارکت».'],
        ['icon' => 'update', 'color' => 'tertiary', 'label' => 'ثبت استفادهٔ اخیر', 'text' => 'با دکمهٔ «استفاده اخیر»، تاریخ امروز به‌عنوان آخرین استفاده ثبت می‌شود. می‌توانید تا ۲۵۵ نویسه دربارهٔ زمینهٔ استفاده بنویسید. این اطلاعات در تعیین سطح مهارت و نمایش آن در فهرست همکاران به کار می‌رود.'],
        ['icon' => 'visibility_off', 'color' => 'secondary', 'label' => 'خصوصی/عمومی', 'text' => 'با دکمهٔ چشم، یک مهارت را خصوصی می‌کنید — از دایرکتوری همکاران پنهان می‌شود ولی در صفحهٔ خودتان می‌ماند.'],
        ['icon' => 'school', 'color' => 'tertiary', 'label' => 'آماده راهنمایی', 'text' => 'با فعال‌کردن این پرچم، به همکاران نشان داده می‌شود که برای این مهارت آمادهٔ راهنمایی هستید.'],
        ['icon' => 'hourglass_top', 'color' => 'gold', 'label' => 'تأیید درخواست مهارت', 'text' => 'مهارت انتخاب‌شده یا پیشنهادی شما ابتدا در انتظار تأیید مدیر سامانه قرار می‌گیرد و پس از تأیید در فهرست همکاران نمایش داده می‌شود. در صورت رد، دلیل آن را می‌بینید و می‌توانید «درخواست مجدد» ثبت کنید.'],
    ];

    $docRows = [
        ['icon' => 'cloud_upload', 'color' => 'primary', 'label' => 'اندازهٔ فایل', 'text' => 'مدارک استاندارد و مدارک سفارشی هر دو حداکثر ' . $d10 . ' مگابایت (PDF/JPG/PNG). فایل بزرگتر از سقف، در مرورگر رد می‌شود.'],
        ['icon' => 'verified_user', 'color' => 'tertiary', 'label' => 'تأیید نهایی قبل از ثبت', 'text' => 'هر فایل بعد از انتخاب، با یک تأییدیهٔ «صحت فایل» ثبت نهایی می‌شود. پس از ثبت نهایی، ویرایش یا حذفِ مدرک از طرف کاربر ممکن نیست.'],
        ['icon' => 'check_circle', 'color' => 'primary', 'label' => 'سه وضعیت مدرک', 'text' => 'بارگذاری‌نشده (آپلود خالی) / در انتظار تأیید (فایل انتخاب‌شده) / تأیید شده (در پرونده ثبت شده). فقط مدارک تأییدشده قابل مشاهده با لینک هستند.'],
        ['icon' => 'vpn_key', 'color' => 'secondary', 'label' => 'حالت تمرکز در دسترسی‌ها', 'text' => 'در زبانهٔ «دسترسی و امنیتی»، وقتی از پالت فرمان یک رکورد را باز می‌کنید، فهرست روی همان یک رکورد پین می‌شود و جستجو جای آن را می‌گیرد؛ پاک کردن جستجو، حالت تمرکز را می‌بندد.'],
    ];

    $credentialRows = [
        ['icon' => 'lock', 'color' => 'error', 'label' => 'مشاهدهٔ اطلاعات ورود', 'text' => 'مدیر سامانه اطلاعات ورود این بخش را برای شما ثبت می‌کند. می‌توانید نام کاربری و رمز را ببینید یا کپی کنید و پیوند سامانه را باز کنید. افزودن، ویرایش یا حذف این اطلاعات در اختیار شما نیست.'],
        ['icon' => 'visibility', 'color' => 'primary', 'label' => 'حفاظت از رمزها', 'text' => 'دکمهٔ چشم، نمایش رمز را روشن یا خاموش می‌کند؛ پنهان‌بودن رمز به‌معنای قفل‌شدن دسترسی نیست. صفحه را بدون مراقبت باز نگذارید. با دکمهٔ کپی می‌توانید رمز را بردارید.'],
        ['icon' => 'search', 'color' => 'tertiary', 'label' => 'جستجوی دسترسی‌ها', 'text' => 'نام سامانه یا نام کاربری را جستجو کنید. با شروع جستجو، نمایش تک‌مورد لغو می‌شود و موارد مطابق عبارت شما نمایش داده می‌شوند.'],
        ['icon' => 'content_copy', 'color' => 'secondary', 'label' => 'کپی با یک کلیک + یادداشت کوتاه', 'text' => 'نام کاربری و رمز هرکدام دکمهٔ کپی دارند. یادداشتِ سامانه، اگر باشد، تا ' . convertToPersian('80') . ' کاراکتر نمایش داده می‌شود.'],
        ['icon' => 'password', 'color' => 'primary', 'label' => 'تغییر رمز عبور شما', 'text' => 'برای تغییر رمز حساب خود، بخش «تغییر رمز عبور» را باز کنید. رمز فعلی را وارد کنید و رمز جدید را دو بار بنویسید. این کار رمز سامانه‌های دیگر را تغییر نمی‌دهد.'],
        ['icon' => 'key_vertical', 'color' => 'tertiary', 'label' => 'کد امضای سری', 'text' => 'در همین زبانه، بخش «کد امضای سری» به‌صورت جمع‌شده قرار دارد. این کد هنگام امضای نامه‌های الکترونیکی در بخش مکاتبات از شما پرسیده می‌شود؛ کد برای هیچ‌کس — حتی ادمین — نمایش داده نمی‌شود. تعیین کد با رمز ورود شما تأیید می‌شود؛ کد نمی‌تواند همان رمز ورودتان باشد و بعد از چند تلاش ناموفق، تا مدتی اجازهٔ تلاش مجدد ندارید (برای هر دو حالت پیام یکسانی می‌بینید). حذف کد فقط با تأییدیه انجام می‌شود و رمز نمی‌خواهد؛ تا تعیین کد تازه، کسی نمی‌تواند به‌جای شما امضا کند. اگر ماژول مکاتبات برای شرکت شما فعال نباشد، این بخش نمایش داده نمی‌شود.'],
    ];


    $notes = [
        'مهارت‌ها روی حسابِ کاربرِ شما ذخیره می‌شوند، نه روی پروفایل — از زبانهٔ استعدادها فقط می‌توانید وضعیت خصوصی/عمومی، آمادگی راهنمایی و استفادهٔ اخیر را تغییر دهید؛ امکان حذف مهارت از همین‌جا وجود ندارد.',
        'ذخیرهٔ «اطلاعات تکمیلی» و «مدارک» نیازمند این است که ابتدا «اطلاعات فردی» تکمیل و ذخیره شده باشد؛ در غیر این صورت یک پیام خطا نمایش داده می‌شود.',
        'نشان «جدید» روی زبانهٔ استعدادها موقتی است و پس از پایان بازهٔ معرفی از بین می‌رود.',
        'زبانهٔ «پشتیبانی و تیکت‌ها» دو بخش دارد: «تیکت‌های من» (سامانهٔ رسمی تیکت) و «پشتیبانی و بازخورد» (درخواست/پیشنهاد/گزارش باگ)؛ هر دو فقط نمایشی‌اند و امکان ثبت مورد جدید از همین‌جا وجود ندارد — برای پاسخ یا پیگیری تیکت از دکمهٔ «مشاهده» وارد سامانهٔ پشتیبانی شوید و برای ثبت درخواست جدید از منوی همبرگری «پشتیبانی و بازخورد» را باز کنید.',
        'زبانهٔ «اعلان‌ها و یادآوری‌ها» دو بخش دارد: «اعلان‌ها» (تاریخچهٔ کامل خوانده‌شده و نخوانده — زنگ بالای صفحه فقط موارد اخیر و نخوانده را نشان می‌دهد، پس تعدادشان یکسان نیست) و «یادآوری‌ها» (همان فهرست زنگ یادآوری بالای صفحه)؛ ساخت یادآوری جدید از همین‌جا ممکن نیست.',
        'زبانهٔ «تنظیمات» همان کلیدهای ظاهر، اعلان‌ها و جدول‌ها است که در نوار بالا و هر جدول جدا پخش‌اند — اینجا فقط یک‌جا جمع شده‌اند و تغییر در هرکدام فوراً همه‌جا اثر می‌کند. بخش «جدول‌ها» برای هر ماژول (اسناد، تیکت‌ها، رزرو، تسک‌بورد) تعداد ستون یا بخش مخفی، وضعیت نوار ابزار و تمام‌صفحه را نشان می‌دهد و بازنشانی جدا دارد؛ «چگالی فشرده» و «حالت خلوت منو» هم بازنشانی جدا دارند و «بازنشانی همه» هر سه را برمی‌گرداند.همهٔ این کلیدها فقط روی همین مرورگر ذخیره می‌شوند، به‌جز «حالت تمرکز» که وضعیتِ شما را برای همکاران هم نشان می‌دهد. دکمهٔ «بازنشانی کامل برنامه» همه‌چیز را پاک می‌کند و پیش از اجرا تأیید می‌گیرد. بخش «اعلان‌ها» در همین زبانه، نام واقعی هر چنل/مخاطب/پروژهٔ بی‌صداشده را نشان می‌دهد و دکمهٔ باصداکردن تکی کنار هر مورد دارد، جدا از دکمهٔ «لغو سکوت همه». کلید «صدای اعلان‌ها» صدای جداگانهٔ تلنگرهای جدید و کارت‌های هشدار را روشن/خاموش می‌کند (جدا از صدای زنگ تایمر و چت؛ حداکثر هر ۱۵ ثانیه یک‌بار). دو کلید «صدای چنل‌ها» و «صدای مخاطبین» همان دکمهٔ «بی‌صدا کردن همه» کنار فهرست گفتگوها هستند: کل صدای ارسال/دریافت آن بخش را خاموش می‌کنند و می‌توانید بعد از آن فقط یک گفتگو را باصدا کنید (در این حالت همان گفتگو در «ردپای شما» با علامت بلندگو دیده می‌شود)؛ وضعیت همهٔ این کلیدها به‌صورت یک کلمه در نوار بالای صفحهٔ اصلی (اعلان | صدا | چت | تمرکز) هم دیده می‌شود؛ این صدا هیچ‌وقت در صفحهٔ چنل و مخاطب پخش نمی‌شود و «لغو سکوت همه» آن را هم روشن می‌کند.',
        'روی آواتار همکاران دو نشانهٔ متفاوت هست: نشانِ وضعیت (در دفتر/دورکار/مأموریت/...، گوشهٔ پایین-راست) را خودِ شخص تنظیم می‌کند، ولی نقطهٔ بالا-چپ خودکار و واقعی است — پر یعنی آنلاین و توخالی یعنی آفلاین؛ به‌محض خروج یا چند دقیقه عدم فعالیت، نقطه توخالی می‌شود، حتی اگر وضعیتِ انتخابی هنوز «در دفتر» باشد.',
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

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>