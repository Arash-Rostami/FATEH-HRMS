@php
    $tabs = [
        ['id' => 'due', 'icon' => 'schedule', 'label' => 'سررسید و تکرار'],
        ['id' => 'channels', 'icon' => 'campaign', 'label' => 'روش‌های اطلاع‌رسانی'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $dueRows = [
        ['icon' => 'today', 'color' => 'error', 'label' => 'پیدا کردن یادآوری‌های دیرکرده', 'text' => '«دیرکرد» یادآوری‌های انجام‌نشدهٔ روزهای قبل را نشان می‌دهد. اگر ساعت یک یادآوری امروز گذشته باشد، آن را در «امروز» با نشان «دیرکرد امروز» می‌بینید. هفته از شنبه شروع می‌شود.'],
        ['icon' => 'snooze', 'color' => 'secondary', 'label' => 'عقب انداختن اعلان', 'text' => 'با خاموش کردن موقت اعلان، سررسید همان می‌ماند و فقط زمان دریافت اعلان عقب می‌افتد. تا آن زمان، یادآوری را در «اعلان خاموش» پیدا می‌کنید.'],
        ['icon' => 'event_repeat', 'color' => 'tertiary', 'label' => 'ایجاد یادآوری بعدی', 'text' => 'با ثبت «انجام شد»، یادآوری بعدی بر اساس برنامهٔ تکرار ساخته می‌شود. اگر موعدهای قبلی گذشته باشند، اولین نوبت آینده انتخاب می‌شود. تکرار ماهانه بر پایهٔ ماه شمسی است؛ روز ' . convertToPersian('31') . ' در ماه ' . convertToPersian('30') . ' روزه، به آخر ماه منتقل می‌شود.'],
        ['icon' => 'block', 'color' => 'primary', 'label' => 'توقف تکرار', 'text' => 'با انتخاب «توقف تکرار»، یادآوری جاری باقی می‌ماند، اما پس از انجام آن یادآوری دیگری ساخته نمی‌شود.'],
    ];

    $channelRows = [
        ['icon' => 'mail', 'color' => 'primary', 'label' => 'ارسال یک‌بارهٔ ایمیل و پیامک', 'text' => 'ایمیل و پیامک پس از رسیدن موعد یادآوری، فقط یک‌بار ارسال می‌شوند. تغییر تاریخ یا ساعت، ارسال دوباره را فعال می‌کند؛ تغییر عنوان یا روش اطلاع‌رسانی چنین اثری ندارد. پیامک به شمارهٔ موبایل ثبت‌شده نیاز دارد. روش‌هایی که سازمان غیرفعال کرده است نمایش داده نمی‌شوند.'],
        ['icon' => 'push_pin', 'color' => 'secondary', 'label' => 'بستن پیام ثابت صفحه', 'text' => 'وقتی پیام ثابت صفحه را ببندید، یادآوری «انجام‌شده» ثبت می‌شود. برای یادآوری تکراری، نوبت بعدی هم ساخته می‌شود. اگر کار هنوز باقی است، وضعیت آن را از فهرست یادآوری‌ها پیگیری کنید.'],
        ['icon' => 'badge', 'color' => 'tertiary', 'label' => 'نشانگر منو فقط برای مورد متصل', 'text' => 'برای فعال کردن «نشانگر منو»، یادآوری را از داخل وظیفه، تیکت، پروژه، سند، رزرو یا رسانه بسازید. وقتی از زنگ نوار بالا یادآوری می‌سازید، می‌توانید «پیام اعلانات» یا «پیام ثابت صفحه» را انتخاب کنید.'],
        ['icon' => 'schedule', 'color' => 'error', 'label' => 'زمان فرستادن اعلان', 'text' => 'اعلان از زمان سررسید به بعد نمایش داده یا فرستاده می‌شود. اگر موردی که یادآوری به آن وصل است به سطل زباله برود، اعلان‌هایش تا زمان بازیابی متوقف می‌شوند.'],
    ];

    $notes = [
        'زنگ کنار هر مورد، یادآوری‌های همان مورد را نشان می‌دهد. اگر یادآوری انجام‌نشده‌ای وجود داشته باشد، برای ویرایش باز می‌شود. برای دیدن همهٔ یادآوری‌ها، زنگ نوار بالا را بزنید.',
        'وقتی یادآوری را از داخل یک وظیفه، تیکت یا رزرو می‌سازید، فرم مهلت یا زمان شروع همان مورد را پیشنهاد می‌دهد. برای پروژه و سند، موعد را خودتان انتخاب کنید.',
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
        'due' => ['intro' => 'سررسید زمانی است که برای یادآوری تعیین می‌کنید. می‌توانید اعلان را موقتاً عقب بیندازید یا برای یادآوری برنامهٔ تکرار بگذارید.', 'rows' => $dueRows],
        'channels' => ['intro' => 'برای هر یادآوری می‌توانید یک یا چند روش اطلاع‌رسانی انتخاب کنید.', 'rows' => $channelRows],
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
