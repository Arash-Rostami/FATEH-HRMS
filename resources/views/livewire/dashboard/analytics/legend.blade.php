@php
    $tabs = [
        ['id' => 'scope', 'icon' => 'dataset', 'label' => 'دامنه و به‌روزرسانی'],
        ['id' => 'charts', 'icon' => 'bar_chart', 'label' => 'نمودارها و دسته‌بندی'],
        ['id' => 'metrics', 'icon' => 'monitoring', 'label' => 'سنجه‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $scopeRows = [
        ['icon' => 'public', 'color' => 'primary', 'label' => 'نگاه سازمانی، نه شخصی', 'text' => 'تمام نمودارها روی کل جدول «profiles» اجرا می‌شوند و بر اساس کاربر یا واحد شما فیلتر نمی‌گردند؛ همهٔ کاربران یک تصویر یکسان از کل سازمان می‌بینند.'],
        ['icon' => 'schedule', 'color' => 'secondary', 'label' => 'اسنپ‌شات ۵ دقیقه‌ای', 'text' => 'داده‌های نمودار با کشِ ۵ دقیقه‌ای ذخیره می‌شوند و میان درخواست‌ها باز استفاده می‌شوند؛ بنابراین ویرایش یک پروفایل بلافاصله در نمودارها ظاهر نمی‌شود و تا انقضای کش تأخیر دارد.'],
        ['icon' => 'hourglass_top', 'color' => 'tertiary', 'label' => 'بارگذاری تنبل', 'text' => 'این بخش تنبل بارگذاری می‌شود؛ ابتدا یک جایگزینِ چرخان نمايش داده می‌شود و کوئری‌های نمودار فقط پس از بارگذاری واقعی اجرا می‌گردند.'],
    ];

    $chartRows = [
        ['icon' => 'pie_chart', 'color' => 'primary', 'label' => 'دونات تنها یک نمودار است', 'text' => 'تنها ماژول «وضعیت تأهل و جنسیت» (hr_d) به‌صورت دونات نمایش داده می‌شود؛ تمام ۱۶ ماژول دیگر میله‌ای هستند.'],
        ['icon' => 'stacked_bar_chart', 'color' => 'secondary', 'label' => 'سطل «سایر» پویا', 'text' => 'نمودارها تنها وقتی یک دستهٔ «سایر» می‌سازند که ردیفی با مقدار تهی یا خارج از فهرست موجود باشد؛ بنابراین تعداد میله‌ها می‌تواند از مواردِ شمارش‌شده بیشتر شود.'],
        ['icon' => 'workspaces', 'color' => 'tertiary', 'label' => 'برش ۱۰ واحد و ۶ تخصص', 'text' => 'نمودارهای مبتنی بر واحد، تنها ۱۰ واحد بزرگ‌تر را نشان می‌دهند و ماژول «تخصص بر حسب سمت» تنها ۶ تخصص پرتکرارتر را؛ موارد کوچک‌تر در «سایر» جمع یا حذف می‌شوند.'],
        ['icon' => 'percent', 'color' => 'error', 'label' => 'hr_m درصد است نه سرشماری', 'text' => 'ماژول «سهم نیروی آزمایشی» درصد ۰ تا ۱۰۰ را نمایش می‌دهد، نه تعداد نفرات را؛ برخلاف سایر میله‌ها که شمارش خام هستند.'],
        ['icon' => 'bar_chart', 'color' => 'primary', 'label' => 'hr_i افقی است', 'text' => 'ماژول «سمت بر حسب واحد» میله‌های افقی دارد (برای خوانایی نام سمتها)؛ بقیهٔ میله‌ها عمودی‌اند.'],
    ];

    $metricRows = [
        ['icon' => 'bolt', 'color' => 'secondary', 'label' => '«آنلاین» = ۱۵ دقیقه', 'text' => 'در ماژول «فعال و آنلاین»، شمارِ «آنلاین» کاربرانی را شامل می‌شود که آخرین بازدیدشان در ۱۵ دقیقهٔ گذشته بوده است؛ نه اتصال لحظه‌ای.'],
        ['icon' => 'trending_up', 'color' => 'error', 'label' => 'ریسک جانشینی = ۵۵+ و ۱۰+', 'text' => 'ماژول «در معرض ریسک جانشینی» پروفایل‌هایی را نشان می‌دهد که هم سن ۵۵ سال به بالا دارند و هم سابقهٔ ۱۰ سال یا بیشتر؛ تنها یکی از دو شرط کافی نیست.'],
        ['icon' => 'school', 'color' => 'tertiary', 'label' => '«بدون تخصص» یعنی فیلد خالی', 'text' => 'ماژول «بدون تخصص ثبت‌شده» پروفایل‌هایی را می‌شمارد که فیلد تخصص تهی یا خالی است؛ نه افرادی که تخصص غیرمعتبری دارند.'],
        ['icon' => 'group', 'color' => 'primary', 'label' => 'حضور و آنلاین از جدول کاربران', 'text' => 'ماژول‌های «حضور» و «فعال/آنلاین» برای خواندن وضعیت حضور و آخرین بازدید، جدول «profiles» را به «users» می‌پیوندند؛ سایر ماژول‌ها فقط «profiles» را می‌خوانند.'],
    ];

    $notes = [
        'این بخش فقط‌خواندنی است: فیلتر، بازهٔ تاریخ یا نفوذ به جزئیات وجود ندارد؛ دکمه‌های دسته و ماژول تنها راه پیمایش هستند.',
        '۴ دسته و ۱۷ ماژول: جمعیت‌شناختی، صلاحیت، سلامت واحد، و مشارکت.',
        'اعداد نمودار شمارش خامِ نفرات هستند، مگر آن که برچسب محور علامت «٪» داشته باشد (فقط hr_m).',
    ];
@endphp

<div x-data="{ tab: 'scope' }">
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

    @foreach([
        'scope' => $scopeRows,
        'charts' => $chartRows,
        'metrics' => $metricRows,
    ] as $panelId => $rows)
        <div x-show="tab === '{{ $panelId }}'" x-cloak class="space-y-3">
            @foreach($rows as $s)
                @php
                    $chipClasses = match ($s['color']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
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

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>