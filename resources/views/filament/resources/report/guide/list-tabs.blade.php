@php
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام گزارش‌ها — فعال و غیرفعال — بدون فیلتر اضافه.'],
        ['label' => 'فعال', 'hint' => 'فقط گزارش‌های active=true. شمارشِ این زبانه به‌صورت نشانِ سبز روی زبانه می‌نشیند و از یک کوئریِ once()-کش‌شده می‌آید.'],
        ['label' => 'غیرفعال', 'hint' => 'فقط گزارش‌های active=false. شمارش به‌صورت نشانِ قرمز. این گزارش‌ها در پنل کاربر ظاهر نمی‌شوند.'],
    ];
    $filters = [
        ['label' => 'وضعیت (active)', 'hint' => 'فیلتر سه‌حالته: فقط فعال / فقط غیرفعال. هم در فهرست و هم در مدیریت ارتباط قابل دسترس است.'],
        ['label' => 'واحد سازمانی (department_id)', 'hint' => 'فیلتر کش‌شده از گزینه‌های واحدها. گزارش‌های آن واحد را فیلتر می‌کند.'],
        ['label' => 'بازهٔ تاریخ ایجاد', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد (createdAtFilter) — از تاریخ / تا تاریخ، شمسی.'],
    ];
    $groups = [
        ['label' => 'بر اساس وضعیت (active)', 'hint' => 'گروه‌بندی فعال/غیرفعال، با قابلیت جمع‌شدن.'],
        ['label' => 'بر اساس واحد (department)', 'hint' => 'گروه‌بندی بر اساس برچسبِ نمایشی واحد (displayLabel)؛ گزارش‌های بدون واحد زیر گروه «-» می‌نشینند. قابل جمع است.'],
    ];
    $columns = [
        ['label' => 'شناسه / عنوان', 'hint' => 'شناسه سورت‌پذیر و عنوان جستجوپذیر (۴۵ کاراکتر برش). هر دو به‌صورت پیش‌فرض visible.'],
        ['label' => 'واحد / نویسنده', 'hint' => 'واحد به‌صورت badge اطلاعاتی با tooltipِ واحد، نویسنده به‌صورت نام. هر دو toggleable و به‌صورت پیش‌فرض مخفی.'],
        ['label' => 'نوع فایل', 'hint' => 'badge با رنگِ وابسته به فرمت: pdf→قرمز، docx/doc→آبی، بقیه→خاکستری. toggleable و پیش‌فرض مخفی.'],
        ['label' => 'وضعیت / تاریخ ایجاد', 'hint' => 'وضعیت آیکون بولینی (check/x). تاریخ ایجاد شمسی، سورت‌پذیر، toggleable و پیش‌فرض مخفی.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_list</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">فهرست گزارشات: زبانه‌ها، فیلترها و گروه‌بندی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ فهرست سه زبانهٔ بالا دارد که با تاگلِ show_list_tabs (در تنظیمات نمایش کاربر) قابل خاموش‌کردن هستند. فهرست به‌صورت پیش‌فرض بر اساس «تاریخ ایجاد» نزولی مرتب می‌شود و ردیف‌ها راهنمای رنگی (stripe) دارند.
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
                همهٔ ستون‌ها به جز شناسه و عنوان «toggleable» هستند — با کلیک روی آیکونِ ستون در هدر، ستون‌ها را نمایش یا مخفی کنید. واحد، نویسنده، نوع فایل و تاریخ ایجاد به‌صورت پیش‌فرض مخفی‌اند تا فهرست شلوغ نشود.
            </p>
        </div>
    </div>
</div>