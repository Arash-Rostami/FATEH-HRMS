@php
    $panels = [
        [
            'icon' => 'view_carousel',
            'label' => 'تایم‌لاین افقی',
            'hint' => 'گالری‌ها به‌صورت کارت‌های اسکرولِ افقیِ snap-x نمایش داده می‌شوند؛ روی رکوردِ فعال، کارت بزرگ‌تر و حلقهٔ فعال بالای آن می‌نشیند. دکمهٔ «مخفی/نمایش تایم‌لاین» ریل و نقطهٔ زمانیِ کنار کارت‌ها را پنهان/ظاهر می‌کند (ریل صرفاً تزئینی است).',
        ],
        [
            'icon' => 'calendar_month',
            'label' => 'فیلتر ماه (تایم‌لاین)',
            'hint' => 'دکمهٔ تقویم بالای تایم‌لاین، ماه‌های موجود (مثل «اردیبهشت ۱۴۰۴») را از تاریخ رویداد می‌سازد؛ انتخاب یک ماه، فقط گالری‌های همان ماه را نشان می‌دهد و «همه ماه‌ها» فیلتر را پاک می‌کند.',
        ],
        [
            'icon' => 'photo_library',
            'label' => 'کلاژ و لایت‌باکس',
            'hint' => 'هر کارت تا ۳ تصویرِ چرخیده را به‌صورت کلاژ نشان می‌دهد و بقیه با نشان «+N»؛ کلیک روی هر تصویر لایت‌باکس Fancybox باز می‌کند که ویدیوها را هم (با data-type html5video) پخش می‌کند. ویدیوها با رفتنِ موس، پیش‌نمایش پخش می‌شوند و مدت‌زمانشان نشان داده می‌شود.',
        ],
        [
            'icon' => 'bolt',
            'label' => 'حالت تمرکز از پالت دستور',
            'hint' => 'وقتی کاربر با پالت دستور (?open={id}) به یک گالری می‌رسد، فقط همان رکورد (با رعایتِ دیدِ واحد) نمایش داده می‌شود؛ دکمهٔ «نمایش همه» فهرست کامل را برمی‌گرداند.',
        ],
        [
            'icon' => 'tune',
            'label' => 'صفحه‌بندی پنهان',
            'hint' => 'بارگذاریِ بیشتر به‌صورت خودکار با اسکرول انجام می‌شود (cursor روی شناسه‌ها)؛ نشانِ loader در انتها ظاهر می‌شود و وقتی رکوردی نماند، محو می‌گردد.',
        ],
    ];

    $scope = [
        ['icon' => 'public', 'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]', 'label' => 'عمومی', 'hint' => 'گالری برای همهٔ کاربران قابل دید است.'],
        ['icon' => 'lock', 'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'label' => 'تک‌واحدی', 'hint' => 'فقط کاربران همان واحد سازمانی می‌بینند.'],
        ['icon' => 'groups', 'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]', 'label' => 'چندواحدی', 'hint' => 'کاربران همهٔ واحدهای اشتراک‌داده‌شده می‌بینند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در زبانهٔ «گالری» چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانهٔ گالریِ پنل کاربری یک تایم‌لاینِ افقی از کارت‌های گالری است. کاربر فقط گالری‌های عمومی یا آن‌هایی که واحدِ سازمانیِ او در <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">department_id</code> یا آرایهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">departments</code> دارند را می‌بیند — هیچ رکورد خصوصیِ واحدِ دیگر برایش قابل دید نیست. وقتی کاربر از وضعیت دیدن محتوا شکایت می‌کند، این زبانه مرجعِ شماست.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">قابلیت‌های پنل کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($panels as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $p['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">tune</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">آیکونِ گوشهٔ کارت — نوع دسترسی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($scope as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $r['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «گالری فلان را نمی‌بینم»، اول چک کنید آن گالری عمومی است یا واحدِ کاربر در فیلدهای دسترسی اش دارد — کوئریِ پنل کاربری فقط همین دو دسته را برمی‌گرداند.
            </p>
        </div>
    </div>
</div>