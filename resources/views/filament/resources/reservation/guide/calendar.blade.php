@php
    $steps = [
        [
            'icon' => 'event',
            'label' => 'یک رویداد تقویم مشترک ساخته می‌شود',
            'hint' => 'برای رزرو فعال از نوع «ملاقات»، یک رویداد خصوصی در تقویم ساخته می‌شود. مالک رویداد رزروکننده است و عنوان آن نام رزروکننده و شخص مرتبط را نشان می‌دهد.',
        ],
        [
            'icon' => 'group_add',
            'label' => 'یک اشتراک رویداد برای شخص مرتبط ساخته می‌شود',
            'hint' => 'همان رویداد با شخص مرتبط با منبع ملاقات، که نامش با نام منبع یکی است، به اشتراک گذاشته می‌شود. تنظیم جدیدی لازم نیست. اشتراک‌های اضافی در همگام‌سازی پاک می‌شوند.',
        ],
        [
            'icon' => 'notifications_active',
            'label' => 'زنگ بلافاصله، نشان قرمز ظرف ۲۴ ساعت',
            'hint' => 'اگر زمان جلسه نرسیده باشد، با اشتراک رویداد زنگ برای دعوت‌شده به صدا درمی‌آید. نشان قرمز در ۲۴ ساعت پیش از جلسه روشن می‌شود.',
        ],
        [
            'icon' => 'lock',
            'label' => 'رویداد تقویمِ رزرو فقط‌خواندنی است',
            'hint' => 'در تقویم، دکمه‌های ویرایش، حذف و اشتراک رویداد رزرو غیرفعال‌اند؛ حتی برای رزروکننده. دکمهٔ «مشاهده رزرو» به صفحهٔ رزرو می‌رود. ویرایش یا حذف دستی در تقویم با همگام‌سازی بعدی بازنویسی می‌شود؛ تغییرات را از صفحهٔ مدیریت رزرو انجام دهید.',
        ],
        [
            'icon' => 'sync',
            'label' => 'لغو یا تغییر رزرو، تقویم را همگام می‌کند',
            'hint' => 'با تغییر یا حذف رزرو در پنل مدیریت یا بخش کاربر، تقویم خودکار به‌روز می‌شود. با لغو یا آزادسازی رزرو، رویداد آن از تقویم پاک می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">رزرو ملاقات به‌صورت خودکار در تقویم دعوت‌شده ظاهر می‌شود</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        وقتی یک کاربر منبعِ نوع «ملاقات» را رزرو می‌کند، سیستم یک رویداد تقویم مشترک می‌سازد و آن را با همکارِ مرتبط به اشتراک می‌گذارد — نه دو رویداد آینه‌ای جدا. این تنها برای نوع «ملاقات» و وضعیت «فعال» اتفاق می‌افتد؛ سایر انواع منبع (میز کار/پارکینگ/خودرو) تقویم نمی‌سازند.
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
                با به‌روزرسانی رزرو، اشتراک‌های مربوط به افراد دیگر و رویدادهای هم‌عنوانِ متعلق به دیگران، به‌جز رزروکننده، پاک می‌شوند. اگر نام منبع ملاقات تغییر کند یا منبع حذف شود، همگام‌سازی همچنان انجام می‌شود. رویدادهای باقی‌مانده از روش قدیمی نیز در همگام‌سازی یا پاک‌سازی بعدی حذف می‌شوند.
            </p>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «جلسه در تقویمم نیست»، اول وضعیت رزرو را بررسی کنید: فقط رزرو «فعال» از نوع «ملاقات» تقویم می‌سازد. لغو/آزادشده تقویم را پاک می‌کند.
            </p>
        </div>
    </div>
</div>