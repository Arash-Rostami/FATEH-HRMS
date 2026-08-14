@php
    $tabs = [
        ['icon' => 'list', 'label' => 'همه', 'hint' => 'بدون فیلتر — همهٔ فیدها به‌ترتیب جدیدترین.'],
        ['icon' => 'event_available', 'label' => 'امروز', 'hint' => 'فیدهای امروز با شمارش زنده (نشان سبز).'],
        ['icon' => 'photo_library', 'label' => 'دارای مدیا', 'hint' => 'فیدهایی که رسانه دارند (نشان آبی).'],
        ['icon' => 'ballot', 'label' => 'نظرسنجی', 'hint' => 'فقط دستهٔ نظرسنجی (نشان زرد).'],
    ];

    $filters = [
        ['icon' => 'category', 'label' => 'دسته', 'hint' => 'انتخاب از میان پنج دستهٔ فید.'],
        ['icon' => 'person', 'label' => 'نویسنده', 'hint' => 'فیلتر بر اساس کاربر سازنده (با جستجو).'],
        ['icon' => 'event_available', 'label' => 'تاریخ ساخت', 'hint' => 'بازهٔ تاریخ ساخت (فیلتر مشترک همهٔ منابع).'],
        ['icon' => 'photo_library', 'label' => 'دارای مدیا', 'hint' => 'فقط فیدهایی که media_paths غیرپر و دارای عضو است (JSON_LENGTH).'],
    ];

    $cols = [
        ['icon' => 'image', 'label' => 'پیش‌نمایش رسانه', 'hint' => 'تصویر اول فید (اگر تصویری باشد).'],
        ['icon' => 'person', 'label' => 'نویسنده + دسته', 'hint' => 'نام کاربر و دسته به‌صورت نشان با رنگ اختصاصی.'],
        ['icon' => 'edit_note', 'label' => 'محتوا', 'hint' => 'نمایش ۴۰ کاراکتر با line-clamp و tooltip متن کامل.'],
        ['icon' => 'forum', 'label' => 'شمارش‌ها', 'hint' => 'نظر (آبی)، واکنش (زرد)، رأی (سبز)، تعداد رسانه (خاکستری‌روشن) — هرکدام قابل مخفی/نمایش.'],
        ['icon' => 'event_available', 'label' => 'تاریخ', 'hint' => 'تاریخ ساخت شمسی، پیش‌فرض مخفی.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_alt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌ها، فیلترها و ستون‌های فهرست</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست فیدها چهار زبانهٔ شمارش‌دار دارد که همگی از یک کوئری آماری واحد تغذیه می‌شوند. زبانه‌ها با تنظیم <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">show_list_tabs</code> کاربر قابل خاموش‌اند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">view_list</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های فهرست</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
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
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                شمارش زبانه‌ها همگی از یک کوئری خام واحد می‌آیند — برای زبانهٔ «دارای مدیا» فقط <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">media_paths IS NOT NULL</code> بررسی می‌شود، پس فید با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">[]</code> هم شمرده می‌شود. فیلتر «دارای مدیا» دقیق‌تر است (JSON_LENGTH).
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-secondary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-secondary-container)]">view_column</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-secondary-container)]">ستون‌های جدول</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($cols as $col)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $col['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $col['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $col['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>