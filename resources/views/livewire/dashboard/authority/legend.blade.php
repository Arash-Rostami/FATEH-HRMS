@php
    $tabs = [
        ['id' => 'views', 'icon' => 'visibility', 'label' => 'نمایش فهرست'],
        ['id' => 'status', 'icon' => 'gavel', 'label' => 'وضعیت‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $viewRows = [
        ['icon' => 'manage_accounts', 'color' => 'primary', 'label' => 'نمای خلاصه', 'text' => 'شرح وظیفه، مسئول و سطح اختیار تأییدشده را در یک نگاه ببینید.'],
        ['icon' => 'list_alt', 'color' => 'tertiary', 'label' => 'نمای جزئیات', 'text' => 'هر ردیف را باز کنید تا روش اجرایی، دفعات تکرار، میزان اثرگذاری و تفویض پیشنهادی را ببینید. اگر همکار دیگری در اختیار شریک باشد، نام او در «تفویض مشترک» دیده می‌شود.'],
        ['icon' => 'bolt', 'color' => 'secondary', 'label' => 'نمایش یک اختیار', 'text' => 'با انتخاب اختیار از جستجوی سراسری، فقط همان مورد و جزئیات آن نمایش داده می‌شود. برای بازگشت به فهرست کامل، «خروج از تمرکز» را انتخاب کنید.'],
        ['icon' => 'expand_more', 'color' => 'primary', 'label' => 'باز/بسته‌کردن همه', 'text' => 'دکمهٔ «باز کردن همه» همهٔ ردیف‌ها را یکجا باز می‌کند و نامِ آن به «بستن همه» تغییر می‌کند.'],
    ];

    $statusRows = [
        ['icon' => 'person', 'color' => 'primary', 'label' => 'وظیفهٔ اصلی', 'text' => 'با نشان شخص، نام مسئول مستقیم همان واحد را می‌بینید.'],
        ['icon' => 'groups', 'color' => 'tertiary', 'label' => 'وظیفهٔ زیرمجموعه', 'text' => 'نشان گروه و عبارت «وظایف زیرمجموعه» یعنی این وظیفه واحدهای زیرمجموعه را هم در بر می‌گیرد.'],
        ['icon' => 'verified', 'color' => 'secondary', 'label' => 'تفویض مصوب', 'text' => 'نشان رنگی هر ردیف، سطح تفویض مصوب را مشخص می‌کند: «تصمیم‌گیری و اجرا»، «بررسی و پیشنهاد»، «بررسی و گزارش» یا «تصمیم‌گیری و گزارش». اگر تفویضی ثبت نشده باشد، نشانی نمایش داده نمی‌شود.'],
    ];

    $notes = [
        'در نوار بالا، واحدهایی را می‌بینید که برایشان اختیار ثبت شده است. صفحه ابتدا روی واحد ثبت‌شده در پروفایل شما باز می‌شود.',
        'برای اصلاح یا افزودن اختیار، از مدیر سامانه بخواهید آن را در پنل مدیریت ثبت کند.',
        'برای پیدا کردن یک اختیار، بخشی از شرح وظیفه را جست‌وجو کنید. برای دیدن وظایف یک واحد، آن واحد را از نوار بالا انتخاب کنید.',
        'عدد بالای فهرست، تعداد اختیاراتی را نشان می‌دهد که با واحد انتخاب‌شده و جست‌وجوی شما مطابقت دارند. برای دیدن جزئیات همهٔ موارد، کنار همان عدد «باز کردن همه» را بزنید.',
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
