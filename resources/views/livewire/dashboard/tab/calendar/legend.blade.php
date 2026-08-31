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
        ['icon' => 'event_seat', 'color' => 'sage', 'label' => 'از طریق رزرو', 'text' => 'رزروهای فعال شما در همهٔ منابع (صندلی، اسپات، خودرو، جلسه و...) به‌صورت خصوصی در تقویمتان نشان داده می‌شوند؛ تغییر یا لغو فقط از تب «رزرو».'],
        ['icon' => 'alarm', 'color' => 'error', 'label' => 'یادآوری', 'text' => 'در صورت تنظیم یادآوری، شمارش معکوس در ابزار تایمر فعال می‌شود و در لحظهٔ شروع رویداد زنگ هشدار به‌صدا درمی‌آید.'],
    ];

    $scheduleGroups = [
        'drag' => [
            ['icon' => 'drag_indicator', 'color' => 'amethyst', 'label' => 'کشیدن و رها کردن', 'text' => 'فقط رویدادهای شخصی و غیر رزروی با کشیدن عمودی (تغییر ساعت) جابجا می‌شوند؛ کلیک جابجا نمی‌کند. رزرو، اشتراقی و مجازی فقط‌خواندنی‌اند.'],
            ['icon' => 'open_in_full', 'color' => 'amethyst', 'label' => 'تغییر مدت', 'text' => 'لبهٔ پایینی رویدادهای شخصی و غیر رزروی را بکشید تا مدت آن کوتاه یا بلند شود؛ گام ۱۵ دقیقه و حداقل ۱۵ و حداکثر ۸ ساعت است و تا پایان روز محدود می‌ماند.'],
            ['icon' => 'edit', 'color' => 'amethyst', 'label' => 'کلیک برای ویرایش', 'text' => 'در نمای هفتگی/روزانه، کلیک روی رویداد شخصی و غیر رزروی فرم ویرایش را باز می‌کند؛ آیکون مداد هنگام نگه‌داشتن اشاره‌گر ظاهر می‌شود. رویدادهای رزرو با کلیک پیام هدایت به تب «رزرو» نشان می‌دهند و رویدادهای اشتراکی دیگران واکنشی ندارند.'],
            ['icon' => 'schedule', 'color' => 'error', 'label' => 'خط اکنون', 'text' => 'خط افقی زمان لحظه‌ای را نشان می‌دهد و هر دقیقه به‌روز می‌شود؛ در نیمه‌شب تقویم یک‌بار به‌روز می‌شود.'],
        ],
        'display' => [
            ['icon' => 'event_seat', 'color' => 'sage', 'label' => 'بلوک و نوار رزرو', 'text' => 'رزروهای فعال شما در نمای هفتگی/روزانه به‌صورت بلوک‌های سبز فقط‌خواندنی نشان داده می‌شوند: رزروهای ساعتی مانند رویدادها در شبکهٔ زمان می‌نشینند و رزروهای تمام‌روز یا چندروزه به‌صورت نوار پیوسته در بالای ستون‌ها. قابل جابجایی یا تغییر نیستند.'],
            ['icon' => 'cake', 'color' => 'gold', 'label' => 'بنرهای تمام‌روز', 'text' => 'تولدها، سالگردها و تعطیلی‌ها به‌صورت بنرهای تمام‌روز در بالای نمای هفتگی/روزانه نمایش داده می‌شوند.'],
            ['icon' => 'event', 'color' => 'neutral', 'label' => 'ستون‌بندی همپوشانی', 'text' => 'رویدادهای هم‌زمان به‌صورت ستون‌های کنار هم (نه روی هم) چیده می‌شوند و ارتفاع هر رویداد متناسب با مدت آن است؛ رویداد بلندتر در ستون اول می‌نشیند.'],
            ['icon' => 'nightlight', 'color' => 'sapphire', 'label' => 'برش نیمه‌شب', 'text' => 'رویدادهای نمایش‌داده‌شده در بازهٔ ۰۶:۰۰ تا ۲۴:۰۰ قرار دارند؛ ساعات پیش از ۰۶:۰۰ در نوار جداگانه نمایش داده می‌شوند.'],
            ['icon' => 'more_time', 'color' => 'neutral', 'label' => 'بازهٔ زمانی', 'text' => 'رویدادهای بلندتر از ۳۰ دقیقه ساعت شروع و پایان را نشان می‌دهند (مثلاً ۰۹:۰۰–۱۰:۳۰)؛ رویدادهای ۳۰ دقیقه‌ای یا کوتاه‌تر فقط ساعت شروع را نشان می‌دهند تا در بلوک کوچک جا شود.'],
        ],
        'view' => [
            ['icon' => 'date_range', 'color' => 'sapphire', 'label' => 'پرش سریع تاریخ', 'text' => 'روی نام بازهٔ زمانی در سربرگ تقویم (نام ماه یا بازهٔ هفتگی/روزانه) ضربه بزنید تا یک تقویم کوچک باز شود؛ با پیکان‌های ماه و سال جابجا شوید و روی هر روز ضربه بزنید تا تقویم مستقیم به همان روز پرش کند.'],
            ['icon' => 'visibility_off', 'color' => 'neutral', 'label' => 'مخفی کردن جمعه', 'text' => 'در نمای هفتگی می‌توانید ستون جمعه (تعطیل) را مخفی کنید؛ دامنهٔ داده همچنان ۷ روز است.'],
            ['icon' => 'smartphone', 'color' => 'neutral', 'label' => 'حالت موبایل', 'text' => 'در صفحه‌های کوچک‌تر از ۷۶۸ پیکسل، نمای هفتگی/روزانه به فهرست روزانه با ضربه برای ویرایش تبدیل می‌شود (بدون کشیدن).'],
            ['icon' => 'open_in_full', 'color' => 'neutral', 'label' => 'بزرگ‌نمایی', 'text' => 'با دکمهٔ بزرگ‌نمایی، نمای هفتگی/روزانه تمام‌صفحه می‌شود؛ برای خروج Esc را بزنید یا روی پس‌زمینه ضربه بزنید.'],
        ],
    ];

    $notes = [
        'راهنمای آیکون‌های روز (تعطیل/تولد/سالگرد/رویداد/مشترک) زیر شبکهٔ تقویم نمایش داده می‌شود؛ این بخش قوانین دیدن و ویرایش رویدادها را توضیح می‌دهد.',
        'به‌اشتراک‌گذاری یک رویداد خصوصی آن را فقط برای گیرندگان انتخاب‌شده قابل مشاهده می‌کند؛ رویداد همچنان از دید بقیه پنهان می‌ماند.',
        'جابجایی و تغییر مدت فقط با کشیدن واقعی (نه کلیک) و با ۱۵ دقیقه گام انجام می‌شود؛ در صورت تغییر هم‌زمان توسط کاربر دیگر، تغییر شما خودکار برگردانده می‌شود.',
    ];

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
        @foreach($tabs as $tab)
            <button
                type="button"
                @click="tab = '{{ $tab['id'] }}'"
                :class="tab === '{{ $tab['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[17px]">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'rules'" x-cloak class="space-y-2">
        @foreach($ruleRows as $row)
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-3 py-2.5">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($row['color']) }}">
                    <span class="material-symbols-rounded text-[15px]">{{ $row['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                    <p class="text-[12px] leading-5 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'schedule'" x-cloak class="space-y-3">
        <div class="flex flex-wrap p-1 bg-[var(--md-sys-color-surface-container-high)]/50 rounded-xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach($subTabs as $sub)
                <button
                    type="button"
                    @click="sub = '{{ $sub['id'] }}'"
                    :class="sub === '{{ $sub['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[12px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[15px]">{{ $sub['icon'] }}</span>
                    {{ $sub['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($scheduleGroups as $subId => $rows)
            <div x-show="tab === 'schedule' && sub === '{{ $subId }}'" x-cloak class="space-y-2">
                @foreach($rows as $row)
                    <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-3 py-2.5">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($row['color']) }}">
                            <span class="material-symbols-rounded text-[15px]">{{ $row['icon'] }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                            <p class="text-[12px] leading-5 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>