@php
    $enums = [
        [
            'icon' => 'shield_person',
            'label' => 'نقش (role)',
            'hint' => 'سه مقدار: «کاربر» (user)، «مدیر» (admin)، «توسعه‌دهنده» (developer). فقط مدیر و توسعه‌دهنده می‌توانند وارد پنل ادمین شوند؛ کاربر عادی به پنل دسترسی ندارد. نقشِ «توسعه‌دهنده» در فرم ساخت غیرفعال است و فقط از مسیر دیتابیس قابل افزودن است.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'وضعیت (status)',
            'hint' => 'فعال / غیرفعال / تعلیق‌شده. کاربر غیرفعال یا تعلیق‌شده نمی‌تواند منبع رزرو کند و از رتبه‌بندی و آمار فعال حذف می‌شود، ولی ورودش به سیستم مسدود نمی‌شود.',
        ],
        [
            'icon' => 'badge',
            'label' => 'نوع (type)',
            'hint' => 'کارمند / پیمانکار / کارآموز / مهمان / ویژه. نوعِ «مهمان» (guest) کاربر را از تخته وضعیت همکاران پنهان می‌کند بدون تأثیر بر سایر بخش‌ها. نوع روی فیلترها و گزارش‌های منابع انسانی اثر دارد.',
        ],
        [
            'icon' => 'event_available',
            'label' => 'حضور (presence)',
            'hint' => 'فیلد جداگانه از وضعیت — بر فیلترهای حضور و غیاب و گزارش‌های سازمانی تأثیر می‌گذارد. به‌صورت enum ذخیره می‌شود و در ستون جدول و اینفولیست به‌صورت badge با رنگ خودش نمایش داده می‌شود.',
        ],
    ];
    $json = [
        [
            'icon' => 'event_repeat',
            'label' => 'مجوزهای رزرو (booking)',
            'hint' => 'یک Repeater با کلید/فعال. پنج کلید پیش‌فرض وجود دارد: all و car و seat و spot و meeting. کلید «all» یک دکمهٔ جامع است — روشن کردنش همهٔ مجوزها را یکجا فعال می‌کند و خاموش کردنِ هر کلید دیگر، «all» را خودکار خاموش می‌کند. در دیتابیس به‌صورت JSON ذخیره و هنگام خواندن به‌صورت آرایهٔ کلید⇄مقدار مسطح می‌شود.',
        ],
        [
            'icon' => 'edit_note',
            'label' => 'اطلاعات اضافه (extra)',
            'hint' => 'فیلد KeyValue که کلید/مقدار آزاد می‌گیرد. هر کلیدی که اینجا وارد کنید به‌صورت خودکار در فیلترها و گروه‌بندی‌های تخته وضعیت همکاران ظاهر می‌شود — مگر اینکه با _ (زیرخط) شروع شود. در دیتابیس زیر مسیر extra.admin نگه داشته می‌شود و بخش preferences کاربر دست‌نخورده باقی می‌ماند.',
        ],
        [
            'icon' => 'counter_4',
            'label' => 'حداکثر رزرو ماهانه (maximum)',
            'hint' => 'عدد صحیح، پیش‌فرض ۱۲، حداقل ۱. حداکثر تعداد رزروهای فعالِ همزمان در یک ماه تقویمی برای این کاربر.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«کاربر» همان حساب ورود است؛ مشخصات پرسنلی در پروفایلِ وصل‌شده می‌نشیند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف یک حساب کاربری است: نام، ایمیل، رمز عبور، نقش، وضعیت، نوع، حضور، مجوزهای رزرو و اطلاعات اضافه. اطلاعات پرسنلی (کد پرسنلی، واحد، سمت، تماس، تصویر) در رکوردِ «پروفایل» وصل‌شده ذخیره می‌شود و زیرِ صفحهٔ ویرایش هر کاربر، مدیریت ارتباط «پروفایل» آن را نمایش می‌دهد. این ماژول کاملاً مدیریتی است — پنل کاربری جداگانه‌ای برای مدیریت کاربران وجود ندارد؛ کاربران عادی حتی نمی‌توانند وارد پنل ادمین شوند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">person</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چهار فیلد کلیدیِ دسترسی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($enums as $e)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $e['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $e['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $e['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                تصویرِ کاربر از پروفایل می‌آید — اگر تصویر پروفایل خالی باشد، یک آواتارِ حروفی از نام کاربر ساخته می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">database</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">فیلدهای JSON و عددی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($json as $j)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $j['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $j['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $j['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                جستجوی سراسری پنل، کاربران را با «نام» یا «ایمیل» پیدا می‌کند و مستقیم به صفحهٔ ویرایش می‌رود.
            </p>
        </div>
    </div>
</div>