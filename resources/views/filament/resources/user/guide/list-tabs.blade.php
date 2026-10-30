@php
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام کاربران — بدون فیلتر اضافه.'],
        ['label' => 'فعال', 'hint' => 'فقط کاربران با وضعیت «فعال». تعداد با نشان سبز روی زبانه نمایش داده می‌شود.'],
        ['label' => 'غیرفعال', 'hint' => 'فقط کاربران با وضعیت «غیرفعال». تعداد با نشان قرمز روی زبانه نمایش داده می‌شود.'],
        ['label' => 'مخفی‌شده', 'hint' => 'فقط کاربران با نوع «مهمان» — همان کاربرانی که از تخته وضعیت همکاران پنهان می‌شوند. نشان خاکستری.'],
        ['label' => 'مدیران', 'hint' => 'کاربران با نقش «مدیر» یا «توسعه‌دهنده». نشان زرد. این زبانه فقط بر اساس نقش است، نه وضعیت.'],
    ];
    $filters = [
        ['label' => 'وضعیت / نقش / نوع / حضور', 'hint' => 'چهار فیلتر انتخابی مستقل — هر کدام گزینه‌های خودش را دارد.'],
        ['label' => 'بازهٔ تاریخ ثبت', 'hint' => 'فیلتر بازهٔ زمانی تاریخ ثبت.'],
        ['label' => 'فیلترهای پویا (اطلاعات اضافه)', 'hint' => 'به‌ازای هر عنوانِ یکتا در «اطلاعات اضافه»ی همهٔ کاربران، یک فیلتر انتخابی خودکار ساخته می‌شود. عنوان‌هایی که با _ شروع شوند فیلتر یا گروه نمی‌شوند. نام فیلتر همان عنوان است و گزینه‌ها مقادیر متفاوت همان عنوان.'],
    ];
    $groups = [
        ['label' => 'بر اساس وضعیت / نقش / حضور', 'hint' => 'گروه‌بندی ثابت بر اساس ستون‌های وضعیت، نقش و حضور؛ هر گروه قابل جمع‌شدن است.'],
        ['label' => 'گروه‌بندیِ پویا (اطلاعات اضافه)', 'hint' => 'هر عنوان در «اطلاعات اضافه» یک گروه قابل جمع‌شدن می‌سازد؛ نام گروه، مقدارِ آن عنوان برای هر کاربر است. عنوان‌های هم‌نامی که فقط در حروف بزرگ و کوچک یا فاصله فرق دارند یکی می‌شوند و پرکاربردترین نگارش به‌عنوان نام گروه می‌آید.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_alt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">پنج زبانهٔ فهرست + فیلترها و گروه‌بندی‌های ثابت و پویا</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست کاربران پنج زبانه دارد که تعداد هر دسته را با نشان نمایش می‌دهند. شمارش‌ها یک‌جا محاسبه می‌شوند. اگر گزینهٔ نمایش زبانه‌ها در تنظیمات کاربری خاموش باشد، زبانه‌ها پنهان می‌شوند.
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
                فیلترها و گروه‌های پویا از عنوان‌های «اطلاعات اضافه» ساخته می‌شوند — برای پنهان کردن یک عنوان از فیلتر، آن را با _ شروع کنید. فهرست این عنوان‌ها هر {{ convertToPersian('15') }} دقیقه به‌روز می‌شود.
            </p>
        </div>
    </div>
</div>