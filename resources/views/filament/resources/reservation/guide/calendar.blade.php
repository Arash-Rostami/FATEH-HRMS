@php
    $steps = [
        [
            'icon' => 'event',
            'label' => 'یک رویداد تقویم مشترک ساخته می‌شود',
            'hint' => 'رزرو فعالِ ملاقات، اگر همکار مرتبط داشته باشد، در تقویم رزروکننده و همان همکار دیده می‌شود. رزرو پیوستهٔ چندروزه رویداد ملاقات نمی‌سازد.',
        ],
        [
            'icon' => 'group_add',
            'label' => 'یک اشتراک رویداد برای شخص مرتبط ساخته می‌شود',
            'hint' => 'همکار مرتبط، دعوت‌نامه را در تقویم خود می‌بیند. ثبت دعوت‌نامه به معنی پذیرفتن آن توسط همکار نیست؛ هماهنگی را جداگانه انجام دهید.',
        ],
        [
            'icon' => 'notifications_active',
            'label' => 'اعلان دعوت و یادآوری جلسه',
            'hint' => 'برای ملاقات آینده، اعلان دعوت ثبت می‌شود. نشان یادآوری نزدیک جلسه در ۲۴ ساعت پیش از آن ظاهر می‌شود.',
        ],
        [
            'icon' => 'lock',
            'label' => 'رویداد تقویمِ رزرو فقط‌خواندنی است',
            'hint' => 'اطلاعات ملاقات را در بخش رزرو تغییر دهید. رویداد ساخته‌شده در تقویم جداگانه قابل ویرایش، حذف یا اشتراک نیست.',
        ],
        [
            'icon' => 'sync',
            'label' => 'لغو یا تغییر رزرو، تقویم را همگام می‌کند',
            'hint' => 'با تغییر رزرو، رویداد مرتبط به‌روز می‌شود. با لغو یا آزادسازی، رویداد از تقویم کنار می‌رود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">رزرو ملاقات به‌صورت خودکار در تقویم دعوت‌شده ظاهر می‌شود</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فقط رزرو فعالِ ملاقات با همکار مرتبط، رویداد تقویم می‌سازد. ثبت رزروهای دیگر یا رزرو چندروزه، چنین رویدادی نمی‌سازد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">timeline</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">از رزرو تا تقویم — پنج گام</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($steps as $i => $step)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $step['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-[10px] font-black">{{ convertToPersian((string)($i + 1)) }}</span>
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $step['label'] }}</p>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $step['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">history_edu</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکتهٔ پاکسازی اشتراک‌های دستی</p>
        </div>
        <div class="p-5">
            <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium">
                رویداد رزرو را جداگانه با افراد دیگر به اشتراک نگذارید؛ دعوت‌های اضافی با به‌روز شدن رزرو کنار می‌روند. رزروکننده و همکار مرتبط، مخاطبان این رویداد هستند.
            </p>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برای بررسی نبودن دعوت‌نامه، نوع، وضعیت و همکار مرتبط را بررسی کنید. رزرو باید فعال و از نوع ملاقات باشد؛ رزرو پیوستهٔ چندروزه رویداد نمی‌سازد. با لغو یا آزادسازی، رویداد کنار می‌رود.
            </p>
        </div>
    </div>
</div>
