@php
    $aiOn = thsAiOn();

    $pills = [
        ['id' => 'roles', 'icon' => 'group', 'label' => 'نقش‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $rows = [
        'requester' => [
            'note' => 'شما این تیکت را ثبت کرده‌اید.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'فهرست شما', 'text' => 'در «تاریخچه»، تیکت‌هایی را که خودتان ثبت کرده‌اید می‌بینید.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'کارهای شما', 'text' => 'تا پیش از بسته‌شدن، از «پاسخ و پیگیری» پیام بفرستید یا «لغو درخواست» را بزنید. لغو، تیکت را برای همیشه می‌بندد. پس از پایان رسیدگی، از زبانهٔ «ارزیابی» امتیاز رضایت بدهید؛ تیکت لغوشده امتیاز نمی‌خواهد.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'با پاسخ تازه، تعیین مسئول یا بسته‌شدن تیکت باخبر می‌شوید. نشان کارتابل برای تیکتِ منتظر امتیاز تا ثبت رضایت شما روشن می‌ماند.'],
        ],
        'head' => [
            'note' => 'شما مدیر واحد دریافت‌کنندهٔ تیکت هستید.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'فهرست شما', 'text' => 'در «تاریخچه»، «نیاز به اقدام من» تیکت‌های باز واحد و تیکت‌های در حال رسیدگی شما را نشان می‌دهد. با «تیکت‌های واحد من» همهٔ تیکت‌های واحد را ببینید و بر اساس وضعیت جدا کنید. افراد هم‌رتبه، همگی مدیر واحد محسوب می‌شوند.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'کارهای شما', 'text' => 'در «پاسخ و پیگیری»، مسئول رسیدگی تعیین کنید، پیام بدهید یا تیکت را ببندید. برای ارجاع به واحد دیگر یا بازگشت به صف، دلیل بنویسید؛ درخواست‌دهنده هم آن را می‌بیند. ثبت اثربخشی اختیاری است.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'نشان کارتابل برای تیکت بازِ بدون مسئول در واحد یا تیکتی که خودتان مسئول آن هستید روشن می‌ماند. اگر درخواست‌دهنده یا مسئول باشید، پاسخ تازه در زنگوله هم اعلام می‌شود.'],
        ],
        'assignee' => [
            'note' => 'رسیدگی به این تیکت به شما سپرده شده است.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'فهرست شما', 'text' => 'از «تاریخچه» و «نیاز به اقدام من»، تیکت‌های در حال رسیدگی خود را پیدا کنید.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'کارهای شما', 'text' => 'در «پاسخ و پیگیری» پیام بدهید و پس از پایان رسیدگی، تیکت را ببندید. ارسال پیام به‌تنهایی تیکت را نمی‌بندد. ثبت اثربخشی اختیاری است. اگر نمی‌توانید ادامه دهید، دلیل را در کادر پاسخ بنویسید و «بازگشت به صف» را بزنید تا مسئول دیگری تعیین شود.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'تا وقتی تیکتِ در حال رسیدگی شما بسته نشده، نشان کارتابل روشن است. با پاسخ تازهٔ درخواست‌دهنده، زنگوله هم فعال می‌شود.'],
        ],
    ];

    $noteGroups = [
        'display' => [
            'label' => 'پیدا کردن و دیدن تیکت',
            'icon' => 'visibility',
            'notes' => [
                'شناسهٔ تیکت شامل کد واحد، سال و ماه میلادی و شماره است؛ مانند TN-۲۶۰۸-۰۰۰۱. کد پشتیبانی عمومی T است. دکمهٔ کپی کنار شناسه، آن را برای استفاده در پیام یا پیگیری کپی می‌کند.',
                'در «جزئیات»، موضوع، شرح و فایل‌ها را بخوانید. پیام بالای صفحه می‌گوید مرحلهٔ بعد چیست و چه کسی باید اقدام کند. نوار وضعیت هم مراحل ثبت، رسیدگی و بسته‌شدن را نشان می‌دهد.',
                'دکمهٔ «توضیحات» کنار زبانه‌ها، مشخصات تیکت و همهٔ پیوست‌ها را باز می‌کند؛ مسئول، واحد مقصد، مهلت، تاریخ‌ها و امتیازها در همین بخش هستند.',
                'اگر مهلت تعیین شده باشد، فهرست زمان باقی‌مانده یا تأخیر را نشان می‌دهد. پس از بسته‌شدن، مشخص می‌شود رسیدگی پیش از مهلت تمام شده یا با تأخیر.',
                'عبارت «معمولاً حدود ... روز» بر اساس تیکت‌های قبلی است؛ زمان قطعی پایان رسیدگی نیست. اگر سابقه کافی نباشد، این برآورد نمایش داده نمی‌شود.',
                'در موبایل، پنجرهٔ تیکت از پایین باز می‌شود و با کشیدن دستگیره به پایین بسته می‌شود. در رایانه، وقتی در حال تایپ نیستید، با کلید M اندازهٔ پنجره را تغییر دهید و با Esc بخش بازشده را ببندید.',
            ],
        ],
        'workflow' => [
            'label' => 'رسیدگی و پایان کار',
            'icon' => 'bolt',
            'notes' => [
                'برای شروع رسیدگی، یک عضو واحد را انتخاب کنید و «تعیین مسئول» را بزنید. انتخاب خودتان یعنی رسیدگی را به عهده می‌گیرید. وضعیت تیکت به «در حال بررسی» تغییر می‌کند.',
                '«بازگشت به صف» مسئول فعلی را برمی‌دارد و تیکت را به صف همین واحد برمی‌گرداند. «ارجاع به واحد دیگر» تیکت را به صف واحد جدید می‌فرستد و مسئول فعلی را برمی‌دارد. برای هر دو، ابتدا دلیل را در کادر پاسخ بنویسید و سپس دکمهٔ مربوط را بزنید؛ دلیل برای درخواست‌دهنده هم دیده می‌شود.',
                'واحدهایی که مدیر دارند یا «تخصیص آزاد تیکت» برایشان فعال است می‌توانند تیکت دریافت کنند. در تخصیص آزاد، اعضای فعال واحد، به‌جز درخواست‌دهنده، می‌توانند تیکت باز را به خود یا همکارشان بسپارند یا ارجاع دهند. پس از تعیین مسئول، دسترسی عمومی اعضای واحد به آن تیکت پایان می‌یابد؛ دسترسی مدیر، درخواست‌دهنده و مسئول باقی می‌ماند.',
                'ارسال پاسخ، تیکت را نمی‌بندد. با «بستن تیکت»، رسیدگی برای همیشه پایان می‌یابد؛ دیگر نمی‌توان پیام داد، مسئول را عوض کرد یا تیکت را دوباره باز کرد. اگر خودتان درخواست‌دهنده هستید و دیگر نیازی به رسیدگی ندارید، «لغو درخواست» را بزنید.',
                '«اثربخشی» یعنی نتیجهٔ رسیدگی چقدر مفید بوده؛ ثبت آن اختیاری است و با انتخاب گزینه، فوراً ذخیره می‌شود. «رضایت» امتیازی جداست که درخواست‌دهنده پس از بسته‌شدن به نحوهٔ رسیدگی می‌دهد.',
                'با تعیین مسئول، یک وظیفهٔ پیگیری هم در «وظیفه‌ها» ساخته می‌شود. گفتگو را در خود تیکت ادامه دهید. با برداشتن مسئول یا ارجاع، وظیفه کنار گذاشته می‌شود و با بستن تیکت، انجام‌شده می‌شود.',
                'در کادر پاسخ، متن بنویسید یا حداکثر ۳ فایل پیوست کنید. Ctrl+Enter پاسخ را می‌فرستد و Shift+Enter خط جدید می‌سازد. پاسخ‌های سریع فقط متن پیشنهادی هستند؛ پیش از ارسال می‌توانید آن‌ها را تغییر دهید.',
                ...($aiOn ? ['«نوشتن هوشمند» پس از انتخاب حوزه، هم موضوع و هم شرح را پیشنهاد می‌دهد. پیش از استفاده، متن را بخوانید و جاهای خالی «____» را پر یا حذف کنید. «پذیرش» فقط پیشنهاد را نگه می‌دارد؛ برای ارسال تیکت باید «ثبت درخواست» را بزنید. «بازگشت متن قبلی» متن خودتان را برمی‌گرداند و «پیشنهاد دیگر» متن تازه می‌سازد. متن شما و انتخاب‌های فرم برای ساخت پیشنهاد به سرویس هوش مصنوعی فرستاده می‌شود. اطلاعات محرمانه وارد نکنید. روزانه ' . convertToPersian(config('ai.ticket.daily_cap')) . ' بار می‌توانید پیشنهاد بگیرید. اگر سرویس در دسترس نباشد یا سهمیه تمام شود، الگوی آماده می‌آید. در صورت رد متن توسط سرویس، الگو می‌آید و یک بار از سهمیه کم می‌شود.'] : []),
            ],
        ],
        'personal' => [
            'label' => 'تنظیم فهرست',
            'icon' => 'tune',
            'notes' => [
                'دکمهٔ رنگ کنار هر ردیف کمک می‌کند تیکت را راحت‌تر پیدا کنید. رنگ فقط در مرورگر خودتان ذخیره می‌شود و وضعیت تیکت را عوض نمی‌کند.',
                'با دکمهٔ زنگ کنار تیکت، برای خودتان یادآور تنظیم کنید. اگر یادآوری داشته باشید، همان دکمه برای ویرایش آن باز می‌شود.',
                'از ابزارهای بالای جدول، ستون‌ها، فاصلهٔ ردیف‌ها و ترتیب نمایش را تنظیم کنید. از دکمهٔ جستجو و فیلتر، تیکت موردنظر را پیدا کنید یا فقط یک وضعیت را ببینید.',
                'در حالت بزرگ‌شده، دکمهٔ «بارگذاری بیشتر» دیده نمی‌شود. برای دریافت ردیف‌های بیشتر ابتدا جدول را به اندازهٔ قبلی برگردانید.',
                'کنار نام مسئول، نشان حضور او را می‌بینید. مرخصی یا مأموریت فقط برای اطلاع شماست و مانع انتخاب او به‌عنوان مسئول نمی‌شود.',
            ],
        ],
    ];
@endphp

<div x-data="{ tab: 'roles', role: 'requester', note: 'display' }">
    <p class="mb-4 text-xs leading-6 text-[var(--md-sys-color-on-surface-variant)]">تیکت یعنی یک درخواست پشتیبانی که می‌توانید پاسخ‌ها و مراحل رسیدگی به آن را پیگیری کنید.</p>
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

    <div x-show="tab === 'roles'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach([
                ['id' => 'requester', 'icon' => 'person', 'label' => 'درخواست‌دهنده'],
                ['id' => 'head', 'icon' => 'shield_person', 'label' => 'مدیر واحد'],
                ['id' => 'assignee', 'icon' => 'engineering', 'label' => 'مسئول رسیدگی'],
            ] as $r)
                <button
                    type="button"
                    @click="role = '{{ $r['id'] }}'"
                    :class="role === '{{ $r['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-2 py-2 rounded-xl text-[12px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $r['icon'] }}</span>
                    {{ $r['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($rows as $roleId => $sections)
            <div x-show="role === '{{ $roleId }}'" x-cloak class="space-y-3">
                <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">{{ $sections['note'] }}</p>
                @foreach(array_slice($sections, 1) as $s)
                    @php
                        $chipClasses = match ($s['color']) {
                            'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                            'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                            'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                        };
                    @endphp
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
        @endforeach
    </div>

    <div x-show="tab === 'notes'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl">
            @foreach($noteGroups as $key => $group)
                <button
                    type="button"
                    @click="note = '{{ $key }}'"
                    :class="note === '{{ $key }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-2 py-2 rounded-xl text-[12px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $group['icon'] }}</span>
                    {{ $group['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($noteGroups as $key => $group)
            <div x-show="note === '{{ $key }}'" x-cloak class="space-y-2">
                @foreach($group['notes'] as $note)
                    <div class="flex items-start gap-2 px-1">
                        <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                        <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
