@php
    $panels = [
        [
            'icon' => 'calendar_month',
            'label' => 'تب تقویم',
            'hint' => 'کاربر رویدادها را در تب «تقویم» پنل کاربری می‌بیند — یک شبکهٔ ماهانهٔ شمسی در سمت چپ و فهرست رویدادهای روز انتخاب‌شده در سمت راست. روی هر روز که رویداد باشد، نشان داده می‌شود. تقویم علاوه بر رویدادها، تولدها، سالگردهای همکاری و تعطیلی‌ها را هم در همان فهرست روزانه نمایش می‌دهد.',
        ],
        [
            'icon' => 'lock',
            'label' => 'مرئیت رویداد',
            'hint' => 'رویداد عمومی برای همهٔ کاربران در تقویم دیده می‌شود. رویداد خصوصی فقط برای سازنده و کسانی که مستقیماً با آن‌ها به اشتراک گذاشته شده. کاربر فقط رویدادهایی را می‌بیند که `user_id` خودش باشد، یا عمومی باشد، یا برایش اشتراک گذاشته شده باشد.',
        ],
        [
            'icon' => 'edit',
            'label' => 'مالکیت و ویرایش',
            'hint' => 'دکمه‌های «اشتراک‌گذاری»، «ویرایش» و «حذف» فقط برای سازندهٔ رویداد (`is_owner`) نمایش داده می‌شوند. سایر کاربران رویداد را فقط می‌بینند. اشتراک‌گذاری یک رویداد خصوصی آن را فقط برای گیرندگان انتخاب‌شده قابل‌مشاهده می‌کند — رویداد همچنان از دید بقیه پنهان می‌ماند.',
        ],
        [
            'icon' => 'event_seat',
            'label' => 'رویدادهای رزرو',
            'hint' => 'رزرو یک منبع جلسه به‌صورت خودکار یک رویداد خصوصی برای رزروکننده می‌سازد و با طرف مقابل اشتراک می‌گذارد. این رویدادها با نشان «از طریق رزرو» نمایش داده می‌شوند. کاربر نمی‌تواند آن‌ها را از تقویم ویرایش یا حذف کند — برای تغییر باید به تب «رزرو» برود.',
        ],
        [
            'icon' => 'alarm',
            'label' => 'یادآوری و بنر شمارش معکوس',
            'hint' => 'اگر یادآوری تنظیم شده باشد، اعلانی `remind_hours` ساعت قبل از رویداد برای مخاطبان ارسال می‌شود. همچنین نزدیک‌ترین رویداد آیندهٔ عمومی با شمارش معکوسِ فعال، به‌صورت بنر سراسری در همهٔ صفحات نمایش داده می‌شود — کاربر می‌تواند آن را برای آن روز رد کند.',
        ],
        [
            'icon' => 'notifications_active',
            'label' => 'نشان «رویداد مشترک نزدیک است»',
            'hint' => 'وقتی یکی از رویدادهای مشترکِ کاربر در ۲۴ ساعت آینده باشد و کاربر امروز تقویم را ندیده باشد، یک نشان در منو ظاهر می‌شود. با باز کردن تب تقویم، این نشان برای آن روز پاک می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در تب تقویم چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربر رویدادها را از تب «تقویم» پنل کاربری مدیریت می‌کند — ساخت، ویرایش، حذف و اشتراک‌گذاری. وقتی کاربری از دیدن یا ویرایش رویداد شکایت می‌کند، این زبانه مرجع شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است. یک راهنمای جداگانه در همان تب تقویم به کاربر نمایش داده می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">قابلیت‌های پنل کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($panels as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $p['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «رویدادی را نمی‌توانم ویرایش کنم»، احتمالاً یا مالکِ آن نیست یا رویداد از طریق رزرو ساخته شده — در هر دو حالت دکمه‌های ویرایش/حذف برای او پنهان است.
            </p>
        </div>
    </div>
</div>