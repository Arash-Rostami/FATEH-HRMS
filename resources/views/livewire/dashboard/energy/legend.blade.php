@php
    $tabs = [
        ['id' => 'survey', 'icon' => 'poll', 'label' => 'پرسشنامه'],
        ['id' => 'results', 'icon' => 'bar_chart', 'label' => 'نتایج'],
    ];

    $groups = [
        'survey' => [
            'intro' => 'چگونه پرسشنامه امتیاز می‌دهد و چه زمانی دوباره باز می‌شود.',
            'rows' => [
                ['icon' => 'trending_down', 'color' => 'error', 'label' => 'امتیاز بالاتر یعنی فرسودگی بیشتر', 'text' => 'این سنجهٔ فرسودگی است، نه انرژی. امتیاز بالاتر یعنی وضعیت بدتر. امتیاز کلی از ۰ تا ۱۶ است؛ زیر ۹ مطلوب و ۱۲ به‌بالا پرخطر.'],
                ['icon' => 'event_repeat', 'color' => 'primary', 'label' => 'دورهٔ خنک‌سازی ۲۵ روزه', 'text' => 'پس از تکمیل پرسشنامه، تا ۲۵ روز فرم بسته می‌ماند و فقط تب «نتایج» در دسترس است؛ پس از این مدت پرسشنامهٔ تازه باز می‌شود. روی کارت «پاسخ‌ها ثبت شد» برچسب شمارش معکوس، روزهای باقی‌مانده تا بازگشایی را نشان می‌دهد.'],
                ['icon' => 'checklist', 'color' => 'tertiary', 'label' => 'گزینهٔ آخر هر بخش، انحصاری و بی‌امتیاز است', 'text' => 'در هر بخش، انتخاب گزینهٔ آخر (معمولاً «هیچ‌کدام») سایر گزینه‌های همان بخش را خودکار پاک می‌کند؛ برعکس، انتخاب هر گزینهٔ دیگر گزینهٔ آخر را پاک می‌کند. این گزینه در شمارش امتیاز هم نمی‌خورد — امتیاز بخش فقط تعداد گزینه‌های غیرآخر است.'],
            ],
        ],
        'results' => [
            'intro' => 'آنچه در نمودار و کارت‌های تب «نتایج» می‌بینید و چه کسی آن را می‌بیند.',
            'rows' => [
                ['icon' => 'history', 'color' => 'secondary', 'label' => 'پاسخ‌نامه‌های من', 'text' => 'در تب «نتایج»، کارت «پاسخ‌نامه‌های من» ۱۲ پاسخ‌نامهٔ آخر را فهرست می‌کند؛ با باز کردن هر ردیف، متن دقیق گزینه‌های انتخاب‌شدهٔ همان دوره (با سؤال‌های همان ماه ثبت) به همراه تاریخ و امتیاز کل دیده می‌شود.'],
                ['icon' => 'groups', 'color' => 'secondary', 'label' => 'میانگین شرکت، شما را مستثنی می‌کند', 'text' => 'میانگین شرکت در نمودار، پاسخ‌نامهٔ خودِ شما را شامل نمی‌شود — یعنی با همکارانتان مقایسه می‌شوید، نه با خودتان. این یک مقایسهٔ همتاست.'],
                ['icon' => 'shield_person', 'color' => 'secondary', 'label' => 'دیدِ مدیر از تیم', 'text' => 'اگر سرپرست بخش هستید، نمودار آخرین امتیاز اعضای تیمتان را نشان می‌دهد — اما فقط اعضایی که حداقل یک پاسخ‌نامه ثبت کرده‌اند. اعضای بدون پاسخ‌نامه در فهرست نیستند.'],
                ['icon' => 'trending_up', 'color' => 'primary', 'label' => 'پنجرهٔ ۱۸ ماهه', 'text' => 'نمودار روند و میانگین شرکت هر دو روی ۱۸ ماه اخیر کار می‌کنند. پاسخ‌نامه‌های قدیمی‌تر از این پنجره در نمودار نیستند، ولی در پایگاه داده باقی می‌مانند. در سربرگ کارت «پاسخ‌نامه‌های من»، بهترین و کم‌امتیازترین امتیاز کل با تاریخ‌شان به‌صورت نشان همیشه دیده می‌شود — حتی وقتی کارت جمع است.'],
            ],
        ],
    ];

    $chipClasses = fn(string $color): string => match ($color) {
        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
    };
@endphp

<div x-data="{ tab: 'survey' }">
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

    @foreach($groups as $groupId => $group)
        <div x-show="tab === '{{ $groupId }}'" x-cloak class="space-y-2">
            <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">{{ $group['intro'] }}</p>
            @foreach($group['rows'] as $row)
                <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($row['color']) }}">
                        <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                        <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>