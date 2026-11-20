@php
    $tabs = [
        ['id' => 'statuses', 'icon' => 'flag', 'label' => 'وضعیت‌ها'],
        ['id' => 'history', 'icon' => 'history', 'label' => 'تاریخچه'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $statusRows = [
        ['icon' => 'check_circle', 'color' => 'sapphire', 'label' => 'فعال', 'text' => 'رزرو لغو یا آزاد نشده است. برای زمان استفاده، تاریخ و ساعت آن را ببینید.'],
        ['icon' => 'autorenew', 'color' => 'sage', 'label' => 'آزادشده', 'text' => 'مدیریت این رزرو را آزاد کرده است. هنوز از سهمیهٔ ماهانه کم می‌کند و دیگر قابل لغو نیست. قابل رزرو شدن همان زمان برای دیگران، به قوانین آن نوع بستگی دارد.'],
        ['icon' => 'event_busy', 'color' => 'amethyst', 'label' => 'لغو توسط کاربر', 'text' => 'صاحب رزرو آن را لغو کرده است. رزرو لغوشده از سهمیهٔ ماهانه کنار می‌رود، اما در تعداد لغوهای کاربر حساب می‌شود.'],
        ['icon' => 'gpp_bad', 'color' => 'gold', 'label' => 'لغو توسط مدیریت', 'text' => 'مدیر سامانه این رزرو را لغو کرده است. این کار به تعداد لغوهای خود کاربر اضافه نمی‌شود.'],
    ];

    $historyRows = [
        ['icon' => 'event_upcoming', 'color' => 'primary', 'label' => 'پیش‌رو', 'text' => 'رزروهای فعالِ هنوز تمام‌نشده را اینجا ببینید. برای رزرو ساعتی، پیش از شروع لغو کنید؛ رزرو تمام‌روز را تا پایان همان روز می‌توان لغو کرد، اگر سقف لغو اجازه بدهد.'],
        ['icon' => 'history', 'color' => 'tertiary', 'label' => 'قبلی', 'text' => 'رزروهایی که زمانشان گذشته است؛ دیگر قابل لغو توسط خودتان نیستند.'],
        ['icon' => 'event_busy', 'color' => 'error', 'label' => 'لغو شده', 'text' => 'رزروهای لغوشده توسط کاربر یا مدیریت. نام لغوکننده و دلیل، اگر ثبت شده باشند، در ردیف دیده می‌شوند.'],
        ['icon' => 'autorenew', 'color' => 'secondary', 'label' => 'آزادشده', 'text' => 'رزروهایی که مدیریت آزاد کرده است. آزادسازی با لغو فرق دارد؛ سهمیه را برنمی‌گرداند و ممکن است هنوز مانع رزرو همزمان خودتان باشد.'],
    ];

    $notes = [
        'برای شروع، نوع مورد و تاریخ را انتخاب کنید. برای رزرو ساعتی، شروع و پایان را هم مشخص کنید. سپس مورد دلخواه را انتخاب و خلاصهٔ رزرو را تأیید کنید.',
        '«مورد رزرو» می‌تواند میز، جای پارک، وسیله یا زمان ملاقات باشد. فیلترها فقط به پیدا کردن مورد مناسب کمک می‌کنند؛ رزروی ثبت نمی‌کنند.',
        'سهمیه برای هر نوع جداست و بر اساس ماه شمسیِ شروع رزرو حساب می‌شود. رزروهای فعال و آزادشده در آن ماه شمرده می‌شوند؛ رزروهای لغوشده شمرده نمی‌شوند.',
        'به سهمیهٔ ماهانه و سقف لغو جداگانه توجه کنید. لغو یک رزرو ممکن است سهمیه را باز کند، اما اگر سقف لغو پر شود، رزرو تازه نیز موقتاً بسته می‌شود.',
        'سقف لغو، فقط لغوهای خودتان در تعداد روز تعیین‌شده را می‌شمارد. با گذشت زمان، لغوهای قدیمی از شمارش خارج می‌شوند. سقف صفر یعنی لغو مجاز نیست؛ به‌تنهایی مانع رزرو تازه نمی‌شود.',
        'رزرو لحظه‌آخری فقط محدودیت سهمیهٔ ماهانه را کنار می‌گذارد. مورد باید آزاد باشد و فاصلهٔ لازم تا شروع و سایر قوانین نیز رعایت شوند. این رزرو هم از سهمیه کم می‌کند.',
        'در رزرو تکراری، تعداد شامل نوبت اول است. روزانه یعنی هر روز و هفتگی یعنی همان روز در هفته‌های بعد. هر نوبت جدا بررسی می‌شود؛ نوبت نامعتبر یا دارای تداخل ثبت نمی‌شود.',
        'تاریخ‌های پیشنهادی، تضمین ثبت نیستند. پس از ثبت، پیام تعداد نوبت‌های ثبت‌نشده و تاریخچه را بررسی کنید.',
        'پیش از لغو رزرو تکراری، متن تأیید را بخوانید. بسته به قوانین، ممکن است فقط همان نوبت یا همهٔ نوبت‌های مرتبطِ قابل لغو، لغو شوند. لغو همزمان یک گروه تکرار، یک نوبت لغو حساب می‌شود.',
        'در «رزروهای همکاران»، با نام همکار یا مورد رزرو جستجو کنید. بازهٔ دلخواه حداکثر ۹۳ روز است. برای دیدن موارد بیشتر، فهرست را ادامه دهید یا بازه را محدودتر کنید.',
        'در منوی «ارتباط»، خودتان تماس یا پیام بدهید، یا پیام آماده بفرستید. ارسال درخواست، رزرو همکار را تغییر نمی‌دهد؛ باید با او هماهنگ کنید. پیامک فقط در صورت فعال بودن سرویس و امکان تماس با همکار نمایش داده می‌شود.',
        'برای ملاقات با همکار مرتبط، دعوت‌نامه در تقویم او ثبت می‌شود. تغییر رزرو را از بخش رزرو انجام دهید؛ رویداد تقویم جداگانه قابل ویرایش نیست.',
        'در بخش «قوانین»، فاصلهٔ لازم تا شروع، روزها و ساعت‌های مجاز و سقف‌ها را ببینید. قوانین ممکن است بین دو نوع یا دو مورد متفاوت باشند.',
        'در هر دو نمای صفحه، نتیجهٔ رزرو یکسان است. در صفحهٔ کوچک، «رفتن به تاریخچه من» شما را مستقیم به رزروهای خودتان می‌برد.',
    ];
@endphp

<div x-data="{ tab: 'statuses' }">
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

    <div x-show="tab === 'statuses'" x-cloak class="space-y-2">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">هر رزرو یکی از این چهار وضعیت را دارد؛ رنگ و آیکون هرکدام همین‌جا مشخص است.</p>
        @foreach($statusRows as $row)
            @php
                $chipClasses = match ($row['color']) {
                    'sapphire' => 'bg-[var(--tool-sapphire-bg)] text-[var(--tool-sapphire-color)]',
                    'gold' => 'bg-[var(--tool-gold-bg)] text-[var(--tool-gold-color)]',
                    'amethyst' => 'bg-[var(--tool-amethyst-bg)] text-[var(--tool-amethyst-color)]',
                    'sage' => 'bg-[var(--tool-sage-bg)] text-[var(--tool-sage-color)]',
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

    <div x-show="tab === 'history'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">رزروهای شما در بخش «تاریخچه من» در چهار زبانه دسته‌بندی می‌شوند.</p>
        @foreach($historyRows as $s)
            @php
                $historyChip = match ($s['color']) {
                    'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                    'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $historyChip }}">
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
        @if(($activeTab ?? null) === 'meeting')
            <div wire:key="reservation-legend-meeting-note" class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">person</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">منابع نوع «ملاقات» نمایندهٔ همکاران شما هستند؛ با رزرو، یک دعوتنامهٔ جلسه در تقویم آن شخص ثبت و به او اطلاع‌رسانی می‌شود.</p>
            </div>
        @endif
        @if(($view ?? null) === 'avantgarde')
            <div wire:key="reservation-legend-grid-note" class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">table_rows</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">در نمای «تاریخچه من»، با نگه‌داشتن نشانگر روی هر ردیف، گوشهٔ چپ ستون آخر سه دکمه ظاهر می‌شود: «پالت» برای رنگ‌آمیزی ردیف، «سنجاق» برای نمایش دائم ردیف در بالای فهرست، و زنگ یادآوری. سنجاق و رنگ فقط در مرورگر خودتان ذخیره می‌شوند (به‌جز خودِ یادآوری که سرور آن را نگه می‌دارد) و محتوای رزروها را تغییر نمی‌دهند. دکمهٔ «مشاهده جزئیات» شما را به نمای رزرو همان منبع می‌برد. سربرگ جدول — مرتب‌سازی (با دکمهٔ مرتب‌سازی یا کلیک روی «منبع» و «زمان»؛ ردیف‌های سنجاق‌شده همیشه بالا می‌مانند و ترتیب ذخیره نمی‌شود)، فیلتر بازهٔ تاریخ، انتخاب ستون‌ها، «نمایش فشرده» و «بزرگ کردن» — هم فقط در مرورگر خودتان ذخیره می‌شوند (فیلتر تاریخ نتیجهٔ فهرست را تغییر می‌دهد)؛ دکمهٔ پیکانِ انتهای همین ردیف («خلوت‌سازی ابزارها») این ابزارها و دکمهٔ شناور جستجو را جمع می‌کند، با «حالت خلوت» منوی همبرگری هم خودکار جمع می‌شود و انتخاب شما به‌خاطر سپرده می‌شود؛ ستون آخر همیشه می‌ماند.</p>
            </div>
            <div wire:key="reservation-legend-booking-note" class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">event_available</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">در نمای رزرو، روزهای غیرقابل‌انتخاب تقویم با نگه‌داشتن نشانگر دلیلشان را نشان می‌دهند (روز گذشته، روز غیرمجاز طبق قوانین رزرو — خط‌خورده —، خارج از بازه، یا برای منابع تمام‌روز مثل میز/پارکینگ/خودرو، روزی که همهٔ منابع آن نوع در آن رزرو شده‌اند — با الگوی راه‌راه مشخص می‌شود). روی ریل زمانی، بخش‌های راه‌راه یعنی همان منبع در آن بازه رزرو دارد؛ اگر بازهٔ شما با آن‌ها همپوشانی شود قرمز و برچسب «تداخل با رزرو فعلی» می‌گیرد — این هشدار فقط دیداری است و تصمیم نهایی با اعتبارسنجی هنگام ثبت است. دستگیره‌ها را می‌توان با ماوس کشید، با کلیک روی ریل به نزدیک‌ترین دستگیره پرش کرد یا با کلیدهای جهت‌داد (هر گام ۳۰ دقیقه) جابه‌جا کرد. همچنین با کلیدهای چپ/راست یا دکمه‌های روی تصویر می‌توانید بدون کلیک، بین منابع موجود دیگر جابه‌جا شوید؛ جزئیات هر منبع در یک ردیفِ زیر تصویر آن نمایش داده می‌شود.</p>
            </div>
        @endif
    </div>
</div>
