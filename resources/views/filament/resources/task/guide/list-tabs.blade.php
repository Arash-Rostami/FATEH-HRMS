@php
    $tabs = [
        ['icon' => 'list', 'label' => 'همه', 'hint' => 'بدون فیلتر وضعیت؛ کل فهرست.'],
        ['icon' => 'schedule', 'label' => 'انجام‌نشده', 'hint' => 'فقط وضعیت todo؛ نشان شمارش خاکستری.'],
        ['icon' => 'sync', 'label' => 'در حال انجام', 'hint' => 'فقط وضعیت in-progress؛ نشان هشدار (warning).'],
        ['icon' => 'pause_circle', 'label' => 'در انتظار', 'hint' => 'فقط وضعیت pending؛ نشان خطر (danger).'],
        ['icon' => 'check_circle', 'label' => 'انجام‌شده', 'hint' => 'فقط وضعیت done.'],
        ['icon' => 'priority_high', 'label' => 'گذشته از موعد (overdue)', 'hint' => 'ضرب‌الاجل رد شده و وضعیت انجام‌شده نیست؛ نشان قرمز. فیلترِ هم‌نام همین شرط را روی جدول اعمال می‌کند.'],
        ['icon' => 'logout', 'label' => 'محول‌شده (delegated)', 'hint' => 'assigned_to پر است و با user_id متفاوت است. فیلتر سه‌حالتهٔ «محول‌شده» همین را اعمال می‌کند.'],
        ['icon' => 'delete', 'label' => 'حذف‌شده (trashed)', 'hint' => 'فقط ردیف‌های حذف‌شدهٔ نرم (onlyTrashed)؛ نشان قرمز. زبانه‌ها فقط وقتی نشان داده می‌شوند که اولویت «show_list_tabs» کاربر روشن باشد.'],
    ];

    $filters = [
        ['label' => 'وضعیت', 'hint' => 'SelectFilter روی ستون status با گزینه‌های enum.'],
        ['label' => 'پروژه', 'hint' => 'SelectFilter روی رابطهٔ project (project_id)؛ فقط وظایفِ همان پروژه را نشان می‌دهد.'],
        ['label' => 'اولویت', 'hint' => 'SelectFilter روی ستون priority.'],
        ['label' => 'ایجادکننده / مسئول انجام', 'hint' => 'دو SelectFilter مستقل با جستجو و پیش‌بارگذاری.'],
        ['label' => 'محول‌شده', 'hint' => 'TernaryFilter سه‌حالته: محول‌شده / محول‌نشده (assigned_to != user_id).'],
        ['label' => 'آرشیو', 'hint' => 'TernaryFilter سه‌حالته: آرشیو‌شده / فعال (archived_at).'],
        ['label' => 'سطل زباله (Trashed)', 'hint' => 'فیلتر سه‌حالتهٔ Filament روی deleted_at.'],
        ['label' => 'تاریخ ثبت', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد.'],
        ['label' => 'گذشته از موعد', 'hint' => 'فیلتر toggle: ضرب‌الاجل گذشته + غیر از انجام‌شده.'],
        ['label' => 'در آستانه حذف', 'hint' => 'فیلتر toggle: deleted_at <= ' . convertToPersian('30') . ' روز پیش — ردیف‌هایی که هرس خودکار قریب‌الوقوع است.'],
    ];

    $groups = [
        ['label' => 'گروه بر اساس وضعیت', 'hint' => 'ردیف‌ها را زیر هر وضعیتِ enum جمع می‌کند.'],
        ['label' => 'گروه بر اساس ایجادکننده / مسئول', 'hint' => 'دو گروه‌بندی مستقل، هرکدام قابل جمع‌شدن (collapsible).'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tab</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌ها و فیلترهای فهرست</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست وظایف هشت زبانه دارد که شمارشِ پنج‌گانهٔ آن‌ها از یک کوئریِ واحدِ SQL محاسبه می‌شود. فیلترها روی همان کوئریِ پایه (که حذف‌شده‌های نرم را هم می‌بیند) اعمال می‌شوند.
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
                شمارشِ زبانه‌ها (todo/in-progress/pending/overdue/trashed) همه از یک کوئریِ تجمیعی می‌آید — با خاموش‌کردن اولویت «show_list_tabs» نوار زبانه‌ها کامل پنهان می‌شود.
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