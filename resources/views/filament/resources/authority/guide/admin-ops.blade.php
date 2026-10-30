@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ جزئیات اختیار را باز می‌کند: شرح وظیفه، واحد، مسئول، زیرمجموعه، روش اجرایی، فراوانی تکرار، شاخص اثر، تفویض پیشنهادی و مصوب، تفویض مشترک و تاریخ‌های ایجاد/ویرایش — همگی شمسی و راست‌چین.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم دو‌بخشیِ اختیار را باز می‌کند: «اطلاعات کلی» (واحد، مسئول، زیرمجموعه، شرح وظیفه) و «جزئیات» (روش اجرایی، فراوانی تکرار، شاخص اثر، تفویض پیشنهادی و مصوب، تفویض مشترک). شرح وظیفه یک ویرایشگر متن‌غنی است.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» این اختیار را برای همیشه پاک می‌کند. حذف یک اختیار روی پروفایل کاربر یا واحد تأثیری نمی‌گذارد — فقط همین مورد حذف می‌شود.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی عملیات گروهی، روی موردهای انتخاب‌شده خروجی اکسل می‌گیرید. ستون‌ها: شناسه، واحد، مسئول، زیرمجموعه، شرح وظیفه، چهار ستون روش اجرایی، فراوانی تکرار، شاخص اثر و تفویض (با عنوان فارسی)، تفویض مشترک و تاریخ ایجاد (شمسی). خروجی روی کل فهرست فیلترشده هم ممکن است.',
        ],
    ];
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام اختیارات — بدون فیلتر اضافه.'],
        ['label' => 'وظایف اصلی', 'hint' => 'فقط وظایف مستقیم (گزینهٔ «وظایف زیرمجموعه» خاموش). تعداد آن‌ها با نشان آبی روی زبانه دیده می‌شود.'],
        ['label' => 'وظایف فرعی', 'hint' => 'فقط وظایف زیرمجموعه (گزینهٔ «وظایف زیرمجموعه» روشن) — وظایفی که روی زیرمجموعه‌ها هم اعمال می‌شوند. تعداد آن‌ها با نشان زرد دیده می‌شود.'],
    ];
    $filters = [
        ['label' => 'واحد سازمانی', 'hint' => 'انتخاب واحد، با امکان جستجو.'],
        ['label' => 'تفویض مصوب', 'hint' => 'فقط اختیارهایی را نشان می‌دهد که تفویض مصوب آن‌ها همان سطح انتخاب‌شده است.'],
        ['label' => 'شاخص اثر / روش اجرایی / فراوانی تکرار', 'hint' => 'سه فیلتر جداگانه؛ هر کدام فهرست را بر اساس مقدار همان فیلد محدود می‌کند.'],
        ['label' => 'وظایف زیرمجموعه', 'hint' => 'نمایش همه، فقط وظایف مستقیم یا فقط وظایف زیرمجموعه.'],
    ];
    $groups = [
        ['label' => 'بر اساس واحد', 'hint' => 'گروه‌بندی بر اساس واحد سازمانی؛ هر گروه قابل بستن است.'],
        ['label' => 'بر اساس تفویض / شاخص اثر / روش اجرایی / فراوانی', 'hint' => 'چهار گروه‌بندی بر اساس همین فیلدها، با عنوان فارسی هر مقدار. اختیارهای بدون مقدار در گروه «ثبت نشده» می‌آیند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: تعریف اختیارات، تفویض و نظارت بر وظایف</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر مورد در جدول یک اختیار سازمانی است. سه دکمهٔ عملیات روی هر ردیف موجود است (پس از سلول‌ها): مشاهده، ویرایش، حذف. دکمهٔ «ساخت اختیار» در هدر صفحه قرار دارد. جستجوی سراسری پنل نیز اختیارات را با نام واحد یا نام مسئول پیدا می‌کند و مستقیم به ویرایش می‌رود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات روی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ops as $op)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $op['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

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
                هر کاربر می‌تواند زبانه‌ها را از تنظیمات نمایش خود نمایان یا پنهان کند.
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ستونِ «وظایف زیرمجموعه» در جدول یک ToggleColumn است — می‌توانید مستقیماً روی سلولِ جدول آن را عوض کنید، بدون اینکه واردِ ویرایش بشوید.
            </p>
        </div>
    </div>
</div>