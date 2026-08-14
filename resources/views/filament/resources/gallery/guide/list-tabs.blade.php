@php
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام گالری‌ها — بدون فیلتر اضافه. زبانهٔ پیش‌فرض.'],
        ['label' => 'دارای تاریخ رویداد', 'hint' => 'فقط رکوردهایی که event_date پر است. شمارش به‌صورت نشانِ سبز روی زبانه می‌نشیند (با کوئری SUM یک‌باره).'],
        ['label' => 'بدون تاریخ رویداد', 'hint' => 'فقط رکوردهایی که event_date خالی است. شمارش به‌صورت نشانِ خاکستری. این رکوردها در انتهای فهرستِ مرتب‌شدهٔ نزولی روی event_date می‌نشینند.'],
    ];
    $filters = [
        ['label' => 'دسترسی (Ternary)', 'hint' => 'عمومی در برابر خصوصی — بر اساس پر یا خالی بودن department_id/departments.'],
        ['label' => 'نوع اشتراک (Ternary)', 'hint' => 'چند واحدی (departments غیرخالی) در برابر تک واحدی (department_id با departments خالی).'],
        ['label' => 'واحد سازمانی (Select)', 'hint' => 'جستجوپذیر و preload؛ رکوردهایی که واحدِ انتخاب‌شده در department_id یا در آرایهٔ departments دارند.'],
        ['label' => 'بازه تاریخ رویداد (From/Until)', 'hint' => 'بازهٔ تاریخ رویداد با دو DatePicker غیرِبومی؛ روی ستون event_date با whereDate اعمال می‌شود.'],
    ];
    $groups = [
        ['label' => 'بر اساس واحد سازمانی', 'hint' => 'گالری‌ها را بر اساس همهٔ واحدهایشان (all_department_models) گروه‌بندی می‌کند — رکوردهای عمومی زیر عنوان «عمومی» می‌نشینند. گروه قابل جمع‌شدن است.'],
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