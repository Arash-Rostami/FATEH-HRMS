@php
    $tabs = [
        ['id' => 'statuses', 'icon' => 'flag', 'label' => 'وضعیت‌ها'],
        ['id' => 'history', 'icon' => 'history', 'label' => 'تاریخچه'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $statusRows = [
        ['icon' => 'check_circle', 'color' => 'sapphire', 'label' => 'فعال', 'text' => 'رزرو شما فعال است و تا پایان بازهٔ رزروشده معتبر می‌ماند.'],
        ['icon' => 'autorenew', 'color' => 'sage', 'label' => 'آزادشده', 'text' => 'این رزرو پیش از پایان بازه توسط مدیریت آزاد شده است؛ همچنان در سهمیهٔ ماهانهٔ شما محاسبه می‌شود، ولی دیگر قابل لغو نیست.'],
        ['icon' => 'event_busy', 'color' => 'amethyst', 'label' => 'لغو توسط کاربر', 'text' => 'خودتان این رزرو را لغو کرده‌اید.'],
        ['icon' => 'gpp_bad', 'color' => 'gold', 'label' => 'لغو توسط ادمین', 'text' => 'این لغو را یک کاربر با دسترسی مدیریتی (ادمین یا توسعه‌دهنده) انجام داده است؛ معمولاً برای رفع تداخل یا تغییر برنامه.'],
    ];

    $historyRows = [
        ['icon' => 'event_upcoming', 'color' => 'primary', 'label' => 'پیش‌رو', 'text' => 'رزروهای فعالِ آینده؛ تنها از این زبانه و تا پیش از شروع رزرو می‌توانید آن را لغو کنید (برای رزرو تمام‌روز تا پایان همان روز).'],
        ['icon' => 'history', 'color' => 'tertiary', 'label' => 'قبلی', 'text' => 'رزروهای گذشتهٔ انجام‌شده؛ امکان لغو ندارند.'],
        ['icon' => 'event_busy', 'color' => 'error', 'label' => 'لغو شده', 'text' => 'رزروهای لغوشده (توسط شما یا ادمین)؛ نام رزرو به‌صورت خط‌خورده نمایش داده می‌شود و اگر علت لغو ثبت شده باشد، آن علت و نام لغوکننده هم در همان ردیف دیده می‌شود.'],
        ['icon' => 'autorenew', 'color' => 'secondary', 'label' => 'آزادشده', 'text' => 'رزروهایی که پیش از پایان بازه آزاد کرده‌اید.'],
    ];

    $notes = [
        'رزرو «فعال» و «آزادشده» هر دو در سقف رزرو ماهانهٔ شما محاسبه می‌شوند؛ فقط رزرو «فعال» را می‌توانید خودتان لغو کنید.',
        'سقف رزرو ماهانه برای هر نوع منبع جداست و بر اساس ماهِ شروع رزرو شمرده می‌شود؛ اگر مدیر برای شما سقف اختصاصی تعیین کرده باشد، نوار «رزروهای فعال این ماه» همان عدد را نشان می‌دهد.',
        'اگر به سقف رزرو ماهانه رسیده‌اید ولی برای آن نوع منبع «رزرو لحظه‌آخری» فعال است، فقط منبعی را که حداکثر چند ساعت دیگر شروع می‌شود و آزاد مانده می‌توانید رزرو کنید؛ این رزرو هم در سقف ماهانه‌تان شمرده می‌شود و بقیهٔ زمان‌ها همچنان مسدودند.',
        'دکمهٔ «گروه افراد» کنار عنوان صفحه، فهرست رزروهای همکاران را باز می‌کند: با دکمه‌های «امروز، فردا، این هفته، هفته بعد، این ماه، ماه بعد» یا یک بازهٔ دلخواه (حداکثر ۹۳ روز؛ روز، ماه و سال شروع را که انتخاب کنید همان‌ها برای پایان هم پر می‌شود و هر تاریخ نادرست پیامش را زیر همان فیلد نشان می‌دهد) و فلش‌های قبلی/بعدی بازه را عوض کنید، نوع منبع را انتخاب یا همه را ببینید، و با جستجوی نام منبع، مشخصاتش (طبقه، داخلی…) یا نام همکار پیدا کنید «این منبع دست کیست». دکمهٔ «ارتباط» هر ردیف دو بخش دارد: «درخواست دستی» (خودتان اقدام می‌کنید) و «درخواست سیستمی» (سیستم پیام آماده را برایتان می‌فرستد). از آن می‌توانید به او پیام بدهید، با داخلی یا موبایلش تماس بگیرید (اگر وضعیت حضورش اجازه بدهد) یا یکی از پیام‌های آماده («تماس با من»، «جابه‌جایی رزرو»، «آزادسازی منبع») را بفرستید تا برایش اعلان بیاید؛ ارسال پیام آماده در هر دقیقه حداکثر ۵ بار ممکن است. رزرو چندروزه (بلندمدت) فقط یک‌بار و زیر اولین روزِ بازهٔ انتخابی می‌آید و بازهٔ واقعی‌اش در ستون زمان نوشته می‌شود. اگر برای منبع تصویر ثبت شده باشد، تصویر کوچک کنار نامش را بزنید تا بزرگ و شفاف ببینید و از درست‌بودن جای رزرو مطمئن شوید. نام کاربران مهمان و غیرفعال نمایش داده نمی‌شود. در «تاریخچه من» هم اگر منبع تصویر دارد، به‌جای آیکن، تصویر کوچکش دیده می‌شود و با زدن روی آن بزرگ می‌شود تا نشانی و جای رزرو را ببینید. در نمای کلاسیک و در صفحهٔ کوچک (موبایل) دکمهٔ «رفتن به تاریخچه من» بالای صفحه شما را مستقیم به تاریخچه می‌برد.',
        'دو نوار بالای صفحه سهمیهٔ شما برای «نوعِ انتخاب‌شده» است: «رزروهای فعال این ماه» (ماه شمسی؛ از اول تا آخر ماه) تعداد رزروهای فعال و آزادشدهٔ شما در ماهِ تاریخ انتخابی را نسبت به سقف ماهانه نشان می‌دهد (با لغو یک رزرو جا باز می‌شود)؛ «لغوهای N روز اخیر» (N = بازهٔ شمارش لغو که ادمین تعیین می‌کند؛ پیش‌فرض ۳۰) تعداد رزروهایی است که خودتان در بازهٔ شمارش لغو (N روز اخیر) لغو کرده‌اید (لغو ادمین شمرده نمی‌شود؛ اگر سقف ۰ باشد، لغو کردن برای این نوع منبع غیرفعال است و جلوی رزرو جدید را نمی‌گیرد) و با رسیدن به سقف، ثبت رزرو جدید از آن نوع موقتاً مسدود می‌شود. هر نوار فقط وقتی دیده می‌شود که مدیر برای آن نوع سقفی گذاشته باشد. رنگ نوار با نزدیک‌شدن به سقف عوض می‌شود (از ۸۰٪ کهربایی با پیام «فقط N رزرو دیگر»، و در سقف قرمز)؛ زیر نوار رزرو می‌بینید سهمیه چه روزی بازمی‌گردد، زیر نوار لغو می‌بینید یکی از لغوهای شما چند روز دیگر از شمارش خارج می‌شود، و اگر رزرو لحظه‌آخری برای آن نوع فعال باشد، زمان دقیقِ باز‌شدن تاریخ انتخابی نوشته می‌شود. دکمهٔ جمع‌شوندهٔ «قوانین» زیر نوارها، همهٔ قوانینِ تنظیم‌شدهٔ نوعِ انتخاب‌شده را دسته‌بندی‌شده (زمان‌بندی، مدت، سهمیه، سایر) نشان می‌دهد: چند روز جلوتر، حداقل مهلت، روزها و ساعت‌های مجاز، مدت، سقف رزرو و لغو (با سقف شخصی شما)، تکرار، تمام‌روز، لغو در سری، آزادسازی، تأیید مدیر و رزرو لحظه‌آخری.',
        'رزروهای تکرارشوندهٔ یک سری در تاریخچه زیر یک ردیف با نشان «repeat + N» جمع می‌شوند؛ اگر لغو یک‌تکه فعال نباشد، لغوی یک عضو، کلِ سری (فقط تکرارهای آینده) را لغو می‌کند و یک بار در سقف لغو شمرده می‌شود؛ اگر هنگام ثبت سری بعضی تکرارها به‌دلیل قوانین یا تداخل ثبت نشوند، پیام هشدار تعداد آن‌ها را نشان می‌دهد.',
        'برخی منابع علاوه بر سیاست کلی نوع، محدودیت روز یا ساعت اختصاصی خودِ همان منبع را هم دارند؛ منبعی که در روز انتخابی شما مجاز نیست در فهرست دیده نمی‌شود، و در نمای آوانگارد وقتی منبعی را باز می‌کنید، ریل و ساعت‌ها و بخش «قوانین» روی بازهٔ مشترکِ سیاست نوع و همان منبع تنظیم می‌شود (مثلاً فقط یکشنبه‌ها ۱۱:۰۰ تا ۱۶:۳۰)؛ تا وقتی ساعت انتخابی شما در بازهٔ مجاز یک منبع نباشد، دکمهٔ رزرو همان کارت (در هر دو نما) غیرفعال است و بازهٔ مجازش را نشان می‌دهد. نوار تاریخ از امروز تا پایان بازهٔ مجاز پیش‌رزرو را نشان می‌دهد و هر روز یک نشان کوچک دارد: عدد گوشهٔ پایین-چپ (با رنگ تم) یعنی همان تعداد منبع هنوز جا دارد، «تکمیل» قرمز یعنی همهٔ منابع در ساعت‌های کاری‌شان پر شده‌اند، و بدون نشان یعنی آن روز منبعی ارائه نمی‌شود.',
        'در «تاریخچه من»، با نگه‌داشتن نشانگر روی هر رزرو، گوشهٔ چپ آن یک آیکون زنگ یادآوری ظاهر می‌شود؛ اگر برای آن رزرو یادآوری تنظیم کرده باشید، همیشه با رنگ اصلی برنامه نمایان می‌ماند و با کلیک، همان یادآوری موجود برای ویرایش باز می‌شود.',
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