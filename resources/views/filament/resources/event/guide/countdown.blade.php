@php
    $parts = [
        [
            'icon' => 'hourglass_top',
            'label' => 'شمارش معکوس سراسری',
            'hint' => 'وقتی برای یک رویداد عمومی شمارش معکوس را فعال کنید، نزدیک‌ترین رویداد آیندهٔ عمومی با شمارش معکوسِ فعال، به‌صورت بنر در همهٔ صفحات پنل کاربری نمایش داده می‌شود. فقط یک رویداد در هر لحظه فعال است — نزدیک‌ترینِ آینده. رویدادهای خصوصی هرگز شمارش معکوس سراسری نمی‌گیرند.',
        ],
        [
            'icon' => 'lock',
            'label' => 'فقط برای رویدادهای عمومی',
            'hint' => 'بخش «شمارش معکوس رویداد» در فرم فقط وقتی رویداد عمومی باشد نمایش داده می‌شود (با toggling فیلد «خصوصی» ظاهر/ناپدید می‌شود). فعال‌سازی روی رویداد خصوصی ممکن نیست.',
        ],
        [
            'icon' => 'celebration',
            'label' => 'حالت شاد / سوگواری',
            'hint' => 'دو حالت دارید: «شاد / جشن» و «سوگواری / یادبود». در حالت سوگواری، افشان کاغذ و جلوه‌های شاد به‌طور خودکار غیرفعال می‌شوند — برای یادبودها و ایام سوگواری مناسب است.',
        ],
        [
            'icon' => 'auto_awesome',
            'label' => 'افشان کاغذ (confetti)',
            'hint' => 'هنگام نمایش بنر و رسیدن به زمان رویداد، افشان کاغذ پخش می‌شود. پیش‌فرض فعال است ولی در حالت سوگواری نادیده گرفته می‌شود.',
        ],
        [
            'icon' => 'format_quote',
            'label' => 'پیام‌های چرخشی (messages)',
            'hint' => 'فهرستی از پیام‌های کوتاه (حداکثر ' . convertToPersian('8') . ' پیام) که در بنر شمارش معکوس به‌صورت چرخشی نمایش داده می‌شوند. پیام‌های خالی به‌صورت خودکار حذف می‌شوند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">hourglass_top</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">شمارش معکوس یک بنر سراسری است که از نزدیک‌ترین رویداد عمومی آینده می‌سازد</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فیلد `countdown` یک ستون JSON است که چهار زیرکلید دارد: <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">enabled</code>، <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">mood</code>، <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">confetti</code> و <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">messages</code>. در فرم، این زیرکلیدها به‌صورت فیلدهای مجزا نمایش داده می‌شوند و هنگام ذخیره به‌صورت یک JSON در همان ستون `countdown` بسته‌بندی می‌شوند. کلیدها بدون پیشوند هستند (نه `countdown_enabled`) چون خود ستون نامِ ویژگی را نشان می‌دهد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اجزای شمارش معکوس</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($parts as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $p['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">cached</span>
                رویداد فعال در کش `countdown:active` به‌مدت {{ convertToPersian('60') }} ثانیه نگه داشته می‌شود و با هر ذخیره یا حذف رویداد، خودکار پاک می‌شود. اگر بنر پس از ویرایش بلافاصله به‌روز نشد، دلیلش همین کش است — پس از یک دقیقه اصلاح می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">visibility</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">تجربهٔ کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">schedule</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">بنر شمارش معکوس</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">کاربر بنر شمارش معکوس را در بالای پنل کاربری می‌بیند — عنوان رویداد، زمان باقی‌مانده و پیام‌های چرخشی. کاربر می‌تواند بنر را برای آن روز رد کند (dismiss)؛ فردا دوباره ظاهر می‌شود.</p>
                </div>
            </div>
        </div>
    </div>
</div>