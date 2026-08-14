@php
    $tabs = [
        ['icon' => 'list_alt', 'label' => 'همه', 'hint' => 'تمام پروفایل‌ها — بدون فیلتر اضافه. این زبانه پیش‌فرض است.'],
        ['icon' => 'check_circle', 'label' => 'در حال کار', 'hint' => 'فقط پروفایل‌هایی با employment_status = working. تعداد به‌صورت نشان روی زبانه می‌نشیند.'],
        ['icon' => 'hourglass_top', 'label' => 'آزمایشی', 'hint' => 'فقط پروفایل‌های آزمایشی (probational). نشان زرد.'],
        ['icon' => 'cancel', 'label' => 'پایان همکاری', 'hint' => 'فقط پروفایل‌های خاتمه‌یافته (terminated). نشان قرمز.'],
    ];
    $filters = [
        ['icon' => 'flag', 'label' => 'وضعیت اشتغال', 'hint' => 'فیلتر بر اساس EmploymentStatus (آزمایشی / در حال کار / خاتمه‌یافته).'],
        ['icon' => 'work', 'label' => 'نوع اشتغال', 'hint' => 'فیلتر بر اساس EmploymentType (تمام‌وقت / پاره‌وقت / قراردادی).'],
        ['icon' => 'person', 'label' => 'جنسیت', 'hint' => 'فیلتر بر اساس جنسیت.'],
        ['icon' => 'school', 'label' => 'تحصیلات', 'hint' => 'فیلتر بر اساس مدرک تحصیلی (Degree).'],
        ['icon' => 'domain', 'label' => 'واحد سازمانی', 'hint' => 'فیلتر بر اساس کد واحد. گزینه‌ها از کشِ واحدها می‌آیند.'],
        ['icon' => 'list_alt', 'label' => 'دارای اطلاعات تکمیلی', 'hint' => 'فیلتر سه‌حالته: فقط دارای details / فقط بدون details. مبتنی بر رابطهٔ HasMany.'],
    ];
    $groups = [
        ['label' => 'بر اساس واحد', 'hint' => 'گروه‌بندی بر اساس department (با displayLabel واحد).'],
        ['label' => 'بر اساس سمت', 'hint' => 'گروه‌بندی بر اساس Position — رئیس هیئت مدیره، مدیرعامل، مدیر ارشد، … کارمند.'],
        ['label' => 'بر اساس وضعیت/نوع اشتغال و جنسیت', 'hint' => 'سه گروه‌بندی دیگر روی employment_status، employment_type و gender.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tab</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌های فهرست بر اساس وضعیت اشتغال</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست پروفایل‌ها به چهار زبانه تقسیم می‌شود. نشانِ تعداد روی هر زبانه از یک کوئریِ واحد (یک‌بار در هر بارگذاری) محاسبه می‌شود. زبانه‌ها فقط وقتی نمایش داده می‌شوند که تنظیم «show_list_tabs» کاربر فعالی باشد؛ در غیر این صورت فهرست بدون زبانه و فیلتر باز می‌شود.
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
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">{{ $t['icon'] }}</span>
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ستون‌های زیادی (موبایل، جنسیت، نوع اشتغال، تاریخ شروع، تعداد details) به‌صورت پیش‌فرض پنهان‌اند و با Toggle ظاهر می‌شوند — فقط آواتار، نام، سمت، وضعیت و کد پرسنلی نمای اولیه‌اند.
            </p>
        </div>
    </div>
</div>