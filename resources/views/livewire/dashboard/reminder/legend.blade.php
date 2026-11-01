@php
    $tabs = [
        ['id' => 'due', 'icon' => 'schedule', 'label' => 'سررسید و تکرار'],
        ['id' => 'channels', 'icon' => 'campaign', 'label' => 'کانال‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $dueRows = [
        ['icon' => 'today', 'color' => 'error', 'label' => 'دیرکرد = قبل از امروز', 'text' => 'فیلتر «دیرکرد» فقط سررسیدهای پیش از آغاز امروز را نشان می‌دهد؛ یادآوری‌ای که ساعتش امروز گذشته هنوز زیر «امروز» می‌ماند، هرچند نشان سررسیدش «دیرکرد امروز» نوشته می‌شود. «این هفته» از شنبه شروع می‌شود.'],
        ['icon' => 'snooze', 'color' => 'secondary', 'label' => 'خاموشی موقت، نه جابه‌جایی', 'text' => 'خاموش‌کردن فقط اعلان را عقب می‌اندازد و سررسید تغییر نمی‌کند: یادآوری از فهرست‌های فعال به فیلتر «اعلان خاموش» می‌رود و پس از پایان خاموشی دوباره اعلان می‌دهد. گزینه‌ها: ' . convertToPersian('1') . ' ساعت، امشب (' . convertToPersian('18:00') . '، فقط پیش از ' . convertToPersian('18') . ')، فردا (' . convertToPersian('09:00') . ')، هفته بعد، یا ' . convertToPersian('1') . ' تا ' . convertToPersian('12') . ' ساعت دلخواه.'],
        ['icon' => 'event_repeat', 'color' => 'tertiary', 'label' => 'تکرار با انجام‌شدن ساخته می‌شود', 'text' => 'هر لحظه فقط یک نمونهٔ زنده وجود دارد؛ نمونهٔ بعدی تنها وقتی ساخته می‌شود که این یکی «انجام شد» بخورد، و از سررسید اصلی جلو می‌رود تا به آینده برسد، پس دیر انجام‌دادن هرگز نمونهٔ دیرکردِ تازه نمی‌سازد. تکرار ماهانه بر اساس ماه شمسی است و روز ' . convertToPersian('31') . ' در ماه ' . convertToPersian('30') . ' روزه به آخر همان ماه می‌رود.'],
        ['icon' => 'block', 'color' => 'primary', 'label' => 'توقف تکرار', 'text' => 'دکمهٔ «توقف تکرار» فقط روی یادآوری تکرارشونده دیده می‌شود و همین یادآوری را یک‌باره می‌کند؛ نمونه‌ای از قبل ساخته نشده که بخواهد پاک شود.'],
    ];

    $channelRows = [
        ['icon' => 'mail', 'color' => 'primary', 'label' => 'ایمیل و پیامک: یک‌بار، سر وقت', 'text' => 'ایمیل و پیامک دقیقاً در ساعت سررسید و فقط یک‌بار فرستاده می‌شوند. تغییر ساعت یا تاریخ سررسید دوباره ارسال را فعال می‌کند، ولی ویرایش عنوان یا کانال‌ها نه. گزینهٔ پیامک بدون شمارهٔ موبایل ثبت‌شده غیرفعال است و اگر سازمان پیامک را خاموش کرده باشد اصلاً نمایش داده نمی‌شود.'],
        ['icon' => 'push_pin', 'color' => 'secondary', 'label' => 'بستن «پیام ثابت صفحه» = انجام‌شدن', 'text' => 'بستن این پیام روی صفحه، خودِ یادآوری را «انجام‌شده» می‌کند (و اگر تکرارشونده باشد نمونهٔ بعدی را می‌سازد)؛ پس برای فقط‌دیدن، آن را نبندید و از فهرست یادآوری‌ها اقدام کنید.'],
        ['icon' => 'badge', 'color' => 'tertiary', 'label' => 'نشانگر منو فقط برای رکورد متصل', 'text' => 'نقطهٔ سررسید روی منو فقط به یادآوری‌ای که به یک رکورد (وظیفه، تیکت، پروژه، سند، رزرو) وصل است داده می‌شود؛ در فرم ساخته‌شده از نوار بالا این گزینه خاموش است. «پیام اعلانات» (زنگ) و «پیام ثابت صفحه» برای همه کار می‌کنند.'],
        ['icon' => 'schedule', 'color' => 'error', 'label' => 'همه منتظر ساعت سررسیدند', 'text' => 'هیچ‌کدام از کانال‌ها زودتر از ساعت سررسید روشن نمی‌شوند؛ ذخیرهٔ یادآوری ساعت ' . convertToPersian('18:00') . ' در صبح، نقطه یا پیامی نمی‌سازد. اگر رکورد متصل به سطل زباله برود، اعلان‌ها متوقف می‌شوند و با بازیابی رکورد دوباره از سر گرفته می‌شوند.'],
    ];

    $notes = [
        'اگر از آیکن زنگ یک رکورد وارد شوید و برایش یادآوریِ انجام‌نشده (حتی خاموش‌شده) داشته باشید، همان یادآوری برای ویرایش باز می‌شود نه فرم خالی؛ فقط وقتی همه انجام‌شده باشند فرم تازه می‌آید. آیکن زنگ هر رکورد فقط یادآوری‌های همان رکورد را فهرست می‌کند، در حالی که زنگ نوار بالا همهٔ یادآوری‌های شما را نشان می‌دهد.',
        'در ساخت یادآوری از روی وظیفه، تیکت یا رزرو، تاریخ سررسید از مهلت یا زمان شروع همان رکورد پیش‌پر می‌شود؛ پروژه و سند تاریخ پیش‌فرض ندارند.',
        'زیر فیلد عنوان تا ' . convertToPersian('8') . ' عنوانِ پراستفادهٔ قبلی شما پیشنهاد می‌شود.',
        'یادآوری‌ها در تقویم هم دیده می‌شوند، حتی اگر اعلانشان خاموش شده باشد، ولی از آنجا قابل ویرایش نیستند.',
        'یادآوری‌های انجام‌شده پس از ' . convertToPersian('90') . ' روز خودکار پاک می‌شوند؛ یادآوری‌های انجام‌نشده هرگز.',
    ];

    $chipClasses = fn(string $color): string => match ($color) {
        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
    };

    $sections = [
        'due' => ['intro' => 'سررسید فقط زمان اعلان را تعیین می‌کند؛ خاموشی و تکرار قواعد خودشان را دارند.', 'rows' => $dueRows],
        'channels' => ['intro' => 'هر یادآوری می‌تواند همزمان از چند کانال خبر بدهد؛ رفتار هرکدام کمی فرق دارد.', 'rows' => $channelRows],
    ];
@endphp

<div x-data="{ tab: 'due' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $tab)
            <button
                type="button"
                @click="tab = '{{ $tab['id'] }}'"
                :class="tab === '{{ $tab['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[18px]">{{ $tab['icon'] }}</span>
                <span class="leading-tight">{{ $tab['label'] }}</span>
            </button>
        @endforeach
    </div>

    @foreach($sections as $id => $section)
        <div x-show="tab === '{{ $id }}'" x-cloak class="space-y-2">
            <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">{{ $section['intro'] }}</p>
            @foreach($section['rows'] as $row)
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
    @endforeach

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>
