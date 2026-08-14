@php
    $tabs = [
        ['id' => 'search', 'icon' => 'search', 'label' => 'جستجو و فیلتر'],
        ['id' => 'view', 'icon' => 'expand_more', 'label' => 'آکاردئون و تمرکز'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $searchRows = [
        ['icon' => 'search', 'color' => 'primary', 'label' => 'جستجوی همه‌جانبه', 'text' => 'نوار جستجو هم در متن سوال و هم در متن پاسخ می‌گردد — نه فقط عنوان‌ها. اگر کلمه‌ای فقط در دلِ پاسخ آمده باشد، آن پرسش هم در نتیجه ظاهر می‌شود.'],
        ['icon' => 'sell', 'color' => 'secondary', 'label' => 'فیلتر دسته‌بندی', 'text' => 'دکمه‌های چپیِ دسته‌بندی با تطبیقِ جزیی (like) کار می‌کنند، نه تطبیق دقیق — «مال» با «مالی» هم‌خوان می‌شود. فعال کردن یک دسته، حالت تمرکز را پاک می‌کند.'],
        ['icon' => 'apartment', 'color' => 'tertiary', 'label' => 'فیلتر واحد', 'text' => 'دکمه‌های چپیِ واحد، پرسش‌های مربوط به واحدِ خودتان را جدا می‌کنند. نشانِ tooltip روی هر دکمه، نام کامل واحد را نمایش می‌دهد. انتخاب «همه» فیلتر را برمی‌دارد.'],
        ['icon' => 'restart_alt', 'color' => 'primary', 'label' => 'پاک‌سازی فیلتر', 'text' => 'دکمهٔ «پاک‌سازی» در نوار فیلتر، جستجو، دسته‌بندی و واحد را هم‌زمان بازنشانی می‌کند و حالت تمرکز را نیز پاک می‌کند.'],
    ];

    $viewRows = [
        ['icon' => 'expand_more', 'color' => 'primary', 'label' => 'باز و بسته شدن', 'text' => 'کلیک روی کارت، پاسخ را با انیمیشن باز می‌کند؛ کلیک دوباره آن را می‌بندد. لینک‌های داخل پاسخ خودکار در زبانهٔ جدید مرورگر باز می‌شوند.'],
        ['icon' => 'center_focus_strong', 'color' => 'tertiary', 'label' => 'حالت تمرکز', 'text' => 'اگر از پالت فرمان (بالا-راست) یک پرسش را انتخاب کنید، فهرست به همان یک ردیف محدود می‌شود و آکاردئونِ آن خودکار باز می‌شود. جستجو یا تغییر فیلتر، تمرکز را پاک می‌کند.'],
        ['icon' => 'edit_calendar', 'color' => 'secondary', 'label' => 'برچسب ثبت/به‌روزرسانی', 'text' => 'برچسبِ هر کارت می‌گوید پرسش «ثبت» شده (هرگز ویرایش نشده، با تاریخ ایجاد) یا «به‌روزرسانی» شده (حداقل یک‌بار ویرایش، با تاریخ آخرین ویرایش و رنگ متفاوت).'],
        ['icon' => 'view_list', 'color' => 'primary', 'label' => 'بارگذاری بیشتر', 'text' => 'فهرست با ' . convertToPersian('10') . ' ردیف شروع می‌شود و دکمهٔ «بارگذاری بیشتر» هر بار ' . convertToPersian('10') . ' ردیف دیگر می‌آورد. در حالت تمرکز، اندازهٔ صفحه به ' . convertToPersian('50') . ' ردیف افزایش می‌یابد.'],
    ];

    $notes = [
        'پرسش‌های «عمومی» (بدون واحد) به همهٔ کاربران نمایش داده می‌شوند؛ پرسشِ مربوط به واحد دیگر فقط با فیلترِ آن واحد قابل دیدن است.',
        'شما نمی‌توانید پرسشی بسازید یا ویرایش کنید — محتوا را ادمین در پنل مدیریت نگهداری می‌کند. اگر پرسشی اشتباه است، به ادمین اطلاع دهید.',
        'جستجو، تغییر دسته‌بندی یا تغییر واحد، حالت تمرکز را پاک می‌کند و فهرست کامل برمی‌گردد.',
        'تاریخِ روی برچسب هر کارت شمسی است و نشان می‌دهد پرسش کِی ثبت یا آخرین‌بار ویرایش شده — نه زمانِ بازدیدِ شما.',
    ];
@endphp

<div x-data="{ tab: 'search' }">
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

    <div x-show="tab === 'search'" x-cloak class="space-y-3">
        @foreach($searchRows as $s)
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

    <div x-show="tab === 'view'" x-cloak class="space-y-3">
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

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>