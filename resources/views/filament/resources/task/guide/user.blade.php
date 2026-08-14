@php
    $panels = [
        [
            'icon' => 'view_column',
            'label' => 'دو زبانه و چهار ستون',
            'hint' => 'زبانهٔ «وظایف من» = وظایفی که به شما محول شده، یا خودتان ساخته‌اید و هنوز محول نشده. زبانهٔ «محول شده» = وظایفی که شما ایجادکننده‌اش هستید و به فرد دیگری محول کرده‌اید. چهار ستون: انجام‌نشده، در حال انجام، در انتظار، انجام‌شده — هر ستون صفحه‌بندی جداگانه دارد.',
        ],
        [
            'icon' => 'search',
            'label' => 'جستجو پنجره را باز می‌کند',
            'hint' => 'جستجو در عنوان و توضیحات، صفحه‌بندی را ریست می‌کند و موقتاً هم پنجرهٔ ' . convertToPersian('45') . ' روزه و هم فیلتر آرشیو را غیرفعال می‌کند — یعنی نتایج شامل وظایف آرشیو‌شده و انجام‌شده‌های قدیمی هم می‌شود.',
        ],
        [
            'icon' => 'checklist',
            'label' => 'انتخاب گروهی',
            'hint' => 'حالت انتخاب، کارت‌ها را علامت می‌زند؛ سپس ارجاع گروهی به یک نفر، جابه‌جایی گروهی به ستون دیگر، یا حذف گروهی. جابه‌جایی گروهی از ستون انجام‌شده، خودکار archived_at را پاک می‌کند.',
        ],
        [
            'icon' => 'logout',
            'label' => 'محول‌کردن و واگردانی',
            'hint' => 'ارجاع به فرد دیگری، شما را به زبانهٔ «محول شده» می‌برد و یک اعلان در کارتابل او می‌فرستد. واگردانی فقط توسط ایجادکننده و فقط وقتی وظیفه انجام‌شده نیست و از تیکت نیست ممکن است.',
        ],
        [
            'icon' => 'forum',
            'label' => 'گفتگوی وظیفه',
            'hint' => 'در هر وظیفه یک گفتگوی دوطرفه بین ایجادکننده و مسئول انجام است — فرد دیگری مشارکت نمی‌کند. پاسخ‌ها در همان مودال ثبت می‌شود.',
        ],
        [
            'icon' => 'archive',
            'label' => 'آرشیو و پنجرهٔ ۴۵ روزه',
            'hint' => 'وظایف انجام‌شده‌ای که بیش از ' . convertToPersian('45') . ' روز از آخرین تغییرشان می‌گذرد، به‌طور پیش‌فرض از ستون پنهان می‌شوند. دکمهٔ آرشیو روی هر کارت انجام‌شده، آن را به‌اختیار پنهان می‌کند. باز کردن دوباره یا جابه‌جایی، خودکار از آرشیو خارج می‌کند.',
        ],
        [
            'icon' => 'description',
            'label' => 'کارت‌های «از تیکت»',
            'hint' => 'وظایفی که از یک تیکت ساخته شده‌اند، فقط‌خواندنی‌اند و برچسب «از تیکت» دارند؛ ویرایش و گفتگوی آن‌ها فقط از طریق خودِ تیکت انجام می‌شود.',
        ],
    ];

    $roles = [
        [
            'icon' => 'person',
            'chip' => 'primary',
            'label' => 'ایجادکننده',
            'hint' => 'وظیفه را ساخته است. تا وقتی محول نشده در «وظایف من» می‌ماند و خودش می‌تواند وضعیت را تغییر دهد. پس از محول‌کردن، فقط گفتگو می‌کند و واگردانی در دسترس اوست.',
        ],
        [
            'icon' => 'assignment_ind',
            'chip' => 'tertiary',
            'label' => 'مسئول انجام',
            'hint' => 'وظیفه به او محول شده است. در «وظایف من» او دیده می‌شود و تغییر وضعیت (در حال انجام / انجام‌شده) با اوست.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ /taskboard چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/taskboard</code> کاربر یک برد کانبان است — چهار ستون وضعیت، دو زبانهٔ نقش، و مودال یکپارچهٔ ایجاد/ویرایش/گفتگو. وقتی کاربری از محول‌کردن، واگردانی، آرشیو یا کارت‌های «از تیکت» شکایت می‌کند، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
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
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">bolt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نشانگر فوری روی کارت</p>
        </div>
        <div class="p-5">
            <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">
                هر کارت یک نوار رنگی کناری دارد که فوریت را از دو عامل می‌سازد: نزدیکی به ضرب‌الاجل (قرمز=گذشته، نزدیک=زرد) و بی‌تحرکی (چند روز بی‌تغیر). وظایف انجام‌شده و در انتظار این نوار را ندارند. این محاسبه در لحظه است و کش نمی‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">groups</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">نقش ایجادکننده در برابر مسئول انجام</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($roles as $r)
                @php
                    $chipClasses = match ($r['chip']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        default => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $chipClasses }}">
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
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">help</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            کاربر در پنل خودش یک راهنمای آماده دارد: دکمهٔ راهنما (آیکون help) بالای صفحهٔ /taskboard یک راهنمای تب‌دار باز می‌کند که نقش‌ها، ستون انجام‌شده (آرشیو/پنجرهٔ ۴۵ روزه/حذف) و نکات را توضیح می‌دهد. اگر کاربر سؤالی دربارهٔ نحوهٔ استفاده دارد، به همان دکمه ارجاع دهید.
        </p>
    </div>
</div>