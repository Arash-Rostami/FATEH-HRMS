@php
    $tabs = [
        ['id' => 'metrics', 'icon' => 'monitoring', 'label' => 'معنی عددها', 'sub' => 'tiles'],
        ['id' => 'tasks', 'icon' => 'workspaces', 'label' => 'وظایف و پروژه‌ها', 'sub' => 'grouping'],
        ['id' => 'sharing', 'icon' => 'ios_share', 'label' => 'اشتراک‌گذاری و خروجی'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $sections = [
        'metrics' => [
            'intro' => 'چهار عدد اول، نتیجهٔ کار در بازهٔ انتخاب‌شده‌اند. کارهای عقب‌افتاده و در حال انجام، وضعیت فعلی کارهای مرتبط با گزارش را نشان می‌دهند.',
            'rows' => [
                ['icon' => 'task_alt', 'color' => 'primary', 'label' => 'چه کاری انجام‌شده حساب می‌شود؟', 'text' => 'وظیفه باید در این بازه به «انجام‌شده» رفته باشد. نتیجه به مسئول آن هنگام انجام می‌رسد؛ جابه‌جا کردن کارت توسط مدیر، کار را به نام مدیر ثبت نمی‌کند. اگر وظیفه دوباره باز شود، ممکن است از این تعداد کم شود. تأیید مالک پروژه جداگانه شمرده می‌شود.'],
                ['icon' => 'percent', 'color' => 'secondary', 'label' => 'انجام به‌موقع', 'text' => 'از میان کارهای انجام‌شده‌ای که مهلت دارند، چند درصد تا روز مهلت تمام شده‌اند؟ کار بدون مهلت در این درصد حساب نمی‌شود. مهلت فعلی ملاک است؛ تغییر آن می‌تواند عدد گزارش قبلی را هم عوض کند.'],
                ['icon' => 'schedule', 'color' => 'tertiary', 'label' => 'زمان معمول انجام', 'text' => 'مدت بین ساخت تا انجام کارها مرتب می‌شود و عدد میانی یا میانگین دو عدد میانی نمایش داده می‌شود؛ به آن «میانه» می‌گویند. مثلاً برای ۲، ۳ و ۲۰ روز، نتیجه ۳ روز است. این مدت، روزهای تقویمی است و ساعت کار واقعی را نشان نمی‌دهد. میانگین هم در اکسل آمده است.'],
                ['icon' => 'verified', 'color' => 'primary', 'label' => 'تأییدهای دریافتی', 'text' => 'تعداد تأییدهایی که شخص دیگری برای کار شما در پروژهٔ نیازمند تأیید ثبت کرده است. تأیید خودکار سامانه، تأیید خودتان و برداشتن تأیید در این عدد حساب نمی‌شوند.'],
                ['icon' => 'trending_up', 'color' => 'secondary', 'label' => 'مقایسه با بازهٔ قبل', 'text' => 'عدد «بازهٔ قبل» مربوط به دوره‌ای با همین تعداد روز، درست پیش از بازهٔ انتخابی است. سبز یعنی بهتر، قرمز یعنی بدتر و خاکستری یعنی تقریباً بدون تغییر. برای زمان انجام، عدد کمتر بهتر است. با زدن این عدد، کل گزارش بازهٔ قبل را ببینید؛ با «بازگشت» برگردید.'],
                ['icon' => 'show_chart', 'color' => 'tertiary', 'label' => 'خط کوچک کنار عددها', 'text' => 'دو مقدارِ بازهٔ قبل و بازهٔ انتخابی را به هم وصل می‌کند. این خط، تغییر روزانه را نشان نمی‌دهد. اگر بازهٔ قبل دست‌کم سه کار انجام‌شده نداشته باشد، مقایسه نمایش داده نمی‌شود.'],
                ['icon' => 'bar_chart', 'color' => 'primary', 'label' => 'نمودار هفتگی', 'text' => 'تعداد کارهای انجام‌شده در هر هفتهٔ بازه را نشان می‌دهد. برای نمایش نمودار، در کل بازه دست‌کم سه کار باید انجام شده باشد.'],
                ['icon' => 'flag', 'color' => 'secondary', 'label' => 'اولویت کارهای انجام‌شده', 'text' => 'عدد کنار هر اولویت، تعداد کارهای انجام‌شده با همان اولویت است. اولویتی که کاری با آن انجام نشده، نمایش داده نمی‌شود.'],
                ['icon' => 'warning', 'color' => 'tertiary', 'label' => 'کارهایی که هنوز پیگیری می‌خواهند', 'text' => 'کار عقب‌افتاده، وظیفه‌ای باز با مهلت گذشته است. در حال انجام و مهلت نزدیک، وضعیت فعلی کارهای مرتبط با گزارش‌اند؛ حتی اگر ماه گذشته را ببینید. مهلت نزدیک یعنی تا سه روز آینده. کارهای بایگانی‌شده یا متوقف در این شمارش‌ها نیستند.'],
            ],
        ],
        'tasks' => [
            'intro' => 'جزئیات کارهای انجام‌شده و کارهای بازِ در حال انجام یا عقب‌افتاده، زیر نام پروژه یا در گروه «وظایف مستقل» آمده است. خالی بودن این بخش به معنی خالی بودن کل پروژه نیست.',
            'rows' => [
                ['icon' => 'workspaces', 'color' => 'secondary', 'label' => 'باز کردن جدول کارها', 'text' => 'روی ردیف خلاصه بزنید تا جدول کارهای آن پروژه باز شود. «وظایف مستقل» به هیچ پروژه‌ای وصل نیستند. «فقط جدول این پروژه» فقط همین فهرست جزئیات را محدود می‌کند؛ خلاصه، فعالیت‌ها و اکسل همچنان کل گزارش‌اند.'],
                ['icon' => 'military_tech', 'color' => 'tertiary', 'label' => 'کارهای برجستهٔ این بازه', 'text' => '«کار شاخص» از ترکیب اولویت و مدت تقویمی انتخاب می‌شود؛ معیار قطعی سختی کار نیست. «کوتاه‌ترین زمان انجام» کارهای ساخته و تمام‌شده در یک روز را کنار می‌گذارد. «بیشترین نظر همکاران» بر اساس تعداد نظرهای دیگران در همین بازه است.'],
                ['icon' => 'expand', 'color' => 'primary', 'label' => 'جزئیات هر کار', 'text' => 'عنوان، اولویت، وضعیت فعلی، مهلت و تاریخ انجام را می‌بینید. مدت انجام از زمان ساخت وظیفه حساب می‌شود. علامت خط تیره یعنی برای آن قسمت اطلاعات کافی نیست.'],
                ['icon' => 'timeline', 'color' => 'secondary', 'label' => 'سابقهٔ فعالیت‌ها', 'text' => 'بخش «فعالیت‌ها» را باز کنید تا تغییر وضعیت، تأیید، تعیین مسئول و نظرهای مرتبط با این بازه را ببینید. تازه‌ترین مورد بالاتر است؛ برای موارد قدیمی‌تر «بارگذاری بیشتر» را بزنید.'],
            ],
        ],
        'sharing' => [
            'intro' => 'بازهٔ گزارش را بالای صفحه انتخاب کنید. اگر می‌خواهید گیرنده دقیقاً همان تاریخ‌ها را ببیند، «بازهٔ دلخواه» را انتخاب کنید. گزینه‌هایی مثل «این ماه» بر اساس زمان باز کردن پیوند محاسبه می‌شوند. در گوشی، «خروجی اکسل» و «چاپ گزارش» در منوی «⋯» کنار عنوان هستند.',
            'rows' => [
                ['icon' => 'forward_to_inbox', 'color' => 'primary', 'label' => 'گزارش برای چه کسی فرستاده می‌شود؟', 'text' => 'از دکمهٔ «اشتراک‌گذاری گزارش»، گیرنده را انتخاب کنید. «مدیر سازمانی» از مدیران واحد فرد انتخاب می‌شود؛ برای مدیر یک واحد، مدیر واحد بالاتر در نظر گرفته می‌شود. برای فرستادن به شخص مشخص، «کاربر دیگر» را انتخاب کنید.'],
                ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'پیوند گزارش و اعتبار آن', 'text' => 'گیرنده در زنگوله، اعلان و پیوندی با اعتبار ۷ روز می‌گیرد. پیوند به حساب او محدود نیست؛ هر کاربر واردشده‌ای که آن را داشته باشد می‌تواند گزارش را با محدودیت‌های دسترسی خودش ببیند. گزارش نسخهٔ ثابت نیست و با تغییر اطلاعات ممکن است عددهایش عوض شوند.'],
                ['icon' => 'visibility', 'color' => 'tertiary', 'label' => 'گیرنده چه چیزی می‌بیند؟', 'text' => 'گیرنده می‌تواند گزارش، جدول‌ها و فعالیت‌ها را ببیند؛ نمی‌تواند بازه را عوض کند یا از همین صفحه اکسل بگیرد. متن نظرها و پیوست‌ها پنهان است. عنوان کارهای شخصی و پروژه‌هایی که به آن‌ها دسترسی ندارد نیز پنهان می‌شود.'],
                ['icon' => 'print', 'color' => 'primary', 'label' => 'چاپ چه چیزهایی را شامل می‌شود؟', 'text' => 'دکمهٔ چاپ، جدول‌های بسته را باز می‌کند و پیش‌نمایش چاپ را نشان می‌دهد. خلاصه، کارهای برجسته و جدول‌ها چاپ می‌شوند؛ فهرست فعالیت‌ها در چاپ نیست. اگر جدول یک پروژه را انتخاب کرده‌اید، فقط همان جدول در چاپ می‌آید.'],
                ['icon' => 'grid_on', 'color' => 'secondary', 'label' => 'فایل اکسل چه چیزی دارد؟', 'text' => 'اکسل شامل خلاصه، کارهای برجسته، روند هفتگی و جزئیات همهٔ گروه‌های گزارش است. بازه، همان بازهٔ نمایشی است؛ انتخاب «فقط جدول این پروژه» اکسل را محدود نمی‌کند. فهرست فعالیت‌ها در فایل اکسل نیست.'],
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
        'اگر مقایسه را نمی‌بینید، ممکن است بازهٔ قبل کمتر از سه کار انجام‌شده داشته باشد. این حالت به معنی صفر بودن عملکرد قبلی نیست.',
        'اگر همین حالا کاری را تمام کرده‌اید و عدد تغییر نکرده، کمی صبر کنید و صفحه را دوباره باز کنید. گزارش ممکن است با کمی تأخیر تازه شود.',
        'کارهای مرتبط با فرد شامل مسئولیت‌های خودش، ساخته‌های بدون مسئول دیگر و کارهایی است که همکارشان است. انجام کار به نام مسئول زمان انجام ثبت می‌شود؛ همکاری یا نظر دادن به‌تنهایی آن را به نام شما ثبت نمی‌کند.',
        'ماه و فصل بر اساس تقویم شمسی‌اند. بازهٔ دلخواه حداکثر ۳۶۵ روز است. دیدن بازهٔ قبل موقتی است؛ پیوند اشتراک با انتخاب اصلی بازه ساخته می‌شود.',
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
