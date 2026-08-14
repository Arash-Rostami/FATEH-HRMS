@php
    $tabs = [
        ['icon' => 'priority_high', 'color' => 'error', 'label' => 'پرخطر (۱۲ به‌بالا)', 'text' => 'امتیاز کلی ۱۲ یا بیشتر. زبانهٔ پیش‌فرض «همه» است و نشانِ هر زبانه از یک کوئریِ تجمیعیِ یک‌بارِ memo شده می‌آید.'],
        ['icon' => 'warning', 'color' => 'tertiary', 'label' => 'متوسط (۹ تا ۱۱)', 'text' => 'امتیاز کلی بین ۹ و ۱۱. زبانه‌ها با تنظیم ترجیحی «نمایش زبانه‌های فهرست» کاربر کنترل می‌شوند.'],
        ['icon' => 'task_alt', 'color' => 'secondary', 'label' => 'مطلوب (زیر ۹)', 'text' => 'امتیاز کلی ۸ یا کمتر. بازه‌های زبانه‌ها با فیلتر «امتیاز پایین» (زیر ۴۵ هم نام‌گذاری شده) هم‌خوانی دارند.'],
        ['icon' => 'today', 'color' => 'primary', 'label' => '۳۰ روز اخیر', 'text' => 'فقط پاسخ‌نامه‌هایی که در ۳۰ روز گذشته تکمیل شده‌اند. زبانه‌ها بر اساس امتیاز کلی و تاریخ تکمیل فیلتر می‌شوند.'],
    ];

    $filters = [
        ['icon' => 'tune', 'label' => 'بازه امتیاز', 'text' => 'حداقل و حداکثر امتیاز کلی (۰ تا ۲۰) را وارد کنید تا پاسخ‌نامه‌ها در آن بازه فیلتر شوند.'],
        ['icon' => 'filter_alt', 'label' => 'امتیاز پایین', 'text' => 'یک فیلتر نقطه‌ای که امتیاز کلی ۸ یا کمتر را نشان می‌دهد.'],
        ['icon' => 'today', 'label' => 'بازه تاریخ تکمیل', 'text' => 'از تاریخ و تا تاریخ تکمیل را انتخاب کنید. تقویم شمسی است.'],
        ['icon' => 'event_repeat', 'label' => '۳۰ روز اخیر', 'text' => 'فقط پاسخ‌نامه‌های ۳۰ روز گذشته.'],
        ['icon' => 'person_search', 'label' => 'کاربر', 'text' => 'فیلتر رابطه‌ای روی کاربر، با جستجو و پیش‌بارگذاری.'],
    ];

    $groups = [
        ['icon' => 'groups', 'label' => 'گروه‌بندی بر اساس کاربر', 'text' => 'ردیف‌ها را بر اساس نام کاربر گروه می‌بندد؛ هر گروه قابل جمع شدن است.'],
        ['icon' => 'event', 'label' => 'گروه‌بندی بر اساس ماه', 'text' => 'بر اساس اندیس ماه گروه می‌بندد و عنوان را از تاریخ تکمیل به‌صورت «نام ماه سال» شمسی می‌سازد.'],
    ];

    $thresholds = [
        ['icon' => 'trending_down', 'color' => 'error', 'label' => 'امتیاز کلی', 'text' => '۱۲ به‌بالا: قرمز (خطر) — ۹ تا ۱۱: زرد (هشدار) — زیر ۹: سبز (مطلوب).'],
        ['icon' => 'monitoring', 'color' => 'tertiary', 'label' => 'امتیاز هر بعد', 'text' => '۳ به‌بالا: قرمز — ۲: زرد — زیر ۲: سبز. هر بعد حداکثر ۴ است.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_alt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌های فهرست، فیلترها و گروه‌بندی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست پاسخ‌نامه‌ها پنج زبانه دارد که امتیازها را بر اساس آستانهٔ فرسودگی جدا می‌کنند. نشانِ هر زبانه از یک کوئریِ تجمیعیِ یک‌بار محاسبه می‌شود، نه یک کوئریِ جداگانه برای هر زبانه. مرتب‌سازی پیش‌فرض بر اساس تاریخ تکمیل (نزولی) است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table_rows</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های فهرست</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $row)
                @php
                    $chipClasses = match ($row['color']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $chipClasses }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $row['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">tune</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">فیلترها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $row)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $row['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-secondary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-secondary-container)]">groups</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-secondary-container)]">گروه‌بندی و رنگ‌بندی امتیاز</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($groups as $row)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $row['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
            @foreach($thresholds as $row)
                @php
                    $chipClasses = match ($row['color']) {
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $chipClasses }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $row['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                آستانه‌های زبانه‌ها و رنگ‌بندی ستون‌ها یکسان هستند: ۱۲ خطر، ۹ هشدار. ستون «تاریخ ثبت» به‌طور پیش‌فرض پنهان است.
            </p>
        </div>
    </div>
</div>