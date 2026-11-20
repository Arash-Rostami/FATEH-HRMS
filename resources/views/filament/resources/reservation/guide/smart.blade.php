@php
    $features = [
        [
            'icon' => 'repeat',
            'label' => 'رزرو تکراری (روزانه یا هفتگی)',
            'hint' => 'تعداد شامل نوبت اول است. کاربر در صفحهٔ رزرو از ۲ تا ۱۰ نوبت و مدیر در فرم مدیریت از ۲ تا ۵۲ نوبت انتخاب می‌کند. هر نوبت جدا بررسی می‌شود؛ نوبت نامعتبر یا دارای تداخل ثبت نمی‌شود. تعداد ثبت‌نشده‌ها را در پیام نتیجه ببینید.',
        ],
        [
            'icon' => 'query_stats',
            'label' => 'نوارهای سهمیه و «قوانین» در پنل کاربر',
            'hint' => 'نوار سهمیه، رزروهای فعال و آزادشدهٔ ماه انتخابی را می‌شمارد. نوار لغو، لغوهای خود کاربر در بازهٔ تعیین‌شده را نشان می‌دهد. در «قوانین»، محدودیت‌های نوع انتخابی و مورد بازشده دیده می‌شوند.',
        ],
        [
            'icon' => 'bolt',
            'label' => 'رزرو لحظه‌آخری',
            'hint' => 'اگر فعال باشد، حتی با سهمیهٔ پر می‌توان مورد آزاد با شروع نزدیک را رزرو کرد. فقط محدودیت سهمیه کنار می‌رود؛ فاصلهٔ لازم تا شروع و سایر قوانین باقی می‌مانند. این رزرو هم در سهمیه حساب می‌شود.',
        ],
        [
            'icon' => 'balance',
            'label' => 'سقف ماهانه و نوار پیشرفت',
            'hint' => 'سقف برای هر نوع، در ماه شمسیِ شروع رزرو حساب می‌شود. سقف اختصاصی کاربر، اگر تعیین شده باشد، جای سقف عمومی را می‌گیرد. لغو، رزرو را از این شمارش کنار می‌گذارد؛ آزادسازی سهمیه را برنمی‌گرداند. ساخت رزرو در مدیریت هم این سقف را بررسی می‌کند؛ ویرایش مدیریتی از آن معاف است.',
        ],
        [
            'icon' => 'block',
            'label' => 'سقف لغو در بازهٔ شمارش (پیش‌فرض ۳۰ روز)',
            'hint' => 'فقط لغوهای خود کاربر شمرده می‌شوند؛ لغو مدیریتی اضافه نمی‌شود. سقف صفر یعنی کاربر حق لغو ندارد. در سقف‌های بیشتر از صفر، رسیدن به حد هم لغو بیشتر و هم رزرو تازه را می‌بندد تا لغوهای قدیمی از شمارش خارج شوند. لغو همزمان یک گروه تکرار، یک نوبت حساب می‌شود.',
        ],
        [
            'icon' => 'schedule',
            'label' => 'حداقل زمان قبل از رزرو',
            'hint' => 'رزرو باید این تعداد ساعت پیش از شروع ثبت شود. صفر یعنی این فاصله لازم نیست. این محدودیت هنگام ساخت رزرو در مدیریت هم بررسی می‌شود؛ در ویرایش مدیریتی کنار گذاشته می‌شود.',
        ],
        [
            'icon' => 'event_available',
            'label' => 'روزها و ساعت‌های مجاز',
            'hint' => 'کاربر فقط در روزها و ساعت‌های مجاز می‌تواند رزرو کند. محدودیت اختصاصی هر مورد هم بررسی می‌شود. مدیر هنگام ساخت و ویرایش می‌تواند از این محدودیت‌های روز و ساعت عبور کند؛ تداخل با رزرو دیگر همچنان بررسی می‌شود.',
        ],
        [
            'icon' => 'timer',
            'label' => 'حداقل و حداکثر مدت رزرو',
            'hint' => 'برای رزرو ساعتی، کوتاه‌ترین و بلندترین مدت مجاز به دقیقه است. تمام‌روز از این محدودیت معاف است. مدیر نیز می‌تواند مدت دیگری انتخاب کند؛ محدودیت رزرو بلندمدت جداگانه بررسی می‌شود.',
        ],
        [
            'icon' => 'swap_horiz',
            'label' => 'رزرو روی زمان آزادشده',
            'hint' => 'بسته به تنظیمات، رزرو آزادشده ممکن است هنوز مانع استفادهٔ دیگران از همان زمان باشد. روشن کردن اجازهٔ رزرو روی زمان آزادشده این مانع را برای مورد برمی‌دارد؛ رزرو همزمانِ همان کاربر همچنان جداگانه بررسی می‌شود.',
        ],
        [
            'icon' => 'link',
            'label' => 'لغو یک رزرو یا کل سری',
            'hint' => 'اگر لغو تک‌نوبت مجاز باشد، فقط ردیف انتخابی لغو می‌شود. در غیر این صورت، همان رزرو و نوبت‌های مرتبطِ قابل لغو با هم لغو می‌شوند. دکمهٔ «لغو رزرو» در مدیریت هم همین قاعده را رعایت می‌کند.',
        ],
        [
            'icon' => 'center_focus_strong',
            'label' => 'باز شدن مستقیم یک منبع',
            'hint' => 'پیوند مستقیم، کارت مورد رزرو را باز می‌کند. این پیوند به خود مورد می‌رود؛ به یک رزرو مشخص تعلق ندارد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tips_and_updates</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">قواعدی که هنگام رزرو باید بدانید</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هنگام ساخت رزرو در مدیریت، مجوز فرد، زمان، سهمیه و تداخل بررسی می‌شوند؛ مدیر می‌تواند روز، ساعت یا مدت متفاوتی از قواعد عادی انتخاب کند. در ویرایش مدیریتی، محدودیت‌های بیشتری کنار گذاشته می‌شوند. فعال بودن نوع و مورد، آزاد بودن زمان و سقف مدت رزرو بلندمدت همچنان بررسی می‌شوند. راهنمای «قوانین رزرو» توضیح هر خطا را دارد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">auto_awesome</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">قواعد مهم رزرو</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($features as $f)
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
                همهٔ این قوانین بر اساس نوع مورد رزرو در صفحهٔ «قوانین رزرو» تنظیم می‌شوند و بعد از ذخیره بلافاصله اعمال می‌شوند؛ نیازی به پاک‌سازی دستی نیست.
            </p>
        </div>
    </div>
</div>
