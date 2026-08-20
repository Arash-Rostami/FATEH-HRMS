@php
    $tabs = [
        ['id' => 'types', 'icon' => 'category', 'label' => 'نوع‌ها'],
        ['id' => 'statuses', 'icon' => 'flag', 'label' => 'وضعیت‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $typeRows = [
        ['icon' => 'support_agent', 'color' => 'primary', 'label' => 'پشتیبانی نرم‌افزار', 'text' => 'راهنمایی یا رفع مشکل فنی.'],
        ['icon' => 'lightbulb', 'color' => 'secondary', 'label' => 'پیشنهاد ماژول', 'text' => 'ایده برای افزودن یا بهبود ماژول. پیش‌فرضِ فرم همین نوع است.'],
        ['icon' => 'bug_report', 'color' => 'error', 'label' => 'گزارش باگ', 'text' => 'گزارش خطا یا رفتار غیرمنتظره.'],
    ];

    $statusRows = [
        ['icon' => 'forum', 'color' => 'primary', 'label' => 'باز', 'text' => 'ثبت‌شده و در انتظار بازبینی ادمین.'],
        ['icon' => 'schedule', 'color' => 'tertiary', 'label' => 'در حال بررسی', 'text' => 'ادمین در حال پیگیری است.'],
        ['icon' => 'check_circle', 'color' => 'secondary', 'label' => 'حل‌شده', 'text' => 'پایش کامل شده.'],
        ['icon' => 'cancel', 'color' => 'error', 'label' => 'عدم امکان', 'text' => 'پایانی — پاسخ ادمین با قاب قرمز نمایش داده می‌شود.'],
    ];

    $n3 = convertToPersian('3');
    $n4 = convertToPersian('4');
    $n5 = convertToPersian('5');
    $n3600 = convertToPersian('3600');

    $notes = [
        'سقف ثبت: نهایتاً ' . $n5 . ' درخواست در هر ' . $n3600 . ' ثانیه. پس از آن، پیام خطا روی فیلد «متن» شمارش معکوس ثانیه‌های باقی‌مانده را نشان می‌دهد.',
        'حداقل طولِ عنوان ' . $n3 . ' و متن ' . $n5 . ' کاراکترِ «معتبر» است — تگ‌های HTML در این شمارش لحاظ نمی‌شوند و فقط متنِ خالص می‌سنجد.',
        'پیوست: نهایتاً ' . $n5 . ' فایل، هر کدام حداکثر ' . $n4 . ' مگابایت، با فرمت‌های تصویری و سند (jpg, png, gif, webp, svg, pdf, doc, docx, xls, xlsx).',
        'درخواست پس از ثبت قابل ویرایش یا حذف توسط شما نیست — فقط ادمین می‌تواند وضعیت یا پاسخ آن را تغییر دهد.',
        'دکمهٔ پشتیبانی را می‌توان از جای دیگری هم باز کرد: رویداد open-release-request.window مودال را با یک نوع پیش‌انتخاب‌شده باز می‌کند (مثلاً «گزارش باگ» از منوی انتشار).',
        'زبانهٔ «درخواست‌های من» با دکمهٔ «موارد بیشتر» به‌صورت صفحه‌بندی ' . $n5 . 'تایی بارگذاری می‌شود؛ درخواست‌های قدیمی‌تر در همان زبانه ظاهر می‌شوند.',
    ];
@endphp

<div x-data="{ tab: 'types' }">
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

    <div x-show="tab === 'types'" x-cloak class="space-y-2">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">هر درخواست یکی از سه نوع زیر را دارد.</p>
        @foreach($typeRows as $row)
            @php
                $chip = match ($row['color']) {
                    'primary'   => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    'error'     => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chip }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'statuses'" x-cloak class="space-y-2">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">وضعیت‌هایی که در زبانهٔ «درخواست‌های من» می‌بینید.</p>
        @foreach($statusRows as $row)
            @php
                $chip = match ($row['color']) {
                    'primary'   => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                    'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                    'tertiary'  => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                    'error'     => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chip }}">
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