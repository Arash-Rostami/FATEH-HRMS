@php
    $tabs = [
        ['icon' => 'list_alt', 'label' => 'همه', 'hint' => 'تمام پروفایل‌ها — بدون فیلتر اضافه. این زبانه پیش‌فرض است.'],
        ['icon' => 'check_circle', 'label' => 'در حال کار', 'hint' => 'فقط پروفایل‌هایی که وضعیت استخدامشان «در حال کار» است. تعداد آن‌ها روی زبانه نمایش داده می‌شود.'],
        ['icon' => 'hourglass_top', 'label' => 'آزمایشی', 'hint' => 'فقط پروفایل‌های آزمایشی؛ تعداد با نشان زرد نمایش داده می‌شود.'],
        ['icon' => 'cancel', 'label' => 'پایان همکاری', 'hint' => 'فقط پروفایل‌های خاتمه‌یافته؛ تعداد با نشان قرمز نمایش داده می‌شود.'],
    ];
    $filters = [
        ['icon' => 'flag', 'label' => 'وضعیت استخدام', 'hint' => 'فیلتر بر اساس وضعیت استخدام (آزمایشی / در حال کار / خاتمه‌یافته).'],
        ['icon' => 'work', 'label' => 'نوع استخدام', 'hint' => 'فیلتر بر اساس نوع استخدام (تمام‌وقت / پاره‌وقت / قراردادی).'],
        ['icon' => 'person', 'label' => 'جنسیت', 'hint' => 'فیلتر بر اساس جنسیت.'],
        ['icon' => 'school', 'label' => 'تحصیلات', 'hint' => 'فیلتر بر اساس مقطع تحصیلی.'],
        ['icon' => 'domain', 'label' => 'واحد سازمانی', 'hint' => 'فیلتر بر اساس واحد.'],
        ['icon' => 'list_alt', 'label' => 'دارای اطلاعات تکمیلی', 'hint' => 'فیلتر سه‌حالته: فقط دارای اطلاعات تکمیلی / فقط بدون اطلاعات تکمیلی / همه.'],
    ];
    $groups = [
        ['label' => 'بر اساس واحد', 'hint' => 'گروه‌بندی بر اساس واحد.'],
        ['label' => 'بر اساس سمت', 'hint' => 'گروه‌بندی بر اساس سمت — رئیس هیئت‌مدیره، مدیرعامل، مدیر ارشد، … کارمند.'],
        ['label' => 'بر اساس وضعیت/نوع استخدام و جنسیت', 'hint' => 'سه گروه‌بندی دیگر بر اساس وضعیت استخدام، نوع استخدام و جنسیت.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tab</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌های فهرست بر اساس وضعیت استخدام</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست پروفایل‌ها به چهار زبانه تقسیم می‌شود. تعداد روی هر زبانه یک‌جا محاسبه می‌شود. زبانه‌ها فقط وقتی نمایش داده می‌شوند که گزینهٔ نمایش زبانه‌ها در تنظیمات کاربری شما روشن باشد؛ در غیر این صورت فهرست بدون زبانه و فیلتر باز می‌شود.
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
                ستون‌های زیادی (تلفن همراه، جنسیت، نوع استخدام، تاریخ شروع، تعداد اطلاعات تکمیلی) به‌صورت پیش‌فرض پنهان‌اند و با منوی ستون‌ها نمایش داده می‌شوند — فقط تصویر، نام، سمت، وضعیت و کد پرسنلی در ابتدا دیده می‌شوند.
            </p>
        </div>
    </div>
</div>