@php
    $panels = [
        [
            'icon' => 'account_tree',
            'label' => 'دو زبانه: سیستمی / غیر سیستمی',
            'hint' => 'صفحهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">/dms</code> کاربر دو زبانه دارد: «سیستمی» و «غیر سیستمی». کاربر فقط اسنادِ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">live</code> را می‌بیند و فقط اسنادی که به واحدش (یا همهٔ واحدها) یا مستقیماً به خودش رسیده است (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">scopeVisibleToUser</code>). با تعویض زبانه، جستجو و فیلتر بازنشانی می‌شوند.',
        ],
        [
            'icon' => 'edit_document',
            'label' => 'دو تأیید جداگانه',
            'hint' => 'هر سند دو دکمه دارد: «تأیید دریافت» (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">read=true</code>) یعنی «سند را دیده‌ام»، سپس «مشاهده و تأیید» (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">read_count&gt;0</code>) یعنی «محتوای آن را خوانده و پذیرفته‌ام». تا هر دو ثبت نشود، سند در کارتابل «اقدام مورد نیاز» می‌ماند.',
        ],
        [
            'icon' => 'error',
            'label' => 'بنر اقدام مورد نیاز',
            'hint' => 'وقتی سندِ تأییدنشده در کارتابل باشد، بنر قرمز بالای صفحه با دو شمارش نمایش داده می‌شود: «نیازمند تأیید دریافت» و «نیازمند تأیید مطالعه». کلیک روی هر شمارش، فهرست را به همان دسته فیلتر می‌کند. این بنر به‌جای اعلان زنگوله‌ای است — مدیریت اسناد از اعلان‌های پنل استفاده نمی‌کند.',
        ],
        [
            'icon' => 'sell',
            'label' => 'فیلتر با کلیک روی تگ/دسته',
            'hint' => 'در هر ردیف، کلیک روی تگِ دسته (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">extra.category</code>) جستجو را روی آن مقدار می‌گذارد، و کلیک روی هر تگِ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">tags</code> فیلتر <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">key|value</code> را فعال می‌کند. جستجوی متنی در عنوان، کد، نسخه، توضیحات و محتوای JSONِ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">extra</code>/<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">tags</code> می‌گردد.',
        ],
        [
            'icon' => 'history',
            'label' => 'مشاهده‌های اخیر و اولویت‌بندی',
            'hint' => 'کارتابل اسنادِ تأییدنشده/نخوانده را بالاتر می‌آورد (اولویت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">COALESCE</code> روی <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">read_count</code>). فهرست با «بارگذاری بیشتر» صفحه‌بندی می‌شود. بخش «اخیراً مشاهده‌شده» به‌صورت سمت‌کلاینت (localStorage) آخرین سندهای بازشده را نگه می‌دارد و قابل پاک‌کردن است.',
        ],
        [
            'icon' => 'view_column',
            'label' => 'تنظیمات جدول: ستون‌ها، تراکم، بزرگ‌نمایی',
            'hint' => 'با دکمهٔ تنظیمات در سرستون: نمایش/پنهان‌کردن ستون‌ها، تراکم ردیف‌ها (فشرده/عادی)، بازنشانی ترتیب و بزرگ‌نمایی جدول. این ترجیحات در مرورگر کاربر ذخیره می‌شود و برای سایر جداول هم به‌کار می‌رود.',
        ],
    ];

    $security = [
        [
            'icon' => 'verified_user',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'دسترسی فایل از طریق مسیر امن',
            'hint' => 'فایل‌ها از مسیر <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">/authorized/...</code> سرو می‌شوند نه از <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">storage/public</code> مستقیم. سرور قبل از ارسال، با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">DMS::visibleToUser()</code> چک می‌کند که سند واقعاً به کاربر قابل دسترس است — در غیر این صورت صفحهٔ ۴۰۴ می‌دهد. فایل‌های الحاقی هم از <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">/authorized-extra/...</code> با همان گارد و بررسیِ مسیر-traversal سرو می‌شوند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ مدیریت اسناد چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">/dms</code> کاربر یک کارتابل اسناد است — نه ویرایشگر. کاربر سندی را که به‌اشتراک گذاشته‌اید دریافت، مطالعه و تأیید می‌کند. وقتی کاربری از دسترسی به سند یا وضعیت تأیید شکایت می‌کند، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
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
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">shield_person</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">دسترسی فایل از طریق مسیر امن</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($security as $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $s['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $s['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «سند را می‌بینم ولی فایل باز نمی‌شود»، احتمالاً سند برای او قابل‌مشاهده نیست (واحدش مالک نیست و در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">users</code> هم نیست) یا وضعیت سند «فعال» نیست — مسیر امن ۴۰۴ برمی‌گرداند.
            </p>
        </div>
    </div>
</div>