@php
    $tabs = [
        [
            'icon' => 'list',
            'label' => 'همه',
            'badge' => null,
            'hint' => 'تمام اعتبارنامه‌ها (در محدودهٔ دسترسی شما) در یک فهرست، مرتب بر اساس تاریخ ایجاد (نزولی). بدون فیلتر اضافه.',
        ],
        [
            'icon' => 'link',
            'label' => 'دارای لینک',
            'badge' => 'success',
            'hint' => 'فقط اعتبارنامه‌هایی که فیلد «لینک ورود» پر شده است (whereNotNull روی link). شمارش این زبانه یک نشان سبز (success) می‌گیرد.',
        ],
        [
            'icon' => 'link_off',
            'label' => 'بدون لینک',
            'badge' => 'gray',
            'hint' => 'فقط اعتبارنامه‌هایی که لینک ورود ندارند (whereNull روی link) — مثلاً سامانه‌هایی با ورود از درونِ شبکه. شمارش این زبانه یک نشان خاکستری (gray) می‌گیرد.',
        ],
    ];
    $filters = [
        ['label' => 'دارای لینک', 'hint' => 'فیلتر سه‌حالته (TernaryFilter) روی ستون link: فقط دارای لینک / فقط بدون لینک / هر دو. مستقل از زبانه‌ها کار می‌کند.'],
        ['label' => 'کاربر (user_id)', 'hint' => 'فیلتر بر اساس صاحبِ اعتبارنامه — فقط برای ادمین‌های با نقش ارتقا‌یافته قابل مشاهده است. جستجو در لیست کاربران با preload.'],
        ['label' => 'بازه تاریخ ایجاد', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد اعتبارنامه (از تاریخ / تا تاریخ).'],
    ];
    $groups = [
        ['label' => 'بر اساس نام سامانه', 'hint' => 'اعتبارنامه‌ها را بر اساس app_name گروه‌بندی می‌کند — برای دیدنِ چندین حسابِ یک سامانه کنار هم. گروه قابل جمع‌شدن است.'],
        ['label' => 'بر اساس کاربر', 'hint' => 'اعتبارنامه‌ها را بر اساس کاربرِ صاحب گروه‌بندی می‌کند. عنوان گروه نام کاربر است و اگر صاحب حذف‌شده باشد «-» نشان داده می‌شود.'],
    ];
    $badgeClasses = fn(?string $badge): string => match ($badge) {
        'success' => 'bg-[var(--md-sys-color-success-container)] text-[var(--md-sys-color-on-success-container)]',
        'gray' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]',
        default => '',
    };
    $d3 = convertToPersian('3');
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tab</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">{{ $d3 }} زبانهٔ بالای فهرست، اعتبارنامه‌ها را بر اساس لینک دسته‌بندی می‌کنند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانه‌ها با کوئریِ پایه ترکیب می‌شوند و شمارش نشان‌ها یک‌بار در هر بارگذاری با مکانیزم once کش می‌شود — یعنی هر سه زبانه فقط یک کوئریِ آماری می‌زنند. زبانه‌ها را می‌توان از تنظیمات کاربری (show_list_tabs) خاموش کرد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">list</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سه زبانه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                            @if($t['badge'])
                                <span @class([
                                    'text-[10px] font-bold px-2 py-0.5 rounded-md',
                                    $badgeClasses($t['badge']),
                                ])>{{ $t['badge'] === 'success' ? 'سبز' : 'خاکستری' }}</span>
                            @endif
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                نشان‌های شمارش وقتی صفر باشند نمایش داده نمی‌شوند (?: null) — یعنی زبانهٔ «بدون لینک» وقتی همه لینک دارند، بدون نشان می‌ماند.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها و گروه‌بندی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">filter_list</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
            @foreach($groups as $g)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">folder</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ستون «صاحب» و فیلتر «کاربر» فقط برای ادمین‌های با نقش ارتقا‌یافته قابل مشاهده‌اند — کاربر عادی لیست خودش را بدون این ستون می‌بیند.
            </p>
        </div>
    </div>
</div>