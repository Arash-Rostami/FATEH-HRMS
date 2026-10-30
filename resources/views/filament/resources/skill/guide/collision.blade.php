@php
    $flow = [
        ['icon' => 'label', 'label' => 'نام را وارد کنید', 'hint' => 'در فرم «ساخت مهارت» نام فارسی (و در صورت تمایل نام انگلیسی) را می‌نویسید. پیش از ذخیره، سیستم بررسی می‌کند که مهارت فعالی با همین نام نباشد.'],
        ['icon' => 'search', 'label' => 'بررسی نام تکراری', 'hint' => 'سیستم نام را هم در نام فارسی و هم در نام انگلیسی مهارت‌ها جستجو می‌کند و در این لحظه آن مورد را قفل می‌کند؛ پس اگر دو نفر هم‌زمان کار کنند، مهارت تکراری ساخته نمی‌شود.'],
        ['icon' => 'auto_awesome', 'label' => 'اگر تأمین‌نشده بود → فعال می‌شود', 'hint' => 'اگر مهارتی با آن نام پیدا شود و تأمین‌نشده (غیرفعال) باشد، همان مورد فعال می‌شود و نام انگلیسی، دسته‌بندی، توضیحات و نمادی که شما وارد کرده‌اید روی آن ثبت می‌شود. مورد تکراری ساخته نمی‌شود.'],
        ['icon' => 'report', 'label' => 'اگر نبود → ساخته می‌شود', 'hint' => 'اگر مهارتی با این نام پیدا نشود، یک مهارت تازهٔ فعال ساخته می‌شود. اگر در همین لحظه کس دیگری مهارتی با همان نام بسازد، سیستم خودکار همان را فعال می‌کند.'],
        ['icon' => 'warning', 'label' => 'اگر فعال بود → خطا', 'hint' => 'اگر مهارتی با آن نام هم‌اکنون فعال باشد، ساخت انجام نمی‌شود و پیام «مهارتی با این نام هم‌اکنون فعال است» نشان داده می‌شود — دو مهارت فعال نمی‌توانند هم‌نام باشند.'],
    ];

    $guards = [
        ['icon' => 'visibility_off', 'label' => 'ویرایشِ مهارت تأمین‌نشده ممکن نیست', 'hint' => 'دکمهٔ «ویرایش» برای مهارت‌های تأمین‌نشده نمایش داده نمی‌شود؛ این مهارت‌ها فقط باید فعال شوند. برای فعال‌سازی، مهارتی با همان نام بسازید (توضیح بالا).'],
        ['icon' => 'delete', 'label' => 'حذف وقتی دارندگان دارد مسدود است', 'hint' => 'دکمهٔ «حذف» برای مهارت‌هایی که دست‌کم یک دارنده دارند نمایش داده نمی‌شود. در حذف گروهی هم این مهارت‌ها کنار گذاشته می‌شوند و فقط بقیه حذف می‌شوند.'],
        ['icon' => 'warning', 'label' => 'محافظت دوم در سیستم', 'hint' => 'به‌عنوان محافظت دوم، سیستم هنگام حذف دوباره بررسی می‌کند که مهارت دارنده نداشته باشد و در غیر این صورت حذف را متوقف می‌کند — پس مهارتی که در حال استفاده است از هیچ مسیری حذف نمی‌شود.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">verified_user</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">ساخت مهارتِ هم‌نام، مهارت تأمین‌نشده را فعال می‌کند — مورد تکراری نمی‌سازد</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        وقتی مهارتی را می‌سازید که نامش با یک ghost یکی باشد، سیستم به‌جای ساخت مورد جدید، همان مهارت تأمین‌نشده را فعال می‌کند — تا شمارشِ جستجوها و تاریخچهٔ آن نام حفظ شود. تکراری نبودن نام هم در فارسی و هم در انگلیسی بررسی می‌شود؛ نام فارسیِ یک مهارت نمی‌تواند با نام انگلیسیِ مهارتی دیگر یکی باشد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">rule</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">مراحل ساخت و جلوگیری از نام تکراری</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($flow as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                هنگام «ساخت»، فقط مهارت‌های فعالِ هم‌نام مانع می‌شوند (مهارت‌های تأمین‌نشده مانع نیستند)؛ اما هنگام «ویرایش»، هر مهارتِ هم‌نامِ دیگری مانع است.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">shield</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">محافظت‌های خودکار سیستم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($guards as $g)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $g['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>