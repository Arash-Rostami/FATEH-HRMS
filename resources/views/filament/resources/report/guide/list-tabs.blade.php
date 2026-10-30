@php
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام گزارش‌ها — فعال و غیرفعال — بدون فیلتر اضافه.'],
        ['label' => 'فعال', 'hint' => 'فقط گزارش‌های فعال. تعداد آن‌ها با نشان سبز روی زبانه دیده می‌شود.'],
        ['label' => 'غیرفعال', 'hint' => 'فقط گزارش‌های غیرفعال. تعداد آن‌ها با نشان قرمز دیده می‌شود. این گزارش‌ها در پنل کاربر دیده نمی‌شوند.'],
    ];
    $filters = [
        ['label' => 'وضعیت', 'hint' => 'نمایش همه، فقط فعال‌ها یا فقط غیرفعال‌ها.'],
        ['label' => 'واحد سازمانی', 'hint' => 'فقط گزارش‌هایی را نشان می‌دهد که موضوعشان واحد انتخاب‌شده است.'],
        ['label' => 'بازهٔ تاریخ ایجاد', 'hint' => 'گزارش‌های ثبت‌شده بین «از تاریخ» و «تا تاریخ» (شمسی).'],
    ];
    $groups = [
        ['label' => 'بر اساس وضعیت', 'hint' => 'گروه‌بندی فعال/غیرفعال، با قابلیت جمع‌شدن.'],
        ['label' => 'بر اساس واحد', 'hint' => 'گزارش‌ها بر اساس نام واحد گروه‌بندی می‌شوند؛ گزارش‌های بدون واحد در گروه «-» قرار می‌گیرند. هر گروه قابل بستن است.'],
    ];
    $columns = [
        ['label' => 'شناسه / عنوان', 'hint' => 'شناسه قابل مرتب‌سازی و عنوان قابل جستجو است (عنوان‌های بلند کوتاه نمایش داده می‌شوند). هر دو همیشه نمایان‌اند.'],
        ['label' => 'واحد / نویسنده', 'hint' => 'واحد با نشان رنگی و نویسنده با نام نمایش داده می‌شود. هر دو به‌صورت پیش‌فرض پنهان‌اند و از منوی ستون‌ها قابل نمایش‌اند.'],
        ['label' => 'نوع فایل', 'hint' => 'نشان رنگی بر اساس فرمت: PDF قرمز، Word آبی، بقیه خاکستری. به‌صورت پیش‌فرض پنهان است.'],
        ['label' => 'وضعیت / تاریخ ایجاد', 'hint' => 'وضعیت با علامت تیک یا ضربدر نشان داده می‌شود. تاریخ ایجاد شمسی و قابل مرتب‌سازی است و به‌صورت پیش‌فرض پنهان است.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_list</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">فهرست گزارش‌ها: زبانه‌ها، فیلترها و گروه‌بندی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ فهرست سه زبانهٔ بالا دارد که هر کاربر می‌تواند از تنظیمات نمایش خود آن‌ها را خاموش کند. فهرست به‌صورت پیش‌فرض بر اساس «تاریخ ایجاد» نزولی مرتب می‌شود و ردیف‌ها نوار رنگی کنار خود دارند.
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
                جز شناسه و عنوان، همهٔ ستون‌ها قابل نمایش یا پنهان‌شدن‌اند؛ از آیکون ستون‌ها بالای جدول استفاده کنید. واحد، نویسنده، نوع فایل و تاریخ ایجاد به‌صورت پیش‌فرض مخفی‌اند تا فهرست شلوغ نشود.
            </p>
        </div>
    </div>
</div>