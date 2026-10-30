@php
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام آگهی‌ها — بدون فیلتر اضافه.'],
        ['label' => 'فعال', 'hint' => 'فقط آگهی‌های فعال. تعداد آن‌ها به‌صورت نشان سبز روی زبانه دیده می‌شود.'],
        ['label' => 'غیرفعال', 'hint' => 'فقط آگهی‌های غیرفعال (پنهان از کاربران). نشان قرمز.'],
        ['label' => 'آقایان', 'hint' => 'فقط آگهی‌های مخصوص آقایان. نشان آبی.'],
        ['label' => 'خانم‌ها', 'hint' => 'فقط آگهی‌های مخصوص خانم‌ها. نشان صورتی.'],
    ];
    $filters = [
        ['label' => 'وضعیت', 'hint' => 'فقط فعال، فقط غیرفعال، یا همه.'],
        ['label' => 'جنسیت', 'hint' => 'انتخاب یکی از گزینه‌ها: آقایان، خانم‌ها، همه.'],
        ['label' => 'دارای سابقه کاری', 'hint' => 'فقط آگهی‌هایی که سابقهٔ کاری دارند، فقط آن‌هایی که ندارند، یا همه.'],
        ['label' => 'دارای شرط مدرک', 'hint' => 'فقط آگهی‌هایی که شرط مدرک دارند، فقط آن‌هایی که ندارند، یا همه.'],
        ['label' => 'دارای شرط مهارت', 'hint' => 'فقط آگهی‌هایی که شرط مهارت دارند، فقط آن‌هایی که ندارند، یا همه.'],
        ['label' => 'بازهٔ تاریخ ایجاد', 'hint' => 'فیلتر بر اساس بازهٔ تاریخ ایجاد آگهی.'],
    ];
    $groups = [
        ['label' => 'گروه‌بندی بر اساس جنسیت', 'hint' => 'آگهی‌ها را به ازای جنسیت گروه‌بندی می‌کند — هر گروه با عنوان خودش و قابل بسته‌شدن است.'],
        ['label' => 'گروه‌بندی بر اساس وضعیت', 'hint' => 'آگهی‌ها را فعال/غیرفعال گروه‌بندی می‌کند — برای تفکیک سریعِ آگهی‌های پنهان.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">view_list</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌ها، فیلترها و گروه‌بندیِ فهرست</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست آگهی‌ها پنج زبانه در بالا دارد (با تعداد هر زبانه)، شش فیلتر و دو گروه‌بندی. زبانه‌ها را می‌توان از تنظیمات شخصی پنهان کرد.
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