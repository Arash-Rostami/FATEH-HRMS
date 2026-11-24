@php
    $tabs = [
        ['id' => 'scope', 'icon' => 'dataset', 'label' => 'آمار و زمان به‌روزرسانی'],
        ['id' => 'charts', 'icon' => 'bar_chart', 'label' => 'نمودارها و دسته‌بندی'],
        ['id' => 'metrics', 'icon' => 'monitoring', 'label' => 'معنای شاخص‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $scopeRows = [
        ['icon' => 'public', 'color' => 'primary', 'label' => 'نمای کلی سازمان', 'text' => 'آمار این بخش مربوط به کل سازمان است و به کاربر یا واحد شما محدود نمی‌شود. همهٔ کاربران، آمار یکسانی می‌بینند.'],
        ['icon' => 'schedule', 'color' => 'secondary', 'label' => 'به‌روزرسانی آمار', 'text' => 'نمایش تغییرات اطلاعات کارکنان ممکن است تا ۵ دقیقه زمان ببرد. برچسب «به‌روزرسانی» بالای هر نمودار، زمان تهیهٔ آمار نمایش‌داده‌شده را مشخص می‌کند.'],
        ['icon' => 'hourglass_top', 'color' => 'tertiary', 'label' => 'آماده‌سازی نمودارها', 'text' => 'پس از ورود به این بخش، نشان بارگذاری تا آماده‌شدن نمودارها نمایش داده می‌شود.'],
    ];

    $chartRows = [
        ['icon' => 'pie_chart', 'color' => 'primary', 'label' => 'نوع نمودارها', 'text' => '«وضعیت تأهل و جنسیت» به‌صورت حلقه‌ای و سایر آمارها به‌صورت میله‌ای نمایش داده می‌شوند.'],
        ['icon' => 'stacked_bar_chart', 'color' => 'secondary', 'label' => 'دستهٔ «سایر»', 'text' => 'اطلاعات خالی یا مقادیری که در دسته‌بندی‌های مشخص قرار نمی‌گیرند، در گروه «سایر» نمایش داده می‌شوند. این گروه فقط در صورت وجود چنین اطلاعاتی ظاهر می‌شود.'],
        ['icon' => 'workspaces', 'color' => 'tertiary', 'label' => 'نمایش دسته‌های اصلی', 'text' => 'نمودارهای واحد سازمانی، ۱۰ واحد بزرگ‌تر و نمودار «تخصص بر حسب سمت»، ۶ تخصص پرتکرارتر را نشان می‌دهند. سایر موارد، بسته به نمودار، در «سایر» قرار می‌گیرند یا نمایش داده نمی‌شوند.'],
        ['icon' => 'percent', 'color' => 'error', 'label' => 'سهم نیروی آزمایشی', 'text' => 'عدد هر واحد، درصد کارکنان آزمایشی از کل کارکنان همان واحد است.'],
        ['icon' => 'bar_chart', 'color' => 'primary', 'label' => 'سمت بر حسب واحد', 'text' => 'برای خوانایی نام سمت‌ها، این نمودار به‌صورت میله‌ای افقی نمایش داده می‌شود؛ سایر نمودارهای میله‌ای عمودی هستند.'],
    ];

    $metricRows = [
        ['icon' => 'bolt', 'color' => 'secondary', 'label' => 'معنای «آنلاین»', 'text' => 'در ماژول «فعال و آنلاین»، شمارِ «آنلاین» کاربرانی را شامل می‌شود که آخرین بازدیدشان در ۱۰ دقیقهٔ گذشته بوده است؛ نه اتصال لحظه‌ای.'],
        ['icon' => 'trending_up', 'color' => 'error', 'label' => 'معنای «ریسک جانشینی»', 'text' => 'ماژول «در معرض ریسک جانشینی» پروفایل‌هایی را نشان می‌دهد که هم سن ۵۵ سال به بالا دارند و هم سابقهٔ ۱۰ سال یا بیشتر؛ تنها یکی از دو شرط کافی نیست.'],
        ['icon' => 'school', 'color' => 'tertiary', 'label' => '«بدون تخصص» یعنی فیلد خالی', 'text' => 'ماژول «بدون تخصص ثبت‌شده» پروفایل‌هایی را می‌شمارد که فیلد تخصص تهی یا خالی است؛ نه افرادی که تخصص غیرمعتبری دارند.'],
        ['icon' => 'group', 'color' => 'primary', 'label' => 'تفاوت حضور و فعالیت در سامانه', 'text' => 'آمار «حضور» بر پایهٔ وضعیت حضور ثبت‌شده و آمار «فعال/آنلاین» بر پایهٔ وضعیت حساب و آخرین بازدید محاسبه می‌شود؛ این اعداد لزوماً برابر نیستند.'],
    ];

    $notes = [
        'این بخش برای مشاهدهٔ آمار است و امکان ویرایش، انتخاب بازهٔ زمانی یا بازکردن جزئیات افراد را ندارد. برای جابه‌جایی، دسته و نمودار مورد نظر را انتخاب کنید.',
        '۴ دسته و ۱۷ ماژول: جمعیت‌شناختی، صلاحیت، سلامت واحد، و مشارکت.',
        'اعداد نمودارها تعداد افراد را نشان می‌دهند، مگر در «سهم نیروی آزمایشی» که محور با علامت «٪» مشخص شده است.',
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
