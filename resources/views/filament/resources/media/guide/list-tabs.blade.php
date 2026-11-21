@php
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام رسانه‌ها — سری‌ها، موارد تک‌قسمتی و جلسه‌ها — بدون فیلتر اضافه.'],
        ['label' => 'سری', 'hint' => 'فهرست مجموعه‌های چندجلسه‌ای. تعداد آن‌ها با نشان آبی روی زبانه دیده می‌شود.'],
        ['label' => 'تک‌قسمت‌ها', 'hint' => 'موارد اصلی صوتی یا تصویری که سری نیستند. تعداد آن‌ها با نشان سبز دیده می‌شود.'],
        ['label' => 'جلسه‌ها', 'hint' => 'فهرست جلسه‌هایی که به یک سری تعلق دارند. تعداد آن‌ها با نشان نارنجی دیده می‌شود.'],
    ];
    $filters = [
        ['label' => 'وضعیت', 'hint' => 'نمایش همه، فقط فعال‌ها یا فقط غیرفعال‌ها.'],
        ['label' => 'واحد سازمانی', 'hint' => 'فقط رسانه‌هایی را نشان می‌دهد که موضوعشان واحد انتخاب‌شده است.'],
        ['label' => 'دسترسی', 'hint' => '«محدود» موارد دارای واحد مخاطب و «عمومی» موارد بدون واحد مخاطب را نشان می‌دهد.'],
        ['label' => 'نوع', 'hint' => 'فیلتر بر اساس نوع رسانه: سری، صوتی یا تصویری.'],
        ['label' => 'سطح', 'hint' => 'سری‌ها و فایل‌های مستقل را از جلسه‌های داخل سری جدا کنید.'],
        ['label' => 'بازهٔ تاریخ انتشار', 'hint' => 'رسانه‌های ثبت‌شده بین «از تاریخ» و «تا تاریخ» (شمسی).'],
    ];
    $groups = [
        ['label' => 'بر اساس وضعیت', 'hint' => 'فعال و غیرفعال را جدا می‌کند؛ هر گروه قابل بستن است.'],
        ['label' => 'بر اساس واحد', 'hint' => 'رسانه‌ها بر اساس نام واحد گروه‌بندی می‌شوند.'],
        ['label' => 'بر اساس سنجاق', 'hint' => 'سنجاق‌شده و سنجاق‌نشده را جدا می‌کند.'],
        ['label' => 'بر اساس نوع', 'hint' => 'سری، صوتی و تصویری را در سه گروه جدا نمایش می‌دهد.'],
    ];
    $columns = [
        ['label' => 'شناسه / عنوان / نوع', 'hint' => 'شناسه و عنوان همیشه نمایان‌اند؛ ستون «نوع» نشان رنگی سری/صوتی/تصویری است.'],
        ['label' => 'سری مربوط / مدت / آیکون', 'hint' => 'سری مربوط، مدت (به دقیقه) و برچسب فارسی آیکون؛ هر سه به‌صورت پیش‌فرض پنهان‌اند و از منوی ستون‌ها قابل نمایش‌اند.'],
        ['label' => 'واحد / نویسنده', 'hint' => 'واحد با نشان رنگی و نویسنده با نام؛ به‌صورت پیش‌فرض پنهان‌اند.'],
        ['label' => 'وضعیت / تاریخ ایجاد', 'hint' => 'وضعیت با علامت تیک یا ضربدر؛ تاریخ ایجاد شمسی و قابل مرتب‌سازی.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_list</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">فهرست رسانه‌ها: زبانه‌ها، فیلترها و گروه‌بندی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ فهرست چهار زبانهٔ بالا دارد که هر کاربر می‌تواند از تنظیمات نمایش خود خاموش کند. فهرست به‌صورت پیش‌فرض بر اساس «تاریخ ایجاد» نزولی مرتب می‌شود.
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
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها</p>
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
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">account_tree</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">گروه‌بندی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($groups as $g)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">account_tree</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">ستون‌های جدول</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($columns as $c)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-tertiary-container)]">table</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $c['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $c['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                جز شناسه، عنوان و نوع، همهٔ ستون‌ها قابل نمایش یا پنهان‌کردن‌اند؛ از آیکون ستون‌ها بالای جدول استفاده کنید.
            </p>
        </div>
    </div>
</div>
