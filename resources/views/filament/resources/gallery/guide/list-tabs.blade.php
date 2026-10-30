@php
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام گالری‌ها — بدون فیلتر اضافه. زبانهٔ پیش‌فرض.'],
        ['label' => 'دارای تاریخ رویداد', 'hint' => 'فقط گالری‌هایی که تاریخ رویداد دارند. تعدادشان با نشان سبز روی زبانه دیده می‌شود.'],
        ['label' => 'بدون تاریخ رویداد', 'hint' => 'فقط گالری‌هایی که تاریخ رویداد ندارند. تعدادشان با نشان خاکستری دیده می‌شود و این گالری‌ها در انتهای فهرست قرار می‌گیرند.'],
    ];
    $filters = [
        ['label' => 'دسترسی', 'hint' => 'عمومی یا خصوصی؛ بر اساس اینکه واحد سازمانی انتخاب شده باشد یا نه.'],
        ['label' => 'نوع اشتراک', 'hint' => 'چندواحدی (چند واحد انتخاب‌شده) یا تک‌واحدی (فقط یک واحد انتخاب‌شده).'],
        ['label' => 'واحد سازمانی', 'hint' => 'قابل جستجو؛ گالری‌هایی را نشان می‌دهد که واحد انتخاب‌شده در یکی از دو فیلد واحد سازمانی آن‌ها آمده باشد.'],
        ['label' => 'بازهٔ تاریخ رویداد (از / تا)', 'hint' => 'با انتخاب «از تاریخ» و «تا تاریخ» (تقویم شمسی)، گالری‌های همان بازه نشان داده می‌شوند.'],
    ];
    $groups = [
        ['label' => 'بر اساس واحد سازمانی', 'hint' => 'گالری‌ها را بر اساس واحدهایشان گروه‌بندی می‌کند؛ گالری‌های عمومی زیر عنوان «عمومی» می‌آیند. گروه قابل جمع‌شدن است.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_alt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌های فهرست بر اساس «تاریخ رویداد» و چهار فیلتر برای محدود کردن جدول</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        سه زبانهٔ فهرست، گالری‌ها را بر اساس داشتن یا نداشتنِ تاریخ رویداد جدا می‌کنند. زبانه‌ها به‌صورت یک تنظیمِ نمایشی کاربر قابل غیرفعال‌شدن هستند (تنظیم <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">show_list_tabs</code>)؛ اگر خاموش باشند، فهرست بدون زبانه نمایش داده می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tab</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های فهرست</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">tab</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_list</span>
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                شمارشِ زبانه‌ها (داشتن/نداشتنِ تاریخ) با یک کوئریِ <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">SUM(CASE WHEN ...)</code> و کشِ <code class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container)] font-mono text-[10px]">once()</code> محاسبه می‌شود — فقط یک بار per render، نه به ازای هر زبانه.
            </p>
        </div>
    </div>
</div>