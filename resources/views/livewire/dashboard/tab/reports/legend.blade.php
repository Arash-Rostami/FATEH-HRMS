@php
    $tabs = [
        ['id' => 'access', 'icon' => 'visibility', 'label' => 'نما و دسترسی'],
        ['id' => 'timeline', 'icon' => 'view_carousel', 'label' => 'فیلتر و تایم‌لاین'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $accessRows = [
        ['icon' => 'slideshow', 'color' => 'primary', 'label' => 'نمای کارتی', 'text' => 'در دسکتاپ، گزارش‌ها به‌صورت کارت‌های اسکرولِ افقی با snap نمایش داده می‌شوند. کارتِ فعال ۱.۱۵ برابر بزرگ‌تر می‌شود و تاریخ/نوع فایل کنار آن می‌نشیند.'],
        ['icon' => 'view_list', 'color' => 'secondary', 'label' => 'نمای لیستی', 'text' => 'نمای فشرده‌تر با تصویر بندانگشتی کنار عنوان. دکمهٔ grid_view / view_list نما را عوض می‌کند و انتخاب در جلسه ذخیره می‌شود.'],
        ['icon' => 'phone_iphone', 'color' => 'tertiary', 'label' => 'موبایل خودکار', 'text' => 'در صفحه‌های زیر ۷۶۸ پیکسل، سیستم به‌صورت خودکار به نمای لیستی سوییچ می‌کند — کارت در موبایل نمایش داده نمی‌شود.'],
        ['icon' => 'open_in_full', 'color' => 'primary', 'label' => 'جزئیات و لایت‌باکس', 'text' => 'کلیک روی کارت یک پنجرهٔ کشویی (slideOver) با تصویر جلد، بدنهٔ گزارش و دکمهٔ دانلود باز می‌کند. کلیک روی تصویر، لایت‌باکسِ تمام‌صفحه را باز می‌کند.'],
        ['icon' => 'download', 'color' => 'secondary', 'label' => 'دانلود فقط فعال', 'text' => 'دانلود فایل اصلی (PDF/Word) با دکمهٔ دانلود. گزارش‌های غیرفعال هرگز در زبانه ظاهر نمی‌شوند و دانلود آن‌ها با ۴۰۳ مسدود است.'],
        ['icon' => 'edit', 'color' => 'tertiary', 'label' => 'نشانِ «به‌روز شده»', 'text' => 'اگر گزارش پس از انتشار ویرایش شده باشد (updated_at بزرگتر از created_at)، نشانِ «به‌روز شده» روی کارت ظاهر می‌شود.'],
    ];

    $timelineRows = [
        ['icon' => 'category', 'color' => 'primary', 'label' => 'چیپ‌های واحد', 'text' => 'چیپ‌های بالای فهرست از روی گزارش‌های فعالی که واحد دارند ساخته می‌شوند — فقط واحدهایی نشان داده می‌شوند که گزارش دارند. کلیک روی چیپ، همان واحد را فیلتر می‌کند؛ کلیکِ دوباره لغو می‌کند.'],
        ['icon' => 'search', 'color' => 'secondary', 'label' => 'جستجو', 'text' => 'جستجوی هم‌زمان در عنوان و توضیحات گزارش‌های فعال. با فیلتر چیپ ترکیب‌پذیر است. وقتی نتیجه خالی باشد، حالت خالیِ «فیلترشده» نشان داده می‌شود.'],
        ['icon' => 'view_carousel', 'color' => 'tertiary', 'label' => 'ریلِ تایم‌لاین', 'text' => 'در نمای کارتی، یک ریلِ تزئینی با نقطهٔ کناری ظاهر می‌شود؛ با دکمهٔ «مخفی/نمایش تایم‌لاین» پنهان یا ظاهر می‌شود. کارتِ فعال با نقطهٔ بزرگ‌تر مشخص می‌شود.'],
        ['icon' => 'expand_more', 'color' => 'primary', 'label' => 'بارگذاری بیشتر', 'text' => 'در نمای کارتی، رسیدن به انتهای ریل، گزارش‌های بعدی را خودکار بارگذاری می‌کند (هر بار ۱۰ مورد). در نمای لیستی، دکمهٔ «بارگذاری بیشتر» پایین فهرست است.'],
    ];

    $notes = [
        'فقط گزارش‌های «فعال» در این زبانه ظاهر می‌شوند — غیرفعال‌ها هرگز به کاربر نمایش داده نمی‌شوند.',
        'فایل‌های قابل دانلود فقط PDF یا Word هستند (pdf/doc/docx)؛ سایر فرمت‌ها پذیرفته نمی‌شوند.',
        'اگر تصویر جلد تعریف نشده باشد، پیش‌نمایش از روی فرمت فایل ساخته می‌شود (pdf.png / doc.png / report.png) و کش می‌شود — همیشه یک تصویر می‌بینید.',
        'زبانه از طریق پالت دستور یا شورتکاتِ «گزارشات» در داشبورد باز می‌شود و آیکون آن show_chart است.',
    ];
@endphp

<div x-data="{ tab: 'access' }">
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

    <div x-show="tab === 'access'" x-cloak class="space-y-2">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">گزارش‌ها به دو نما (کارتی/لیستی) نمایش داده می‌شوند؛ کلیک روی هر کارت جزئیات کامل را باز می‌کند.</p>
        @foreach($accessRows as $row)
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