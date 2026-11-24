@php
    $tabs = [
        ['id' => 'types', 'icon' => 'category', 'label' => 'نوع درخواست'],
        ['id' => 'statuses', 'icon' => 'flag', 'label' => 'وضعیت‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $typeRows = [
        ['icon' => 'support_agent', 'color' => 'primary', 'label' => 'پشتیبانی نرم‌افزار', 'text' => 'اگر در استفاده از سامانه به راهنمایی یا کمک نیاز دارید، این گزینه را انتخاب کنید.'],
        ['icon' => 'lightbulb', 'color' => 'secondary', 'label' => 'پیشنهاد ماژول', 'text' => 'برای پیشنهاد یک قابلیت تازه یا بهتر کردن یکی از بخش‌ها، این گزینه را انتخاب کنید.'],
        ['icon' => 'bug_report', 'color' => 'error', 'label' => 'گزارش باگ', 'text' => 'اگر بخشی خطا می‌دهد یا آن‌طور که انتظار دارید کار نمی‌کند، این گزینه را انتخاب کنید.'],
    ];

    $statusRows = [
        ['icon' => 'forum', 'color' => 'primary', 'label' => 'باز', 'text' => 'درخواست ثبت شده و در انتظار بررسی مدیر سامانه است.'],
        ['icon' => 'schedule', 'color' => 'tertiary', 'label' => 'در حال بررسی', 'text' => 'مدیر سامانه در حال بررسی و پیگیری درخواست است.'],
        ['icon' => 'check_circle', 'color' => 'secondary', 'label' => 'حل‌شده', 'text' => 'رسیدگی به درخواست با موفقیت پایان یافته است.'],
        ['icon' => 'cancel', 'color' => 'error', 'label' => 'عدم امکان', 'text' => 'امکان انجام درخواست وجود ندارد. پاسخ مدیر سامانه در کادر قرمز نمایش داده می‌شود.'],
    ];

    $n3 = convertToPersian('3');
    $n5 = convertToPersian('5');
    $n10 = convertToPersian('10');

    $notes = [
        'در هر ساعت می‌توانید تا ' . $n5 . ' درخواست ثبت کنید. اگر به این حد برسید، زمان باقی‌مانده زیر کادر «متن درخواست» دیده می‌شود.',
        'اگر قبلاً همین عنوان و متن را فرستاده‌اید، وضعیت درخواست را در «درخواست‌های من» ببینید. سامانه درخواست تکراری را ثبت نمی‌کند.',
        'عنوان باید دست‌کم ' . $n3 . ' و متن دست‌کم ' . $n5 . ' نویسه داشته باشد.',
        'پیوست: نهایتاً ' . $n5 . ' فایل، هر کدام حداکثر ' . $n10 . ' مگابایت، با فرمت‌های تصویری و سند (jpg, jpeg, png, gif, bmp, webp, svg, pdf, doc, docx, xls, xlsx).',
        'پیش از ثبت، متن و پیوست‌ها را مرور کنید. پس از ثبت، وضعیت و پاسخ مدیر سامانه را در «درخواست‌های من» ببینید. ویرایش یا حذف درخواست از اینجا ممکن نیست.',
        'با ورود از میان‌برهای پشتیبانی، ممکن است نوع درخواست از پیش انتخاب شده باشد؛ پیش از ارسال، آن را بررسی کنید.',
        'برای دیدن درخواست‌های قدیمی‌تر، در «درخواست‌های من» روی «موارد بیشتر» بزنید.',
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
