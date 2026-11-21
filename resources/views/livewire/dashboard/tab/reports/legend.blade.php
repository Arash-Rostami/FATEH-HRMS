@php
    $tabs = [
        ['id' => 'views', 'icon' => 'visibility', 'label' => 'نماها'],
        ['id' => 'sharing', 'icon' => 'share', 'label' => 'اشتراک و انقضا'],
        ['id' => 'timeline', 'icon' => 'view_carousel', 'label' => 'تایم‌لاین'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $viewRows = [
        ['icon' => 'slideshow', 'color' => 'primary', 'label' => 'نمای کارتی', 'text' => 'در رایانه، کارت‌های گزارش با پیمایش افقی نمایش داده می‌شوند. کارت انتخاب‌شده بزرگ‌تر است و تاریخ و نوع فایل کنار آن قرار دارد.'],
        ['icon' => 'view_list', 'color' => 'secondary', 'label' => 'نمای فهرستی', 'text' => 'گزارش‌ها به‌صورت فهرستی فشرده، همراه با تصویر کوچک و عنوان نمایش داده می‌شوند. با دکمهٔ تغییر نما، بین نمایش کارتی و فهرستی جابه‌جا شوید؛ انتخاب شما در نشست جاری حفظ می‌شود.'],
        ['icon' => 'phone_iphone', 'color' => 'tertiary', 'label' => 'نمای موبایل', 'text' => 'در صفحه‌نمایش‌های کوچک، گزارش‌ها به‌صورت خودکار در نمای فهرستی نمایش داده می‌شوند.'],
        ['icon' => 'open_in_full', 'color' => 'primary', 'label' => 'جزئیات و تصویر گزارش', 'text' => 'با انتخاب کارت، پنجرهٔ جزئیات شامل تصویر جلد، متن گزارش و دکمهٔ دانلود باز می‌شود. برای مشاهدهٔ تصویر در اندازهٔ کامل، روی آن کلیک کنید.'],
    ];

    $sharingRows = [
        ['icon' => 'share', 'color' => 'primary', 'label' => 'دسترسی به واحدها', 'text' => 'هر گزارش می‌تواند «عمومی» (در دسترس همه) یا محدود به چند واحد باشد. اگر واحد شما در فهرستِ مخاطبانِ گزارش نباشد، آن گزارش برای شما نمایش داده نمی‌شود.'],
        ['icon' => 'bookmark', 'color' => 'primary', 'label' => 'نشان کردن گزارش', 'text' => 'کنار «دانلود»، دکمهٔ «نشان کردن» را بزنید. برای دیدن گزارش‌هایی که نشان کرده‌اید، «فقط نشان‌شده‌ها» را روشن کنید. نشان‌ها در همین مرورگر می‌مانند.'],
        ['icon' => 'keep', 'color' => 'secondary', 'label' => 'سنجاق‌شده', 'text' => 'گزارش‌های مهم توسط ادمین «سنجاق» می‌شوند و در صدر فهرست (بالای ترتیبِ تاریخ) می‌نشینند و با نشانِ «سنجاق» شناسایی می‌شوند.'],
        ['icon' => 'event_busy', 'color' => 'tertiary', 'label' => 'انقضای خودکار', 'text' => 'اگر برای گزارش تاریخ انقضا تعیین شده باشد، پس از آن تاریخ به‌صورت خودکار از زبانه مخفی می‌شود (بدون حذف).'],
        ['icon' => 'download', 'color' => 'secondary', 'label' => 'دانلودِ ایمن', 'text' => 'دانلود فایل اصلی (PDF/Word) با دکمهٔ دانلود. گزارش‌های غیرفعال، منقضی‌شده یا خارج از دسترسی واحدِ شما هرگز در زبانه ظاهر نمی‌شوند و دانلود آن‌ها با پیام خطا مسدود است.'],
        ['icon' => 'edit', 'color' => 'tertiary', 'label' => 'نشانِ «به‌روز شده»', 'text' => 'گزارش‌هایی که پس از ثبت ویرایش شده‌اند، با نشان «به‌روز شده» مشخص می‌شوند.'],
    ];

    $timelineRows = [
        ['icon' => 'category', 'color' => 'primary', 'label' => 'فیلتر واحد سازمانی', 'text' => 'بالای فهرست، واحدهای دارای گزارش فعال نمایش داده می‌شوند. با انتخاب نام واحد، گزارش‌های همان واحد را ببینید؛ برای لغو فیلتر، دوباره آن را انتخاب کنید.'],
        ['icon' => 'search', 'color' => 'secondary', 'label' => 'جستجو', 'text' => 'عنوان و توضیحات گزارش‌های فعال را جستجو کنید. می‌توانید هم‌زمان از فیلتر واحد نیز استفاده کنید. اگر موردی مطابق جستجو و فیلتر نباشد، پیام نبود نتیجه نمایش داده می‌شود.'],
        ['icon' => 'view_carousel', 'color' => 'tertiary', 'label' => 'ریلِ تایم‌لاین', 'text' => 'در نمای کارتی، یک ریلِ تزئینی با نقطهٔ کناری ظاهر می‌شود؛ با دکمهٔ «مخفی/نمایش تایم‌لاین» پنهان یا ظاهر می‌شود. زیر نقطه، تاریخ گزارش همیشه نمایش داده می‌شود؛ با نگه‌داشتن نشانگر روی کارت، نوع فایل هم بالای نقطه ظاهر می‌شود. کارتِ فعال با نقطهٔ بزرگ‌تر مشخص می‌شود.'],
        ['icon' => 'expand_more', 'color' => 'primary', 'label' => 'بارگذاری بیشتر', 'text' => 'در نمای کارتی، رسیدن به انتهای ریل، گزارش‌های بعدی را خودکار بارگذاری می‌کند (هر بار ۱۰ مورد). در نمای لیستی، دکمهٔ «بارگذاری بیشتر» پایین فهرست است.'],
        ['icon' => 'history', 'color' => 'secondary', 'label' => 'اخیراً باز شده', 'text' => 'در نمای لیستی، بخش تاشوی «اخیراً باز شده» (با همان شکل لینک‌ها و اسناد) تا ۶ گزارش آخرِ بازشده را با تصویر بندانگشتی نشان می‌دهد؛ کلیک روی هر مورد پنجرهٔ جزئیات را دوباره باز می‌کند و «پاک کردن» فهرست را خالی می‌کند. این فهرست فقط در مرورگر خودتان ذخیره می‌شود و در نمای کارتی نمایش داده نمی‌شود.'],
    ];

    $notes = [
        'فقط گزارش‌های «فعال»، «منقضی‌نشده» و در دسترسِ واحدِ شما در این زبانه ظاهر می‌شوند — بقیه هرگز به کاربر نمایش داده نمی‌شوند.',
        'ادمین می‌تواند گزارش را بدون حذف مخفی کند؛ گزارش مخفی در فهرست، جستجو و لینک مستقیم دیده نمی‌شود.',
        'تاریخ کنار هر گزارش، «تاریخ گزارش» (دوره‌ای که گزارش به آن تعلق دارد) است؛ اگر تعریف نشده باشد، تاریخ بارگذاری نشان داده می‌شود.',
        'فایل‌های قابل دانلود فقط PDF یا Word هستند (pdf/doc/docx)؛ سایر فرمت‌ها پذیرفته نمی‌شوند.',
        'اگر گزارش تصویر جلد نداشته باشد، تصویر پیش‌فرض متناسب با نوع فایل نمایش داده می‌شود.',
        'برای دسترسی سریع، از جستجوی سراسری یا میان‌بر «گزارشات» در داشبورد استفاده کنید.',
    ];
@endphp

<div x-data="{ tab: 'views' }">
    <div class="flex flex-wrap gap-y-1 p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
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

    <div x-show="tab === 'views'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">گزارش‌ها به دو نما (کارتی/لیستی) نمایش داده می‌شوند؛ کلیک روی هر کارت جزئیات کامل را باز می‌کند.</p>
        @foreach($viewRows as $row)
            @php
                $chipClasses = match ($row['color']) {
                    'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'sharing'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">دسترسی، سنجاق، انقضا و دانلود — هر کدام قانون خاص خود را دارند.</p>
        @foreach($sharingRows as $row)
            @php
                $chipClasses = match ($row['color']) {
                    'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'timeline'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">فیلترها و ریلِ تایم‌لاین به شما کمک می‌کنند گزارشِ موردنظر را سریع پیدا کنید.</p>
        @foreach($timelineRows as $row)
            @php
                $chipClasses = match ($row['color']) {
                    'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
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
