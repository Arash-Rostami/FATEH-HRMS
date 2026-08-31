@php
    $tabs = [
        ['id' => 'metrics', 'icon' => 'monitoring', 'label' => 'معیارها', 'sub' => 'tiles'],
        ['id' => 'tasks', 'icon' => 'workspaces', 'label' => 'وظایف و پروژه‌ها', 'sub' => 'grouping'],
        ['id' => 'sharing', 'icon' => 'ios_share', 'label' => 'اشتراک‌گذاری و خروجی'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $sections = [
        'metrics' => [
            'intro' => 'همهٔ اعداد، نمودارها و چیپ‌های این بخش برای بازهٔ زمانیِ انتخاب‌شدهٔ بالای صفحه محاسبه می‌شوند.',
            'rows' => [
                ['icon' => 'task_alt', 'color' => 'primary', 'label' => 'تکمیل‌شده یعنی چه', 'text' => 'شمار «تکمیل‌شده» بر اساس رویدادهای واقعی تغییر وضعیت در همین بازه محاسبه می‌شود، نه وضعیت فعلی وظیفه — اگر وظیفه‌ای در این بازه انجام شد و بعداً دوباره باز شد، همچنان در این گزارش «تکمیل‌شده» شمرده می‌شود.'],
                ['icon' => 'percent', 'color' => 'secondary', 'label' => 'درصد به‌موقع بودن', 'text' => 'بر اساس مهلتِ فعلیِ هر وظیفه سنجیده می‌شود، نه مهلتی که در لحظهٔ تکمیل داشته؛ اگر مهلت بعداً ویرایش شود، این عدد با مهلت تازه بازمحاسبه می‌شود.'],
                ['icon' => 'schedule', 'color' => 'tertiary', 'label' => 'میانگین و میانهٔ زمان انجام', 'text' => 'هر دو عدد روی کارت دیده می‌شوند: «میانگین» حساس به داده‌های پرت است (یک وظیفهٔ خیلی کند همه را بالا می‌کشد)، «میانه» حساس نیست — برای مقایسهٔ بازه‌ها همیشه میانه ملاک محاسبهٔ چیپ تغییر است.'],
                ['icon' => 'verified', 'color' => 'primary', 'label' => 'تأییدیهٔ دریافتی', 'text' => 'شمار رویدادهای تأیید مثبتی است که دیگران روی وظایف شما ثبت کرده‌اند؛ رد یا لغوِ تأیید در این عدد شمرده نمی‌شود.'],
                ['icon' => 'trending_up', 'color' => 'secondary', 'label' => 'چیپ تغییر و مقایسه با بازهٔ قبل', 'text' => 'سبز یعنی بهتر شدن نسبت به بازهٔ مشابهِ پیش از این بازه، خاکستری یعنی بدون تغییر، قرمز یعنی بدتر شدن. کلیک روی چیپ، گزارش را به همان بازهٔ قبلی می‌برد؛ با بنر «بازگشت» به بازهٔ فعلی برمی‌گردید — این حالت موقتی است و در آدرس صفحه ذخیره نمی‌شود.'],
                ['icon' => 'show_chart', 'color' => 'tertiary', 'label' => 'نمودار ریز کنار هر عدد', 'text' => 'همان دو نقطهٔ «بازهٔ قبل» و «بازهٔ فعلی» را به‌صورت یک خط کوچک نشان می‌دهد. تا وقتی بازهٔ قبل داده کافی نداشته باشد، این نمودار و چیپ تغییر هیچ‌کدام ظاهر نمی‌شوند.'],
                ['icon' => 'bar_chart', 'color' => 'primary', 'label' => 'نمودار روند هفتگی', 'text' => 'بالای کارت، تعداد تکمیل‌شده‌های هر هفتهٔ همین بازه را به‌صورت میله‌ای نشان می‌دهد؛ اگر مجموع کل بازه کمتر از سه تکمیل‌شده باشد، به‌جای نمودار پیامِ «داده کافی برای نمودار هفتگی نیست» می‌بینید.'],
                ['icon' => 'flag', 'color' => 'secondary', 'label' => 'چیپ‌های اولویتِ تکمیل‌شده‌ها', 'text' => 'زیر کارت‌های اصلی، برای هر سطح اولویت (فوری، بالا، متوسط، کم) که در همین بازه دست‌کم یک وظیفهٔ تکمیل‌شده داشته باشد، یک چیپ جداگانه با شمار همان اولویت دیده می‌شود — عددها ترکیب یا میانگین نمی‌شوند، هر چیپ فقط شمار اولویت خودش را نشان می‌دهد؛ اولویتی که در این بازه تکمیل‌شده‌ای نداشته، اصلاً چیپ ندارد.'],
                ['icon' => 'warning', 'color' => 'tertiary', 'label' => 'همچنان معوق / در حال انجام / مهلت نزدیک', 'text' => 'این سه عدد وابسته به بازهٔ انتخابی نیستند و همیشه وضعیت لحظه‌ایِ وظایف شما را نشان می‌دهند: معوق (مهلت گذشته و هنوز باز)، در حال انجام، و مهلت نزدیک (تا هفت روز آینده).'],
            ],
        ],
        'tasks' => [
            'intro' => 'وظایف متصل به پروژه زیر نام همان پروژه گروه‌بندی می‌شوند و وظایف بدون پروژه در گروه «مستقل» جدا دیده می‌شوند؛ پروژه‌ای که در این بازه هیچ فعالیت شخصی از شما نداشته، در فهرست نمی‌آید.',
            'rows' => [
                ['icon' => 'workspaces', 'color' => 'secondary', 'label' => 'پروژه‌ها و وظایف مستقل', 'text' => 'هر پروژه و گروه «مستقل» یک ردیف خلاصه (تکمیل‌شده، به‌موقع، معوق، در حال انجام) دارد؛ کلیک روی ردیف، آکاردئون همان گروه را باز می‌کند.'],
                ['icon' => 'military_tech', 'color' => 'tertiary', 'label' => 'نکات برجسته', 'text' => 'سه کارت «سخت‌ترین بسته‌شده» (بر اساس اولویت × مدت انجام)، «سریع‌ترین انجام» و «بیشترین همکاری» (شمار نظرهای دیگران روی همان وظیفه، نه نظرهای خودتان) فقط وقتی حداقل یک وظیفه در این بازه تکمیل شده باشد ظاهر می‌شوند.'],
                ['icon' => 'expand', 'color' => 'primary', 'label' => 'جدول ریز داخل هر آکاردئون', 'text' => 'داخل هر پروژه یا گروه «مستقل»، جدول وظایف با اولویت، وضعیت، مهلت، تاریخ تکمیل، مدت انجام و به‌موقع‌بودن دیده می‌شود — همان ستون‌هایی که در خروجی اکسل هم می‌آیند.'],
                ['icon' => 'timeline', 'color' => 'secondary', 'label' => 'آکاردئون فعالیت‌ها', 'text' => 'رویدادهای تغییر وضعیت، تأیید، واگذاری و نظرِ وظایف دخیل در همین بازه را به‌ترتیب زمان نشان می‌دهد؛ با دکمهٔ «بارگذاری بیشتر» صفحه‌بندی می‌شود.'],
            ],
        ],
        'sharing' => [
            'intro' => 'بازهٔ گزارش با دکمه‌های بالای صفحه («این ماه»، «ماه گذشته»، «این فصل» یا یک بازهٔ دلخواه) تغییر می‌کند؛ همهٔ بخش‌های زیر همان بازهٔ انتخابی را می‌سازند.',
            'rows' => [
                ['icon' => 'forward_to_inbox', 'color' => 'primary', 'label' => 'اشتراک‌گذاری با مدیر', 'text' => 'دکمهٔ اشتراک‌گذاری یک لینک امضاشده برای مدیر شما می‌سازد و در کارتابل او اعلان می‌گذارد؛ این لینک تا ۱۴ روز معتبر است و پس از آن دیگر باز نمی‌شود.'],
                ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان در کارتابل گیرنده', 'text' => 'با ارسال گزارش، یک اعلان معمولی برای گیرنده ثبت می‌شود — دقیقاً همان زنگولهٔ اعلان‌هایی که در سراسر برنامه استفاده می‌شود، بدون هیچ رفتار جداگانه‌ای.'],
                ['icon' => 'visibility', 'color' => 'tertiary', 'label' => 'حالت فقط‌خواندنی', 'text' => 'گیرندهٔ لینکِ اشتراکی گزارش را فقط می‌بیند — بازهٔ زمانی، دامنهٔ پروژه و دکمه‌های تغییر برایش غیرفعال‌اند؛ فقط باز و بسته‌کردن فهرست فعالیت‌ها برای او هم کار می‌کند.'],
                ['icon' => 'print', 'color' => 'primary', 'label' => 'چاپ گزارش', 'text' => 'دکمهٔ چاپ پیش از باز کردن پیش‌نمایش چاپ، همهٔ آکاردئون‌های بسته را خودکار باز می‌کند تا چیزی از قلم نیفتد.'],
                ['icon' => 'grid_on', 'color' => 'secondary', 'label' => 'خروجی اکسل کامل است', 'text' => 'دکمهٔ خروجی فقط چند عدد خلاصه نیست — همان خلاصهٔ عملکرد، نکات برجسته، روند هفتگی، ریزبه‌تفکیک هر پروژه و فهرست کامل وظایف (گروه‌بندی‌شده بر اساس همان پروژه‌ها) را در یک فایل اکسل می‌سازد؛ دقیقاً همان بازهٔ زمانی که روی صفحه می‌بینید.'],
            ],
        ],
    ];

    $chipClasses = fn($c) => match ($c) {
        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
    };

    $m = $sections['metrics']['rows'];
    $t = $sections['tasks']['rows'];
    $groups = [
        'metrics' => [
            ['id' => 'tiles', 'icon' => 'grid_view', 'label' => 'کارت‌های اصلی', 'rows' => [$m[0], $m[1], $m[2], $m[3]]],
            ['id' => 'compare', 'icon' => 'trending_up', 'label' => 'نمودار و مقایسه', 'rows' => [$m[4], $m[5], $m[6]]],
            ['id' => 'extra', 'icon' => 'flag', 'label' => 'اولویت و وضعیت لحظه‌ای', 'rows' => [$m[7], $m[8]]],
        ],
        'tasks' => [
            ['id' => 'grouping', 'icon' => 'workspaces', 'label' => 'گروه‌بندی و نکات برجسته', 'rows' => [$t[0], $t[1]]],
            ['id' => 'detail', 'icon' => 'timeline', 'label' => 'جزئیات و فعالیت‌ها', 'rows' => [$t[2], $t[3]]],
        ],
    ];

    $notes = [
        'برای دیدن چیپ تغییر و نمودار ریز، بازهٔ قبل از بازهٔ فعلی باید دست‌کم سه وظیفهٔ واقعاً تکمیل‌شده داشته باشد؛ کمتر از آن، فقط عدد بازهٔ فعلی را می‌بینید و مقایسه‌ای نمایش داده نمی‌شود.',
        'اعداد این صفحه ممکن است چند ثانیه تا چند دقیقه با آخرین تغییرات فاصله داشته باشند تا صفحه سریع‌تر باز شود؛ اگر همین الان وظیفه‌ای را تمام کردید و عدد به‌روز نشد، کمی صبر کنید یا صفحه را دوباره بارگذاری کنید.',
        'با دکمه‌های بالای صفحه بین «این ماه»، «ماه گذشته»، «این فصل» یا یک بازهٔ دلخواه سوئیچ کنید — نمودارها، جدول‌ها و خروجی اکسل همگی همان بازهٔ انتخابی را می‌سازند.',
    ];
@endphp

<div x-data="{ tab: 'metrics', sub: 'tiles' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $tab)
            <button type="button" @click="tab = '{{ $tab['id'] }}'@if(!empty($tab['sub'])) ; sub = '{{ $tab['sub'] }}'@endif"
                    :class="tab === '{{ $tab['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex flex-col items-center justify-center gap-0.5 px-1.5 py-2 rounded-xl text-[11px] font-bold transition-all duration-200">
                <span class="material-symbols-rounded text-[18px]">{{ $tab['icon'] }}</span>
                <span class="leading-tight text-center">{{ $tab['label'] }}</span>
            </button>
        @endforeach
    </div>

    @foreach($tabs as $tab)
        @if($tab['id'] === 'notes')
            <div x-show="tab === 'notes'" x-cloak class="space-y-2">
                @foreach($notes as $note)
                    <div class="flex items-start gap-2 px-1">
                        <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                        <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
                    </div>
                @endforeach
            </div>
        @elseif(!empty($groups[$tab['id']]))
            @php($sec = $sections[$tab['id']])
            <div x-show="tab === '{{ $tab['id'] }}'" x-cloak>
                @if(!empty($sec['intro']))
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-2">{{ $sec['intro'] }}</p>
                @endif
                <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
                    @foreach($groups[$tab['id']] as $g)
                        <button type="button" @click="sub = '{{ $g['id'] }}'"
                                :class="sub === '{{ $g['id'] }}'
                                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200">
                            <span class="material-symbols-rounded text-[17px]">{{ $g['icon'] }}</span>
                            {{ $g['label'] }}
                        </button>
                    @endforeach
                </div>
                @foreach($groups[$tab['id']] as $g)
                    <div x-show="sub === '{{ $g['id'] }}'" x-cloak class="space-y-2">
                        @foreach($g['rows'] as $s)
                            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($s['color']) }}">
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
        @else
            @php($sec = $sections[$tab['id']])
            <div x-show="tab === '{{ $tab['id'] }}'" x-cloak class="space-y-2">
                @if(!empty($sec['intro']))
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">{{ $sec['intro'] }}</p>
                @endif
                @foreach($sec['rows'] as $s)
                    <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($s['color']) }}">
                            <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                            <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach
</div>
