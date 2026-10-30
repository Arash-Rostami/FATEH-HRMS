@php
    $tabs = [
        ['icon' => 'list', 'label' => 'همه', 'hint' => 'بدون فیلتر؛ همهٔ رویدادها.'],
        ['icon' => 'schedule', 'label' => 'پیش رو', 'hint' => 'رویدادهایی که زمانشان هنوز نرسیده است. تعداد آن‌ها با نشان سبز نمایش داده می‌شود.'],
        ['icon' => 'history', 'label' => 'گذشته', 'hint' => 'رویدادهایی که زمانشان گذشته است. با نشان خاکستری.'],
        ['icon' => 'public', 'label' => 'عمومی', 'hint' => 'فقط رویدادهای عمومی. با نشان آبی.'],
        ['icon' => 'lock', 'label' => 'خصوصی', 'hint' => 'فقط رویدادهای خصوصی. با نشان هشدار.'],
    ];

    $filters = [
        ['icon' => 'person', 'label' => 'فیلتر مخاطب یا سازنده', 'hint' => 'رویدادهای یک کاربر مشخص را نشان می‌دهد (به‌عنوان سازنده یا مخاطب رویداد خصوصی). نام کاربر را می‌توانید جستجو کنید.'],
        ['icon' => 'lock', 'label' => 'فیلتر عمومی یا خصوصی', 'hint' => 'سه حالت دارد: همه، فقط خصوصی، فقط عمومی.'],
        ['icon' => 'schedule', 'label' => 'فیلتر رویدادهای آینده', 'hint' => 'کلید روشن/خاموشی که فقط رویدادهای آینده را نشان می‌دهد؛ مثل زبانهٔ «پیش رو» ولی به‌صورت فیلتر.'],
        ['icon' => 'calendar_month', 'label' => 'فیلتر بازه تاریخ', 'hint' => 'با دو انتخابگر «از تاریخ» و «تا تاریخ»، رویدادهای آن بازه را نشان می‌دهد. تاریخ‌ها به‌طور پیش‌فرض شمسی‌اند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_list</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">فهرست رویدادها با پنج زبانهٔ آماده و چهار فیلتر</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانه‌ها با تنظیمات نمایش کاربر قابل خاموش شدن هستند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">view_carousel</span>
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $t['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر زبانه‌ها را در تنظیمات نمایش خاموش کند، همهٔ زبانه‌ها پنهان می‌شوند و فهرست بدون زبانه نمایش داده می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">tune</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">فیلترها و گروه‌بندی</p>
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $f['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
            <div class="flex items-start gap-4 p-5">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">splitscreen</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">گروه‌بندی بر اساس عمومی یا خصوصی</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">رویدادها را در دو دستهٔ «خصوصی» و «عمومی» نشان می‌دهد؛ هر دسته را می‌توانید ببندید یا باز کنید.</p>
                </div>
            </div>
        </div>
    </div>
</div>