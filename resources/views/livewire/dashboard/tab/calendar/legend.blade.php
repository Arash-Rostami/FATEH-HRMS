@php
    $tabs = [
        ['id' => 'rules', 'icon' => 'visibility', 'label' => 'دیدن و ویرایش'],
        ['id' => 'schedule', 'icon' => 'schedule', 'label' => 'زمان‌بندی'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $subTabs = [
        ['id' => 'drag', 'icon' => 'drag_indicator', 'label' => 'کشیدن'],
        ['id' => 'display', 'icon' => 'view_week', 'label' => 'نوار و بنر'],
        ['id' => 'view', 'icon' => 'view_quilt', 'label' => 'نما و بزرگ‌نمایی'],
    ];

    $ruleRows = [
        ['icon' => 'lock', 'color' => 'gold', 'label' => 'رویداد خصوصی', 'text' => 'فقط شما و کسانی که مستقیماً با آن‌ها به اشتراک گذاشته‌اید می‌بینند.'],
        ['icon' => 'public', 'color' => 'sapphire', 'label' => 'رویداد عمومی', 'text' => 'همهٔ کاربران این رویداد را در تقویم خود می‌بینند.'],
        ['icon' => 'edit', 'color' => 'amethyst', 'label' => 'مالکیت و ویرایش', 'text' => 'دکمه‌های «اشتراک‌گذاری»، «ویرایش» و «حذف» فقط برای سازندهٔ رویداد است؛ سایر کاربران فقط می‌بینند.'],
        ['icon' => 'event_seat', 'color' => 'sage', 'label' => 'از طریق رزرو', 'text' => 'رزروهای فعال شما در همهٔ منابع (صندلی، اسپات، خودرو، جلسه و...) به‌صورت خصوصی در تقویمتان نشان داده می‌شوند؛ تغییر یا لغو فقط از تب «رزرو». رزروهای بلندمدت (۷ روز یا بیشتر) فقط در روز شروع و پایان نشانه‌گذاری می‌شوند — نه هر روز — تا شلوغ نشوید؛ عدد کوچک کنار نشانهٔ روز شروع در تقویم ماهانه، تعداد روزهای رزرو را نشان می‌دهد و در فهرست رویدادهای همان روز، برچسب «از طریق رزرو» تاریخ پایان را هم نشان می‌دهد.'],
        ['icon' => 'alarm', 'color' => 'error', 'label' => 'یادآوری', 'text' => 'در صورت تنظیم یادآوری، شمارش معکوس در ابزار تایمر فعال می‌شود و در لحظهٔ شروع رویداد زنگ هشدار به‌صدا درمی‌آید.'],
        ['icon' => 'alarm', 'color' => 'sapphire', 'label' => 'یادآوری (ابزار بالای صفحه)', 'text' => 'یادآورهایی که از زنگ بالای صفحه ساخته‌اید، همراه با ساعت سررسیدشان به‌صورت فقط‌خواندنی روی تقویم نشان داده می‌شوند — حتی اگر اعلانشان موقتاً خاموش باشد؛ سررسید هرگز تغییر نمی‌کند، فقط اعلان به‌تعویق می‌افتد. در این حالت تعداد ساعت باقی‌مانده تا روشن‌شدن دوبارهٔ اعلان به‌صورت برچسب متنی کوچک کنارشان دیده می‌شود.'],
    ];

    $scheduleGroups = [
        'drag' => [
            ['icon' => 'drag_indicator', 'color' => 'amethyst', 'label' => 'تغییر ساعت با کشیدن', 'text' => 'در نمای روزانه و هفتگی، رویداد شخصی خود را به بالا یا پایین بکشید تا ساعت آن تغییر کند. رزروها، رویدادهای دیگران و مناسبت‌ها از این طریق قابل تغییر نیستند.'],
            ['icon' => 'open_in_full', 'color' => 'amethyst', 'label' => 'تغییر مدت', 'text' => 'لبهٔ پایینی رویدادهای شخصی و غیر رزروی را بکشید تا مدت آن کوتاه یا بلند شود؛ گام ۱۵ دقیقه و حداقل ۱۵ و حداکثر ۸ ساعت است و تا پایان روز محدود می‌ماند.'],
            ['icon' => 'edit', 'color' => 'amethyst', 'label' => 'کلیک برای ویرایش', 'text' => 'در نمای هفتگی/روزانه، کلیک روی رویداد شخصی و غیر رزروی فرم ویرایش را باز می‌کند؛ آیکون مداد هنگام نگه‌داشتن اشاره‌گر ظاهر می‌شود. رویدادهای رزرو با کلیک پیام هدایت به تب «رزرو» نشان می‌دهند و رویدادهای اشتراکی دیگران واکنشی ندارند.'],
            ['icon' => 'schedule', 'color' => 'error', 'label' => 'خط اکنون', 'text' => 'خط افقی زمان لحظه‌ای را نشان می‌دهد و هر دقیقه به‌روز می‌شود؛ در نیمه‌شب تقویم یک‌بار به‌روز می‌شود. نمای روزانه هنگام باز شدن خودکار تا ساعت اکنون (برای امروز) یا اولین رویداد همان روز اسکرول می‌شود.'],
        ],
        'display' => [
            ['icon' => 'event_seat', 'color' => 'sage', 'label' => 'بلوک و نوار رزرو', 'text' => 'رزروهای فعال شما در نمای هفتگی/روزانه به‌صورت بلوک‌های سبز فقط‌خواندنی نشان داده می‌شوند: رزروهای ساعتی مانند رویدادها در شبکهٔ زمان می‌نشینند و رزروهای تمام‌روز یا چندروزه به‌صورت نوار پیوسته در بالای ستون‌ها. قابل جابجایی یا تغییر نیستند.'],
            ['icon' => 'cake', 'color' => 'amethyst', 'label' => 'بنرهای تمام‌روز', 'text' => 'تولدها، سالگردها و تعطیلی‌ها به‌صورت بنرهای تمام‌روز در بالای نمای هفتگی/روزانه نمایش داده می‌شوند. در نمای روزانه، اگر بیش از ۳ بنر باشد، بقیه پشت دکمهٔ «+ مورد دیگر» جمع می‌شوند و روزی که بنری ندارد ردیف خالی نمی‌گیرد.'],
            ['icon' => 'event', 'color' => 'neutral', 'label' => 'ستون‌بندی همپوشانی', 'text' => 'رویدادهای هم‌زمان به‌صورت ستون‌های کنار هم (نه روی هم) چیده می‌شوند و ارتفاع هر رویداد متناسب با مدت آن است؛ رویداد بلندتر در ستون اول می‌نشیند.'],
            ['icon' => 'nightlight', 'color' => 'sapphire', 'label' => 'برش نیمه‌شب', 'text' => 'رویدادهای نمایش‌داده‌شده در بازهٔ ۰۶:۰۰ تا ۲۴:۰۰ قرار دارند؛ ساعات پیش از ۰۶:۰۰ در نوار جداگانه نمایش داده می‌شوند.'],
            ['icon' => 'more_time', 'color' => 'neutral', 'label' => 'بازهٔ زمانی', 'text' => 'رویدادهای بلندتر از ۳۰ دقیقه ساعت شروع و پایان را نشان می‌دهند (مثلاً ۰۹:۰۰–۱۰:۳۰)؛ رویدادهای ۳۰ دقیقه‌ای یا کوتاه‌تر فقط ساعت شروع را نشان می‌دهند تا در بلوک کوچک جا شود.'],
        ],
        'view' => [
            ['icon' => 'date_range', 'color' => 'sapphire', 'label' => 'پرش سریع تاریخ', 'text' => 'روی نام بازهٔ زمانی در سربرگ تقویم (نام ماه یا بازهٔ هفتگی/روزانه) ضربه بزنید تا یک تقویم کوچک باز شود؛ با پیکان‌های ماه و سال جابجا شوید و روی هر روز ضربه بزنید تا تقویم مستقیم به همان روز پرش کند. در نمای ماهانه، پیکان‌های دو طرف نام ماه، ماه را عوض می‌کنند و پیکان‌های کنار «امروز» یک روز جابجا می‌شوند (در نمای هفتگی یک هفته، در نمای روزانه یک روز).'],
            ['icon' => 'visibility_off', 'color' => 'neutral', 'label' => 'مخفی کردن جمعه', 'text' => 'در نمای هفتگی می‌توانید ستون جمعه را پنهان کنید. این کار فقط نمایش ستون را تغییر می‌دهد و رویدادهای آن روز حذف نمی‌شوند.'],
            ['icon' => 'smartphone', 'color' => 'neutral', 'label' => 'حالت موبایل', 'text' => 'در صفحه‌های کوچک‌تر از ۷۶۸ پیکسل، نمای هفتگی/روزانه به فهرست روزانه با ضربه برای ویرایش تبدیل می‌شود (بدون کشیدن).'],
            ['icon' => 'open_in_full', 'color' => 'neutral', 'label' => 'بزرگ‌نمایی', 'text' => 'با دکمهٔ بزرگ‌نمایی، نمای هفتگی/روزانه تمام‌صفحه می‌شود؛ برای خروج Esc را بزنید یا روی پس‌زمینه ضربه بزنید.'],
        ],
    ];

    $notes = [
        ['sms' => true, 'icon' => 'sms', 'color' => 'sapphire', 'title' => 'پیامک تبریک هوشمند', 'text' => 'کنار تولد و سالگرد همکاران واجد شرایط در سال شمسی جاری، دکمهٔ پیامک دیده می‌شود. کادر متن در ابتدا خالی است؛ «متن دلخواه» برای نوشتن پیام خودتان و «ساخت هوشمند» برای پر کردن یک متن آماده است (هر بار کلیک، متن بعدی) و پس از پر شدن قابل ویرایش است. زبانهٔ «پیش‌نمایش موبایل» پیام نهایی را روی صفحهٔ یک گوشی، با نام شما در ابتدا و نام سامانه در انتها، همراه با تعداد بخش‌های پیامک نشان می‌دهد. دکمه برای تولد یا سالگرد خودتان و برای همکاری که شماره موبایل ثبت‌نشده، غیرفعال/معلق یا مهمان باشد یا در مرخصی/مأموریت به سر ببرد، دیده نمی‌شود.'],
        ['icon' => 'filter_list', 'color' => 'amethyst', 'title' => 'فیلتر هوشمند روز', 'text' => 'اگر در یک روز دو نوع رویداد یا بیشتر باشد، بالای پنل کناری دکمه‌های فیلتر (همه، تولد، سالگرد، تعطیلات، یادآور، رویداد) ظاهر می‌شود و بدون بارگذاری دوباره، فقط همان نوع را نشان می‌دهد.'],
        ['sms' => true, 'icon' => 'check_circle', 'color' => 'sage', 'title' => 'وضعیت «ارسال شد»', 'text' => 'پس از ارسال موفق، دکمهٔ همان مناسبت در همین مرورگر پنهان می‌شود (در پنل کناری، به‌جای آن نشان «ارسال شد» دیده می‌شود). این یادآوری برای هر حساب و سال شمسی جداست؛ با پاک‌کردن حافظهٔ مرورگر یا استفاده از دستگاه دیگر، دکمه دوباره دیده می‌شود. ارسال ناموفق دکمه را پنهان نمی‌کند.'],
        ['icon' => 'palette', 'color' => 'gold', 'title' => 'راهنمای آیکون‌های تقویم', 'text' => 'راهنمای آیکون‌های روز (تعطیل/تولد/سالگرد/رویداد/مشترک) زیر شبکهٔ تقویم نمایش داده می‌شود؛ این بخش قوانین دیدن و ویرایش رویدادها را توضیح می‌دهد.'],
        ['icon' => 'share', 'color' => 'sapphire', 'title' => 'حریم خصوصی اشتراک‌گذاری', 'text' => 'به‌اشتراک‌گذاری یک رویداد خصوصی آن را فقط برای گیرندگان انتخاب‌شده قابل مشاهده می‌کند؛ رویداد همچنان از دید بقیه پنهان می‌ماند.'],
        ['icon' => 'sync_problem', 'color' => 'error', 'title' => 'گام تغییر و تداخل هم‌زمان', 'text' => 'جابجایی و تغییر مدت فقط با کشیدن واقعی (نه کلیک) و با ۱۵ دقیقه گام انجام می‌شود؛ در صورت تغییر هم‌زمان توسط کاربر دیگر، تغییر شما خودکار برگردانده می‌شود.'],
        ['icon' => 'notifications_paused', 'color' => 'neutral', 'title' => 'یادآورهای فقط‌خواندنی', 'text' => 'یادآورهای شما (از ابزار یادآوری در نوار بالا) در روز سررسید به‌صورت فقط‌خواندنی روی تقویم نشان داده می‌شوند؛ قابل کشیدن، ویرایش یا حذف از همین‌جا نیستند.'],
    ];

    $notes = array_values(array_filter($notes, fn ($note) => ! (($note['sms'] ?? false) && tenantOff('notify:sms'))));

    $chipClasses = function ($color) {
        return match ($color) {
            'sapphire' => 'bg-[var(--tool-sapphire-bg)] text-[var(--tool-sapphire-color)]',
            'gold' => 'bg-[var(--tool-gold-bg)] text-[var(--tool-gold-color)]',
            'amethyst' => 'bg-[var(--tool-amethyst-bg)] text-[var(--tool-amethyst-color)]',
            'sage' => 'bg-[var(--tool-sage-bg)] text-[var(--tool-sage-color)]',
            'error' => 'bg-[var(--md-sys-color-error-container)]/50 text-[var(--md-sys-color-error)]',
            'neutral' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]',
        };
    };
@endphp

<div x-data="{ tab: 'rules', sub: 'drag' }">
    <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $t)
            <button
                type="button"
                @click="tab = '{{ $t['id'] }}'"
                :class="tab === '{{ $t['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[17px]">{{ $t['icon'] }}</span>
                <span>{{ $t['label'] }}</span>
            </button>
        @endforeach
    </div>

    {{-- TAB 1: Rules --}}
    <div x-show="tab === 'rules'" x-cloak class="space-y-2">
        @foreach($ruleRows as $row)
            @php $isLong = mb_strlen($row['text']) > 190; @endphp
            <div
                x-data="{ expanded: false }"
                @if($isLong) @click="expanded = !expanded" @endif
                class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-3 py-2.5 transition-colors {{ $isLong ? 'cursor-pointer hover:border-[var(--md-sys-color-primary)]/40' : '' }}"
            >
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($row['color']) }}">
                    <span class="material-symbols-rounded text-[15px]">{{ $row['icon'] }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2 mb-0.5">
                        <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                        @if($isLong)
                            <span class="text-[10px] font-semibold text-[var(--md-sys-color-primary)] flex items-center gap-0.5 shrink-0">
                                <span x-text="expanded ? 'بستن' : 'بیشتر'"></span>
                                <span class="material-symbols-rounded text-[13px] transition-transform duration-200" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                            </span>
                        @endif
                    </div>
                    <p class="text-[12px] leading-5 text-[var(--md-sys-color-on-surface-variant)]" :class="(!expanded && {{ $isLong ? 'true' : 'false' }}) ? 'line-clamp-2' : ''">
                        {{ $row['text'] }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- TAB 2: Schedule --}}
    <div x-show="tab === 'schedule'" x-cloak class="space-y-3">
        <div class="flex flex-wrap p-1 bg-[var(--md-sys-color-surface-container-high)]/50 rounded-xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach($subTabs as $s)
                <button
                    type="button"
                    @click="sub = '{{ $s['id'] }}'"
                    :class="sub === '{{ $s['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[12px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[15px]">{{ $s['icon'] }}</span>
                    {{ $s['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($scheduleGroups as $subId => $rows)
            <div x-show="sub === '{{ $subId }}'" x-cloak class="space-y-2">
                @foreach($rows as $row)
                    <div
                        class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-3 py-2.5"
                    >
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($row['color']) }}">
                            <span class="material-symbols-rounded text-[15px]">{{ $row['icon'] }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                            <p class="text-[12px] leading-5 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- TAB 3: Scannable Categorized Notes --}}
    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            @php $isLongNote = mb_strlen($note['text']) > 190; @endphp
            <div
                x-data="{ expanded: false }"
                @if($isLongNote) @click="expanded = !expanded" @endif
                class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-3 py-2.5 transition-colors {{ $isLongNote ? 'cursor-pointer hover:border-[var(--md-sys-color-primary)]/40' : '' }}"
            >
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($note['color']) }}">
                    <span class="material-symbols-rounded text-[15px]">{{ $note['icon'] }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2 mb-0.5">
                        <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $note['title'] }}</p>
                        @if($isLongNote)
                            <span class="text-[10px] font-semibold text-[var(--md-sys-color-primary)] flex items-center gap-0.5 shrink-0">
                                <span x-text="expanded ? 'بستن' : 'بیشتر'"></span>
                                <span class="material-symbols-rounded text-[13px] transition-transform duration-200" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                            </span>
                        @endif
                    </div>
                    <p class="text-[12px] leading-5 text-[var(--md-sys-color-on-surface-variant)]" :class="(!expanded && {{ $isLongNote ? 'true' : 'false' }}) ? 'line-clamp-2' : ''">
                        {{ $note['text'] }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>
</div>
