@php
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام آگهی‌ها — بدون فیلتر اضافه.'],
        ['label' => 'فعال', 'hint' => 'فقط آگهی‌های active=true. شمارش به‌صورت نشانِ سبز روی زبانه می‌نشیند.'],
        ['label' => 'غیرفعال', 'hint' => 'فقط آگهی‌های active=false (پنهان از پنل کاربر). نشانِ قرمز.'],
        ['label' => 'آقایان', 'hint' => 'فقط آگهی‌های جنسیت Male. نشانِ آبی.'],
        ['label' => 'خانم‌ها', 'hint' => 'فقط آگهی‌های جنسیت Female. نشانِ صورتی.'],
    ];
    $filters = [
        ['label' => 'وضعیت (سه‌حالته)', 'hint' => 'فقط فعال / فقط غیرفعال — فیلتر TernaryFilter روی ستون active.'],
        ['label' => 'جنسیت', 'hint' => 'SelectFilter با گزینه‌های enum AdGender (آقایان/خانم‌ها/همه).'],
        ['label' => 'دارای سابقه کاری', 'hint' => 'فیلتر سه‌حالته: فقط آگهی‌های دارای experience / فقط بدون experience (whereNotNull / whereNull).'],
        ['label' => 'دارای شرط مدرک', 'hint' => 'فیلتر سه‌حالته روی certificate (null/not-null).'],
        ['label' => 'دارای شرط مهارت', 'hint' => 'فیلتر سه‌حالته روی skill (null/not-null).'],
        ['label' => 'بازه تاریخ ایجاد', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد آگهی (createdAtFilter مشترک از trait FilamentFilters).'],
    ];
    $groups = [
        ['label' => 'گروه‌بندی بر اساس جنسیت', 'hint' => 'آگهی‌ها را به ازای جنسیت گروه‌بندی می‌کند — هر گروه با برچسب enum و قابل جمع‌شدن.'],
        ['label' => 'گروه‌بندی بر اساس وضعیت', 'hint' => 'آگهی‌ها را فعال/غیرفعال گروه‌بندی می‌کند — برای تفکیک سریعِ آگهی‌های پنهان.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">view_list</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌ها، فیلترها و گروه‌بندیِ فهرست</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست آگهی‌ها پنج زبانهٔ بالایی دارد که با یک کوئریِ واحد شمارش‌شده‌اند و شش فیلتر و دو گروه‌بندی. زبانه‌ها با ترجیحِ کاربر (show_list_tabs) قابل پنهان‌سازی هستند.
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                شمارشِ زبانه‌ها (فعال/غیرفعال/آقایان/خانم‌ها) در یک کوئریِ واحد با selectRaw و CASE WHEN محاسبه می‌شود — نه چهار کوئری جدا.
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