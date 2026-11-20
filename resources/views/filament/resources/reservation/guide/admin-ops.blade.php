@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'جزئیات صاحب رزرو، مورد انتخابی، زمان، وضعیت و دلیل لغو را ببینید. نوبت‌های تکراری و رزرو اول گروه نیز مشخص‌اند.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'فرد، مورد، زمان و سایر اطلاعات رزرو را تغییر دهید. برای لغو عادی، از دکمهٔ «لغو رزرو» استفاده کنید تا قواعد گروه تکرار هم رعایت شوند. تغییرات رزرو ملاقات، تقویم مرتبط را هم به‌روز می‌کند.',
        ],
        [
            'icon' => 'cancel',
            'label' => 'لغو رزرو',
            'hint' => 'فقط برای رزرو فعال نمایش داده می‌شود. پس از تأیید، نام مدیر و زمان لغو ثبت می‌شود. پیش از تأیید، بررسی کنید لغو تک‌نوبت مجاز است یا نوبت‌های مرتبط هم لغو می‌شوند.',
        ],
        [
            'icon' => 'autorenew',
            'label' => 'آزادسازی',
            'hint' => 'رزرو در سوابق و سهمیهٔ ماهانه باقی می‌ماند و دیگر قابل لغو نیست. در رزرو پیوستهٔ شروع‌شده، زمان پایان به لحظهٔ آزادسازی تغییر می‌کند. برای رزرو ساعتی یا آینده، قابل رزرو شدن زمان به تنظیمات نوع بستگی دارد.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'رزرو و سابقهٔ آن پاک می‌شود. برای تغییر برنامه، معمولاً «لغو رزرو» مناسب‌تر است تا سابقه باقی بماند.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'اطلاعات رزروها را در فایل اکسل دریافت کنید؛ نام فرد، مورد، زمان، وضعیت و دلیل لغو در خروجی آمده است.',
        ],
    ];
    $filters = [
        ['label' => 'وضعیت', 'hint' => 'فیلتر بر اساس یکی از چهار وضعیت.'],
        ['label' => 'نوع مورد رزرو', 'hint' => 'فیلتر بر اساس نوع مورد رزرو (میز کار، پارکینگ، خودرو، ملاقات).'],
        ['label' => 'تمام‌روز', 'hint' => 'فقط رزروهای تمام‌روز، فقط ساعتی، یا همه.'],
        ['label' => 'دارای تکرار', 'hint' => 'نوبت‌های بعدیِ رزرو تکراری را جدا می‌کند. رزرو اول گروه، مثل رزرو تک‌نوبتی، در سمت بدون تکرار دیده می‌شود.'],
        ['label' => 'تاریخ ثبت', 'hint' => 'فیلتر بر اساس بازهٔ تاریخ ثبت رزرو.'],
    ];
    $groups = [
        ['label' => 'بر اساس کاربر', 'hint' => 'رزروها را به ازای هر کاربر گروه‌بندی می‌کند — مناسب برای بررسی فعالیت یک شخص.'],
        ['label' => 'بر اساس وضعیت', 'hint' => 'رزروهای فعال/آزادشده/لغو را در گروه‌های جداگانه می‌نمایاند.'],
        ['label' => 'بر اساس منبع', 'hint' => 'رزروها را به ازای هر منبع گروه‌بندی می‌کند — برای دیدن تقویم اشغال یک منبع خاص.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: نظارت، لغو، آزادسازی و خروجی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        دکمه‌های هر ردیف، کارهای مجاز شما را نشان می‌دهند. برای پیدا کردن رزرو، نام فرد یا مورد را جستجو کنید؛ برای دریافت چند ردیف، از خروجی گروهی استفاده کنید.
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">event_available</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">رزرو بلندمدت (ماهانه/سالانه)</p>
        </div>
        <div class="p-5 flex flex-col gap-2.5">
            <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium">
                برای استفادهٔ پیوسته در چند روز، «تمام‌روز» را خاموش بگذارید و تاریخ و ساعت شروع و پایان را وارد کنید. کل بازه یک رزرو است و در تمام این مدت اشغال محسوب می‌شود.
            </p>
            <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">
                اگر مدت ۲۴ ساعت یا بیشتر باشد، رزرو پیوسته است و با تکرار ترکیب نمی‌شود. سقف مدتِ رزرو بلندمدت، اگر تعیین شده باشد، همچنان رعایت می‌شود.
            </p>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)] flex flex-col gap-1.5">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">info</span>
                لغو روی کل بازه اثر دارد. برای کوتاه کردن، تاریخ پایان را ویرایش کنید. آزادسازیِ رزرو پیوستهٔ شروع‌شده، پایان را به زمان فعلی تغییر می‌دهد.
            </p>
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">cancel</span>
                برای رزرو بلندمدتِ آینده (هنوز شروع‌نشده) از «لغو رزرو» استفاده کنید، نه آزادسازی.
            </p>
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">block</span>
                برای خالی گذاشتن فاصله‌ای در وسط، رزرو را در آن نقطه پایان دهید و برای ادامه، رزرو جدا بسازید.
            </p>
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lock_person</span>
                ثبت رزرو پیوستهٔ چندروزه از فرم مدیریت انجام می‌شود. در صفحهٔ کاربر، تاریخ و بازهٔ ساعتی یا تمام‌روز انتخاب می‌شود.
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

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">کجا تقویمِ یک منبع را ببینید؟</p>
        </div>
        <div class="p-5">
            <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium">
                در صفحهٔ «منابع»، مورد دلخواه را باز کنید و بخش «رزروها» را ببینید. همهٔ رزروهای مرتبط با همان مورد در آن فهرست آمده‌اند.
            </p>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                روی صفحهٔ ویرایش یک رزروِ اصلی، بخش «تکرارها» ظاهر می‌شود و رزروهای تکراریِ آن سری را فهرست می‌کند.
            </p>
        </div>
    </div>
</div>
