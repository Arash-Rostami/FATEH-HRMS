@php
    $tabs = [
        ['id' => 'views', 'icon' => 'visibility', 'label' => 'منظرها'],
        ['id' => 'status', 'icon' => 'gavel', 'label' => 'وضعیت‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $viewRows = [
        ['icon' => 'manage_accounts', 'color' => 'primary', 'label' => 'منظر اجمالی', 'text' => 'زبانهٔ پیش‌فرض — هر ردیف فقط شرح وظیفه، مسئول و نشانِ تفویضِ مصوب را نشان می‌دهد. جزئیاتِ اجرایی پنهان است.'],
        ['icon' => 'list_alt', 'color' => 'tertiary', 'label' => 'منظر مدیریتی', 'text' => 'با باز کردنِ هر ردیف در این زبانه، چهار ردیفِ اضافی آشکار می‌شود: روش اجرایی، فراوانی تکرار، شاخص اثر و تفویض پیشنهادی. تفویضِ مشترک نیز در نوارِ بالایی نمایش داده می‌شود.'],
        ['icon' => 'bolt', 'color' => 'secondary', 'label' => 'حالتِ تمرکز', 'text' => 'وقتی از پالتِ دستوری یک اختیار را انتخاب کنید، فهرست به همان یک رکورد پین می‌شود و خودکار باز می‌گردد. با دکمهٔ «خروج از تمرکز» به فهرستِ کامل برمی‌گردید.'],
        ['icon' => 'expand_more', 'color' => 'primary', 'label' => 'باز/بسته‌کردن همه', 'text' => 'دکمهٔ «باز کردن همه» همهٔ ردیف‌ها را یکجا باز می‌کند و نامِ آن به «بستن همه» تغییر می‌کند.'],
    ];

    $statusRows = [
        ['icon' => 'person', 'color' => 'primary', 'label' => 'وظیفهٔ اصلی', 'text' => 'آیکونِ شخص و نامِ مسئولِ مستقیم در ردیف می‌نشیند — این وظیفه فقط برای همان واحد اعمال می‌شود.'],
        ['icon' => 'groups', 'color' => 'tertiary', 'label' => 'وظیفهٔ زیرمجموعه', 'text' => 'آیکونِ گروه و برچسبِ «وظایف زیرمجموعه» جایگزینِ نامِ مسئول می‌شود — این وظیفه برای تمامی زیرمجموعه‌های سازمانی نیز اعمال می‌گردد.'],
        ['icon' => 'verified', 'color' => 'secondary', 'label' => 'تفویض مصوب', 'text' => 'نشانِ رنگیِ روی هر ردیف سطحِ تفویضِ مصوب را نشان می‌دهد: تصمیم و اجرا (آبی)، بررسی و پیشنهاد (ثانویه)، بررسی و گزارش (ثالثیه)، تصمیم و گزارش (قرمز). رکوردِ بدونِ تفویض نشان نمی‌گیرد.'],
    ];

    $notes = [
        'نوارِ واحدها فقط واحدهایی را نشان می‌دهد که حداقل یک اختیار دارند — واحدِ خالی در این فهرست نیست. واحدِ پیش‌فرض برابر با واحدِ پروفایلِ شماست.',
        'این صفحه فقط خواندنی است؛ ایجاد، ویرایش و حذفِ اختیارات در پنلِ مدیریت انجام می‌شود.',
        'جستجو در شرح وظیفه، روش اجرایی، فراوانی تکرار، شاخص اثر، تفویض‌ها، تفویض مشترک و نامِ واحد انجام می‌شود — نه در نامِ مسئول.',
        'تعداد کل اختیاراتِ سازمان در بالای صفحه نمایش داده می‌شود و به‌صورتِ کش‌شده هر ' . convertToPersian('1') . ' ساعت یک‌بار به‌روز می‌شود.',
        'با دکمهٔ «بارگذاری بیشتر»، ' . convertToPersian('20') . ' ردیفِ دیگر به فهرست اضافه می‌شود.',
    ];
@endphp

<div x-data="{ tab: 'views' }">
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

    <div x-show="tab === 'views'" x-cloak class="space-y-3">
        @foreach($viewRows as $s)
            @php
                $chipClasses = match ($s['color']) {
                    'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
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

    <div x-show="tab === 'status'" x-cloak class="space-y-3">
        @foreach($statusRows as $s)
            @php
                $chipClasses = match ($s['color']) {
                    'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
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

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>