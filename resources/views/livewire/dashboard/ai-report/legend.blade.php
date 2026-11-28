@php
    $limits ??= $presenter->limits();

    $pills = [
        ['id' => 'generate', 'icon' => 'auto_awesome', 'label' => 'درخواست و فایل‌ها'],
        ['id' => 'versions', 'icon' => 'history', 'label' => 'اصلاح و اشتراک'],
        ['id' => 'views', 'icon' => 'table_rows', 'label' => 'فهرست گزارش‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $generateRows = [
        ['icon' => 'edit', 'color' => 'primary', 'label' => 'چه بنویسم تا گزارش مفیدی بگیرم؟', 'text' => 'موضوع، دورهٔ زمانی و نتیجهٔ موردنیاز را مشخص کنید؛ مثلاً «فروش مهر را از فایل پیوست خلاصه کن و با شهریور مقایسه کن». اطلاعات لازم را در درخواست یا فایل بدهید. درخواست باید حداقل ' . $limits['prompt_min'] . ' حرف باشد. «نوع گزارش» قالب پیشنهادی را تعیین می‌کند؛ «میزان توضیحات» طول تقریبی متن را مشخص می‌کند. انتخاب گروه مخاطب فقط شیوهٔ نوشتن را عوض می‌کند و به کسی دسترسی نمی‌دهد.'],
        ['icon' => 'attach_file', 'color' => 'tertiary', 'label' => 'فایل‌ها چگونه در گزارش استفاده می‌شوند؟', 'text' => 'تا ' . $limits['files'] . ' فایل PDF، تصویر JPG/PNG/WebP، Word با پسوند docx، Excel با پسوند xlsx، CSV یا متن پیوست کنید. هر PDF یا تصویر تا ' . $limits['inline_mb'] . ' مگابایت و همهٔ PDFها و تصویرها با هم تا ' . $limits['inline_total_mb'] . ' مگابایت؛ هر فایل دیگر تا ' . $limits['per_file_mb'] . ' مگابایت و همهٔ پیوست‌ها با هم تا ' . $limits['total_mb'] . ' مگابایت مجاز است. فایل ناخوانا در گزارش استفاده نمی‌شود و نام آن بالای گزارش نمایش داده می‌شود. اگر فقط بخشی از فایل خوانده شده باشد، پیام آن را هم همان‌جا می‌بینید. پیش از استفاده، مطمئن شوید اطلاعات لازم وارد گزارش شده است.'],
        ['icon' => 'counter_1', 'color' => 'secondary', 'label' => 'چه کارهایی سهمیه مصرف می‌کنند؟', 'text' => 'روزانه ' . convertToPersian(config('ai.report.daily_cap')) . ' نوبت دارید. ساخت گزارش، اصلاح کل گزارش، اصلاح یک بخش و تلاش دوباره هرکدام یک نوبت مصرف می‌کنند. ویرایش دستی، برگشت به نسخهٔ قبل، اشتراک‌گذاری، چاپ و کپی سهمیه مصرف نمی‌کنند. در خطای فنی، نوبت مصرف‌شده به سهمیهٔ روز درخواست برمی‌گردد. اگر خروجی بیش از حد بلند شود یا سرویس درخواست را به دلیل محتوا نپذیرد، نوبت برنمی‌گردد؛ پیام خطا این موضوع را مشخص می‌کند. حذف گزارش سهمیه را برنمی‌گرداند.'],
        ['icon' => 'progress_activity', 'color' => 'tertiary', 'label' => 'لازم است منتظر بمانم؟', 'text' => 'هر بار فقط یک درخواست شما اجرا می‌شود. ساخت معمولاً ' . $limits['eta'] . ' ثانیه طول می‌کشد و صفحه خودکار نتیجه را نشان می‌دهد. می‌توانید صفحه را ببندید و بعداً نتیجه را از فهرست باز کنید. بستن صفحه ساخت را متوقف نمی‌کند. متن درخواست و فایل‌ها به سرویس هوش مصنوعی خارج از سامانه فرستاده می‌شوند؛ اطلاعات محرمانه ننویسید یا پیوست نکنید.'],
    ];

    $versionRows = [
        ['icon' => 'history', 'color' => 'primary', 'label' => 'اصلاح کل گزارش یا فقط یک بخش', 'text' => 'در فرم، فقط تغییر موردنظر را بنویسید و «ویرایش هوشمند» را بزنید. برای اصلاح یک بخش، دکمهٔ «اصلاح این بخش با هوش مصنوعی» کنار همان بخش را بزنید؛ نام آن در فرم دیده می‌شود و بقیهٔ گزارش تغییر نمی‌کند. ضربدر کنار نام بخش، انتخاب آن را لغو می‌کند تا اصلاح برای کل گزارش باشد. در اصلاح یک بخش، نوع و میزان توضیحات گزارش تغییر نمی‌کند. فایل‌های قبلی نگه داشته نمی‌شوند؛ اگر اصلاح به آن‌ها نیاز دارد، دوباره پیوست کنید. تا ' . $limits['versions'] . ' نسخهٔ آخر نگه داشته می‌شود و با دکمهٔ شمارهٔ نسخه می‌توانید بدون مصرف سهمیه به نسخهٔ آمادهٔ قبلی برگردید.'],
        ['icon' => 'edit', 'color' => 'secondary', 'label' => 'ویرایش دستی با اصلاح هوش مصنوعی چه فرقی دارد؟', 'text' => 'دکمهٔ مداد، متن و عددهای همان بخش را برای تغییر مستقیم باز می‌کند. ذخیرهٔ آن نسخهٔ تازه‌ای می‌سازد و سهمیه مصرف نمی‌کند. امکان اضافه یا حذف کردن ردیف و خط وجود ندارد؛ خالی گذاشتن آن‌ها با خطا روبه‌رو می‌شود. جمع‌هایی که از ردیف‌های عددی محاسبه شده‌اند خودکار تغییر می‌کنند؛ جمع دستی و مقدارهای دارای واحد را خودتان بررسی کنید. عنوان «مفروضات و محدودیت‌ها» قابل تغییر نیست. ویرایش دستی فقط برای گزارش فعال خودتان و بدون گیرنده ممکن است.'],
        ['icon' => 'forward_to_inbox', 'color' => 'tertiary', 'label' => 'ذخیره و اشتراک‌گذاری دو کار جدا هستند', 'text' => 'گزارش تازه ابتدا پیش‌نویس است. با «ذخیره گزارش» ذخیره‌شده علامت می‌خورد و با کسی به اشتراک گذاشته نمی‌شود. برای دسترسی همکاران، پنجرهٔ «اشتراک‌گذاری با همکاران» را باز کنید، افراد را انتخاب کنید و «ارسال» را بزنید؛ این کار گزارش را هم ذخیره می‌کند. برای قطع دسترسی یک فرد، تیک او را بردارید و دوباره «ارسال» را بزنید. پیوند اعلان تا ' . $limits['link_days'] . ' روز معتبر است؛ پایان اعتبار پیوند، اشتراک را قطع نمی‌کند و گیرنده می‌تواند گزارش را از فهرست «دریافتی» باز کند.'],
        ['icon' => 'lock', 'color' => 'error', 'label' => 'چرا بعضی تغییرها برای گزارش مشترک بسته‌اند؟', 'text' => 'تا وقتی گزارش گیرنده دارد، تغییر عنوان، ویرایش دستی و برگشت به نسخهٔ قبل بسته‌اند تا متن در دسترس همکاران ثابت بماند. اصلاح با هوش مصنوعی، گزارشی تازه و خصوصی برای شما می‌سازد؛ گزارش همکاران عوض نمی‌شود. برای تغییر مستقیم همان گزارش، همهٔ تیک‌ها را در پنجرهٔ اشتراک بردارید و «ارسال» را بزنید؛ دسترسی همهٔ گیرندگان قطع می‌شود. اگر گزارش غیرفعال است، پیش از اصلاح یا اشتراک‌گذاری آن را دوباره فعال کنید.'],
    ];

    $viewRows = [
        ['icon' => 'edit_note', 'color' => 'primary', 'label' => 'گزارش‌ها را از کجا پیدا کنم؟', 'text' => 'در نمای «فشرده»، فرم و گزارش کنار فهرست هستند. با «فعال» و «غیرفعال» می‌توانید فهرست را تغییر دهید. در نمای «کلاسیک»، کنار عنوان صفحه «جدول گزارش‌ها» را بزنید تا گزارش‌ها را جست‌وجو یا فیلتر کنید. برای خواندن گزارش، روی آن بزنید. «نمایش بیشتر» ادامهٔ فهرست را باز می‌کند.'],
        ['icon' => 'table_rows', 'color' => 'secondary', 'label' => 'جستجو و مرتب کردن فهرست', 'text' => 'در جدول، با عنوان جستجو کنید یا گزارش‌های خود، دریافتی، پیش‌نویس، ذخیره‌شده و غیرفعال را جدا ببینید. جستجو و فیلتر بر فهرست کناری اثر ندارند. از ابزارهای بالای جدول می‌توانید ترتیب، ستون‌ها و اندازهٔ ردیف‌ها را تغییر دهید. سنجاق، گزارش را بالای جدول می‌آورد و پالت، رنگ آن را مشخص می‌کند؛ این انتخاب‌ها فقط در همان مرورگر می‌مانند.'],
        ['icon' => 'task_alt', 'color' => 'tertiary', 'label' => 'نشان‌ها چه معنایی دارند؟', 'text' => '«پیش‌نویس» یعنی هنوز «ذخیره گزارش» را نزده‌اید و گزارش را به اشتراک نگذاشته‌اید. «ذخیره‌شده» یعنی ذخیره یا اشتراک انجام شده است؛ این نشان به‌تنهایی به معنی دسترسی همکاران نیست. «جدید» یعنی گزارش دریافتی را هنوز باز نکرده‌اید. «رونوشت خصوصی» یعنی از اصلاح گزارش مشترک، گزارشی تازه برای خودتان ساخته شده است.'],
        ['icon' => 'visibility_off', 'color' => 'error', 'label' => 'غیرفعال کردن با حذف فرق دارد', 'text' => 'با غیرفعال کردن، دسترسی گیرندگان قطع می‌شود، اما گزارش باقی می‌ماند و خودتان آن را در فهرست غیرفعال‌ها می‌بینید. با فعال‌سازی دوباره، دسترسی گیرندگان قبلی برمی‌گردد. حذف، گزارش را برای همیشه از دسترس خارج می‌کند. گزارش در حال ساخت را نمی‌توانید حذف یا غیرفعال کنید.'],
    ];

    $notes = [
        'هوش مصنوعی ممکن است اشتباه کند. قبل از ارسال یا تصمیم‌گیری، عددها و نتیجه‌گیری را با منبع اصلی بررسی کنید. بخش «مفروضات و محدودیت‌ها» نشان می‌دهد چه اطلاعاتی در اختیار نبوده یا تحلیل چه محدودیت‌هایی داشته است.',
        'هشدار «ارقام با یکدیگر همخوانی ندارند» یعنی عدد جمع با مجموع ردیف‌های جدول فرق دارد. در گزارش فعال خودتان، روی هشدار بزنید تا درخواست اصلاح همان جدول آماده شود. متن را بخوانید و «ویرایش هوشمند» را بزنید. هر بار ویرایش، یک نوبت از سهمیه کم می‌کند.',
        'اگر ساخت یا اصلاح ناموفق شد، پیام خطا راه بعدی را توضیح می‌دهد. «تلاش دوباره» همان درخواست را تکرار می‌کند و سهمیه مصرف می‌کند؛ فایل‌های لازم را باید دوباره پیوست کنید. اگر اصلاح یک گزارش آماده ناموفق شده باشد، «برگرداندن نسخهٔ قبلی» آخرین نسخهٔ موفق را بدون مصرف سهمیه برمی‌گرداند.',
        '«استفادهٔ دوباره از این درخواست»، درخواست اولیه را در فرم می‌گذارد تا گزارشی جدا بسازید؛ خودش ساخت را شروع نمی‌کند و سهمیه مصرف نمی‌کند. اگر درخواست اولیه دیگر در تاریخچه نگه داشته نشده باشد یا گزارش از اصلاح یک گزارش مشترک ساخته شده باشد، این دکمه نمایش داده نمی‌شود.',
        'برای چاپ یا گرفتن فایل PDF، دکمهٔ چاپ بالای صفحه را بزنید. در گوشی، «چاپ یا ذخیره PDF» و «رونوشت متن و ارقام» در منوی «⋯» کنار عنوان هستند. برای گرفتن فایل، در پنجرهٔ چاپ «ذخیره به‌صورت PDF» را انتخاب کنید. چاپ شامل خود گزارش است. «رونوشت متن و ارقام» متن و جدول‌های گزارش را کپی می‌کند.',
        'کارت خلاصهٔ بالای گزارش، عنوان، یک عدد مهم، تصمیم و محدودیت‌های گزارش را خلاصه می‌کند. با زدن عنوان کارت باز می‌شود و دکمهٔ رونوشت جدا دارد. این کارت از همان گزارش ساخته می‌شود؛ درخواست تازه‌ای به هوش مصنوعی نمی‌فرستد و در چاپ نمی‌آید.',
        'اگر بخش دانش سازمانی فعال باشد، برای دیدن گزارش‌های سازمان کنار عنوان صفحه «گزارش‌های سازمانی» را بزنید. در گوشی، همین پیوند با عنوان «گزارش‌ها» دیده می‌شود.',
        'متن درخواست شما در همان زبانهٔ مرورگر موقتاً نگه داشته می‌شود تا پس از بارگذاری دوباره برگردد؛ روی نگهداری فایل‌های انتخاب‌شده حساب نکنید. برای فرستادن سریع درخواست می‌توانید Ctrl+Enter یا در مک Cmd+Enter را بزنید.',
    ];

    $chipTones = [
        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
    ];
@endphp

<div x-data="{ tab: 'generate' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($pills as $pill)
            <button
                type="button"
                @click="tab = '{{ $pill['id'] }}'"
                :class="tab === '{{ $pill['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[17px]">{{ $pill['icon'] }}</span>
                {{ $pill['label'] }}
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'generate'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">گزارش ساز هوشمند از درخواست شما — و در صورت پیوست، فایل‌ها — یک گزارش ساختاریافته می‌سازد.</p>
        @foreach($generateRows as $s)
            @php($chipClasses = $chipTones[$s['color']])
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'versions'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">پیش از تغییر یا ارسال گزارش، تفاوت اصلاح، ذخیره و اشتراک‌گذاری را بدانید.</p>
        @foreach($versionRows as $s)
            @php($chipClasses = $chipTones[$s['color']])
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'views'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">دو سبک نمایش، جدول گزارش‌ها و نشان‌های وضعیت.</p>
        @foreach($viewRows as $s)
            @php($chipClasses = $chipTones[$s['color']])
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
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
