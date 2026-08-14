@php
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام کاربران — بدون فیلتر اضافه.'],
        ['label' => 'فعال', 'hint' => 'فقط کاربران با status=active. تعداد به‌صورت badge سبز روی زبانه.'],
        ['label' => 'غیرفعال', 'hint' => 'فقط کاربران با status=inactive. تعداد به‌صورت badge قرمز.'],
        ['label' => 'مخفی‌شده', 'hint' => 'فقط کاربران با type=guest (مهمان) — همان کاربرانی که از تخته وضعیت همکاران پنهان می‌شوند. badge خاکستری.'],
        ['label' => 'مدیران', 'hint' => 'کاربران با نقش admin یا developer. badge زرد. این زبانه فقط با نقش‌ها کار می‌کند نه وضعیت.'],
    ];
    $filters = [
        ['label' => 'وضعیت / نقش / نوع / حضور', 'hint' => 'چهار فیلتر انتخابی مستقل — هرکدام از enumهای خودشان گزینه‌ها را می‌خوانند.'],
        ['label' => 'بازه تاریخ ثبت', 'hint' => 'فیلتر بازهٔ زمانی created_at.'],
        ['label' => 'فیلترهای پویا (اطلاعات اضافه)', 'hint' => 'به‌ازای هر کلیدِ یکتا در extra.admin همهٔ کاربران، یک فیلتر انتخابی خودکار ساخته می‌شود. کلیدهایی که با _ شروع شوند فیلتر/گروه نمی‌شوند. نام فیلتر همان کلید است و گزینه‌ها مقادیر متمایز همان کلید.'],
    ];
    $groups = [
        ['label' => 'بر اساس وضعیت / نقش / حضور', 'hint' => 'گروه‌بندیِ ثابت بر اساس ستونهای enum — collapsible.'],
        ['label' => 'گروه‌بندیِ پویا (اطلاعات اضافه)', 'hint' => 'هر کلیدِ extra.admin یک گروهِ collapsible می‌سازد؛ عنوان گروه مقدارِ همان کلید برای هر کاربر است. کلیدهای هم‌نام با حروف بزرگ/کوچک یا فاصله با هم ادغام می‌شوند و پرتکرارترین نگارش برچسب می‌شود.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_alt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">پنج زبانهٔ فهرست + فیلترها و گروه‌بندی‌های ثابت و پویا</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست کاربران پنج زبانه دارد که شمارشِ هر دسته را به‌صورت badge نمایش می‌دهند. شمارش‌ها در یک پرس‌وجوی واحد محاسبه می‌شوند. زبانه‌ها به ترجیح نمایشی کاربر (show_list_tabs) احترام می‌گذارند — اگر خاموش باشد، زبانه‌ها پنهان می‌شوند.
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
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tune</span>
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">folder</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">گروه‌بندی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
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
                فیلترها و گروه‌های پویا از کلیدهای extra.admin ساخته می‌شوند — برای پنهان کردن یک کلید از فیلتر، آن را با _ شروع کنید. کشِ این کلیدها هر {{ convertToPersian('15') }} دقیقه بازسازی می‌شود.
            </p>
        </div>
    </div>
</div>