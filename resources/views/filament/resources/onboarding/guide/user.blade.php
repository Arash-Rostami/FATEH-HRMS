@php
    $panels = [
        [
            'icon' => 'waving_hand',
            'label' => 'کارتی برای هر بخش پر شده',
            'hint' => 'کاربر در زبانهٔ «آنبوردینگ» صفحهٔ پروفایل، فقط بخش‌هایی را می‌بیند که شما پر کرده‌اید: خوش‌آمد، ویدیوها، مأموریت، چشم‌انداز، راهنماها، برنامهٔ روز اول و اطلاعات تکمیلی. بخش‌های خالی خودکار حذف می‌شوند — اگر هیچ محتوایی نباشد، حالتِ خالیِ «محتوایی بارگذاری نشده» نمایش داده می‌شود.',
        ],
        [
            'icon' => 'play_circle',
            'label' => 'ویدیوها در مودال پخش می‌شوند',
            'hint' => 'کلیک روی هر ویدیو یک مودالِ سراسری باز می‌کند (نه صفحهٔ جدید). در صورت وجود تصویر بندانگشتی نمایش داده می‌شود؛ وگرنه یک آیکونِ smart_display جایگزین می‌نشیند. مدت زمانِ دستیِ زیرِ ویدیو نشان داده می‌شود.',
        ],
        [
            'icon' => 'menu_book',
            'label' => 'راهنماها: PDF در زبانهٔ جدید، بقیه دانلود',
            'hint' => 'فایل‌های PDF در زبانهٔ جدیدِ مرورگر باز می‌شوند؛ سایر فرمت‌ها (Word/Excel) مستقیم دانلود می‌شوند. آیکون و رنگِ هر فایل از پسوندش گرفته می‌شود (PDF قرمز، Word آبی، Excel سبز) و پسوند به‌صورت برچسب روی کارت می‌نشیند.',
        ],
        [
            'icon' => 'campaign',
            'label' => 'اطلاعات تکمیلی: استاندارد در برابر سفارشی',
            'hint' => 'کلیدهای ازپیش‌تعریف‌شده (مثل parking یا wifi) کارت با حاشیهٔ یکپارچه و آیکونِ ثانویه می‌گیرند. کلیدهای خارج از کاتالوگ با حاشیهٔ نقطه‌چین، پس‌زمینهٔ مات و برچسب «سفارشی» نمایش داده می‌شوند — تا کاربر بفهمد این آیتمِ اختصاصیِ سازمان شماست.',
        ],
    ];

    $priority = [
        [
            'icon' => 'priority',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'نسخهٔ اختصاصی بر عمومی اولویت دارد',
            'hint' => 'اگر برای کاربری یک آنبوردینگِ اختصاصیِ فعال بسازید، آن کاربر دیگر نسخهٔ عمومی را نمی‌بیند — حتی اگر نسخهٔ عمومی هم فعال باشد. حذف یا غیرفعال‌کردنِ نسخهٔ اختصاصی، نسخهٔ عمومی را دوباره به او برمی‌گرداند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در پروفایل خود چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        آنبوردینگ یک زبانهٔ داخلِ صفحهٔ پروفایل است (نه یک ماژولِ مستقل) — کاربر در زبانهٔ «آنبوردینگ» از نوار کناریِ پروفایل وارد می‌شود و محتوای شما را به‌صورت کارت‌های خواندنی می‌بیند. وقتی کاربری می‌گوید «پیام خوش‌آمد نمی‌بینم»، این زبانه مرجعِ شما برای تشخیصِ علت است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">آنچه کاربر می‌بیند</p>
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $p['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">priority</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">رفتارِ اولویت از نگاه کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($priority as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $r['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «محتوای عمومی را نمی‌بینم»، احتمالاً یک نسخهٔ اختصاصیِ فعال برای او ساخته‌اید — آن را غیرفعال یا حذف کنید تا نسخهٔ عمومی دوباره نمایش داده شود.
            </p>
        </div>
    </div>
</div>