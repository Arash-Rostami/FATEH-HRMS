@php
    $tabs = [
        ['icon' => 'list', 'label' => 'همه', 'hint' => 'بدون فیلتر وضعیت؛ کل فهرست.'],
        ['icon' => 'schedule', 'label' => 'انجام‌نشده', 'hint' => 'فقط وظایف «انجام‌نشده»؛ تعداد با نشان خاکستری.'],
        ['icon' => 'sync', 'label' => 'در حال انجام', 'hint' => 'فقط وظایف «در حال انجام»؛ تعداد با نشان زرد.'],
        ['icon' => 'pause_circle', 'label' => 'در انتظار', 'hint' => 'فقط وظایف «در انتظار»؛ تعداد با نشان قرمز.'],
        ['icon' => 'check_circle', 'label' => 'انجام‌شده', 'hint' => 'فقط وظایف «انجام‌شده».'],
        ['icon' => 'priority_high', 'label' => 'سررسید گذشته', 'hint' => 'سررسیدشان گذشته و انجام‌شده نیستند؛ نشان قرمز. فیلتر «سررسید گذشته» هم همین شرط را اعمال می‌کند.'],
        ['icon' => 'logout', 'label' => 'محول‌شده', 'hint' => 'مسئول انجام تعیین شده و با ایجادکننده فرق دارد. فیلتر «محول‌شده» هم همین را نشان می‌دهد.'],
        ['icon' => 'delete', 'label' => 'حذف‌شده', 'hint' => 'فقط وظایف حذف‌شده؛ نشان قرمز. زبانه‌ها فقط وقتی دیده می‌شوند که تنظیم «نمایش زبانه‌های فهرست» کاربر روشن باشد.'],
    ];

    $filters = [
        ['label' => 'وضعیت', 'hint' => 'فقط وظایف با وضعیت انتخاب‌شده را نشان می‌دهد.'],
        ['label' => 'پروژه', 'hint' => 'فقط وظایفِ پروژهٔ انتخاب‌شده را نشان می‌دهد.'],
        ['label' => 'اولویت', 'hint' => 'فقط وظایف با اولویت انتخاب‌شده را نشان می‌دهد.'],
        ['label' => 'ایجادکننده / مسئول انجام', 'hint' => 'دو فیلتر جداگانه با امکان جستجوی نام.'],
        ['label' => 'محول‌شده', 'hint' => 'فقط وظایف محول‌شده یا فقط محول‌نشده را نشان می‌دهد.'],
        ['label' => 'آرشیو', 'hint' => 'فقط وظایف آرشیو‌شده یا فقط فعال را نشان می‌دهد.'],
        ['label' => 'حذف‌شده‌ها', 'hint' => 'نمایش وظایف حذف‌شده، فقط حذف‌شده‌ها، یا بدون آن‌ها.'],
        ['label' => 'تاریخ ثبت', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد.'],
        ['label' => 'سررسید گذشته', 'hint' => 'با روشن‌کردن آن، وظایفی نمایش داده می‌شوند که سررسیدشان گذشته و انجام‌شده نیستند.'],
        ['label' => 'در آستانه حذف', 'hint' => 'فیلتر toggle: deleted_at <= ' . convertToPersian('30') . ' روز پیش — ردیف‌هایی که هرس خودکار قریب‌الوقوع است.'],
    ];

    $groups = [
        ['label' => 'گروه بر اساس وضعیت', 'hint' => 'وظایف را زیر هر وضعیت دسته‌بندی می‌کند.'],
        ['label' => 'گروه بر اساس ایجادکننده / مسئول', 'hint' => 'دو گروه‌بندی جداگانه که هرکدام قابل بستن و بازکردن است.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tab</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌ها و فیلترهای فهرست</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست وظایف هشت زبانه دارد و فیلترها روی همهٔ وظایف، حتی حذف‌شده‌ها، اعمال می‌شوند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">format_list_bulleted</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های فهرست</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                با خاموش‌کردن تنظیم «نمایش زبانه‌های فهرست» نوار زبانه‌ها کامل پنهان می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها و گروه‌بندی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">filter_list</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
            @foreach($groups as $g)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">folder</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>