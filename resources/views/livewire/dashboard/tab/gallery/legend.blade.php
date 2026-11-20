@php
    $tabs = [
        ['id' => 'access', 'icon' => 'lock', 'label' => 'دسترسی'],
        ['id' => 'timeline', 'icon' => 'view_carousel', 'label' => 'تایم‌لاین'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $accessRows = [
        ['icon' => 'public', 'color' => 'sapphire', 'label' => 'عمومی', 'text' => 'این گالری برای همهٔ همکاران قابل دید است؛ واحد سازمانی ندارد.'],
        ['icon' => 'lock', 'color' => 'gold', 'label' => 'تک‌واحدی', 'text' => 'فقط همکارانِ همان واحد سازمانی این گالری را می‌بینند.'],
        ['icon' => 'groups', 'color' => 'amethyst', 'label' => 'چندواحدی', 'text' => 'همکارانِ همهٔ واحدهای اشتراک‌داده‌شده این گالری را می‌بینند.'],
    ];

    $timelineRows = [
        ['icon' => 'view_carousel', 'color' => 'primary', 'label' => 'نمای خط زمانی', 'text' => 'گالری‌ها با پیمایش افقی نمایش داده می‌شوند. دکمهٔ «مخفی/نمایش تایم‌لاین» خط زمان را پنهان یا نمایان می‌کند. تاریخ رویداد زیر نشان هر کارت قرار دارد و با نگه‌داشتن نشانگر روی کارت، نوع محتوا نیز مشخص می‌شود. در رایانه، کارت انتخاب‌شده بزرگ‌تر است؛ در موبایل کارت‌ها هم‌اندازه‌اند.'],
        ['icon' => 'calendar_month', 'color' => 'secondary', 'label' => 'فیلتر ماه', 'text' => 'دکمهٔ تقویم بالای تایم‌لاین، ماه‌های موجود را از تاریخ رویداد می‌سازد؛ انتخاب یک ماه فقط گالری‌های همان ماه را نشان می‌دهد و «همه ماه‌ها» فیلتر را پاک می‌کند.'],
        ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'حالت تمرکز', 'text' => 'وقتی از پالت دستور مستقیم به یک گالری می‌روید، فقط همان رکورد نمایش داده می‌شود؛ دکمهٔ «نمایش همه» فهرست کامل را برمی‌گرداند.'],
        ['icon' => 'bookmark', 'color' => 'secondary', 'label' => 'نشان کردن گالری', 'text' => 'در هر دو نمای گالری، «نشان کردن» روی کارت در دسترس است. برای دیدن مواردی که نشان کرده‌اید، «فقط نشان‌شده‌ها» را روشن کنید. نشان‌ها در همین مرورگر می‌مانند.'],
        ['icon' => 'grid_view', 'color' => 'tertiary', 'label' => 'نمای دیوار', 'text' => 'با دکمهٔ نمای دیوار، گالری‌ها در ستون‌های فشرده نمایش داده می‌شوند. عنوان و تاریخ زیر تصویر و تعداد تصاویر بیشتر با نشان «+N» مشخص می‌شود. نام واحد با نگه‌داشتن نشانگر روی تصویر ظاهر می‌شود. برای مشاهدهٔ فایل‌ها در اندازهٔ کامل، تصویر را انتخاب کنید. پیکان‌های چپ و راست فقط در نمای خط زمانی در دسترس‌اند.'],
    ];

    $notes = [
        'ادمین می‌تواند گالری را بدون حذف مخفی کند؛ گالری مخفی در فهرست، جستجو و لینک مستقیم دیده نمی‌شود.',
        'هر کارت تا ۳ تصویر را به‌صورت کلاژ نشان می‌دهد و بقیه با نشان «+N» پنهان می‌شوند؛ کلیک روی تصویر، لایت‌باکس همهٔ فایل‌ها (تصویر و ویدیو) را باز می‌کند.',
        'ویدیوها با بردنِ نشانگر موس روی کارت، پیش‌نمایش پخش می‌شوند و مدت‌زمانشان در گوشه نمایش داده می‌شود.',
        'بارگذاریِ گالری‌های بیشتر به‌صورت خودکار با اسکرول به انتها انجام می‌شود؛ وقتی رکوردی نماند، نشانِ بارگذاری محو می‌گردد.',
        'عنوان و توضیحاتِ طولانی روی کارت با دکمهٔ «مشاهده بیشتر» کامل باز می‌شوند.',
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
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">آیکونِ گوشهٔ هر کارت، نوع دسترسیِ گالری را نشان می‌دهد؛ شما فقط گالری‌های عمومی یا آن‌هایی که واحدِ سازمانیِ شما در آن‌ها اشتراک دارد را می‌بینید.</p>
        @foreach($accessRows as $row)
            @php
                $chipClasses = match ($row['color']) {
                    'sapphire' => 'bg-[var(--tool-sapphire-bg)] text-[var(--tool-sapphire-color)]',
                    'gold' => 'bg-[var(--tool-gold-bg)] text-[var(--tool-gold-color)]',
                    'amethyst' => 'bg-[var(--tool-amethyst-bg)] text-[var(--tool-amethyst-color)]',
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
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">تایم‌لاین افقی، گالری‌ها را به ترتیب تاریخ رویداد (نزولی) نشان می‌دهد.</p>
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
