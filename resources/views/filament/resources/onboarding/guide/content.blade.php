@php
    $tabs = [
        [
            'icon' => 'waving_hand',
            'label' => 'خوش‌آمدگویی',
            'hint' => 'تنها زبانهٔ اجباری. ویرایشگر غنی با رنگ‌های متن، تیترها (H۱ تا H۴)، جدول، نقل‌قول، فهرست‌ها و خط افقی. ارتفاع حداقل ۲۸۰ پیکسل.',
        ],
        [
            'icon' => 'flag',
            'label' => 'مأموریت / چشم‌انداز / برنامه زمانی',
            'hint' => 'سه زبانهٔ اختیاری با همان ویرایشگر غنی. هر کدام که خالی بماند، کارتِ آن در پنل کاربر خودکار حذف می‌شود — نیازی به فعال/غیرفعال کردن جداگانه نیست.',
        ],
        [
            'icon' => 'videocam',
            'label' => 'ویدیوها',
            'hint' => 'Repeater: عنوان، فایل ویدیو (دیسک public، مسیر onboarding/video، فرمت ویدیویی، حداکثر ۹۵ مگابایت)، تصویر بندانگشتی اختیاری (حداکثر ۱ مگابایت) و مدت زمانِ دستی (مثل ۳:۴۵). نام فایل ذخیره‌شده تصادفی است.',
        ],
        [
            'icon' => 'menu_book',
            'label' => 'راهنماها',
            'hint' => 'Repeater از مستندات: عنوان و فایل (PDF/Word/Excel، مسیر onboarding/guides، حداکثر ۴۹ مگابایت). پسوند و حجم فایل در هنگام ذخیره از دیسک خوانده و در فیلدهای مخفی ext و size نگه‌داری می‌شوند — در خروجی اکسل و اینفولیست نمایش داده می‌شوند.',
        ],
        [
            'icon' => 'dashboard_customize',
            'label' => 'بخش‌های اضافی',
            'hint' => 'Repeater با سه فیلد: کلید (مثل parking)، عنوان نمایشی اختیاری، و محتوای غنی. کلید نقشِ آیکون و برچسبِ خودکار را تعیین می‌کند؛ اگر عنوان نمایشی خالی باشد، عنوان پیش‌فرضِ همان کلید از کاتالوگ استفاده می‌شود.',
        ],
    ];

    $templates = [
        [
            'icon' => 'auto_awesome',
            'label' => 'افزودن سریع از الگوها',
            'hint' => 'در زبانهٔ «بخش‌های اضافی»، یک Select چندانتخابی بالای Repeater قرار دارد که ۴۵ کلید از پیش‌تعریف‌شده را در ۹ دسته (رفت‌وآمد، محیط کار، فناوری، زمان و مقررات، همکاران، مزایا، پشتیبانی، رشد، بازخورد) گروه‌بندی کرده است. با انتخاب چند مورد، آن‌ها با عنوان و توضیحِ پیش‌فرض به Repeater اضافه می‌شوند — سپس می‌توانید محتوای هر کدام را شخصی‌سازی کنید.',
        ],
        [
            'icon' => 'style',
            'label' => 'کلید پیش‌تعریف‌شده در برابر سفارشی',
            'hint' => 'اگر کلید یکی از ۴۵ کلیدِ کاتالوگ باشد، در پنل کاربر کارتِ آن با حاشیهٔ یکپارچه و آیکونِ ثانویه نمایش داده می‌شود. اگر کلید خارج از کاتالوگ باشد، کارت با حاشیهٔ نقطه‌چین، پس‌زمینهٔ مات و برچسب «سفارشی» ظاهر می‌شود — تا کاربر بتواند آیتم‌های اختصاصیِ شما را از استانداردها تشخیص دهد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">edit_note</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">فرم ویرایش: یک بخش تنظیمات + هفت زبانهٔ محتوا</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فرم از دو ستون تشکیل شده: ستونِ کوچکِ «تنظیمات» (کاربر اختصاصی + فعال) و ستونِ بزرگ شامل هفت زبانهٔ محتوایی. زبانه‌ها مستقل از هم ذخیره می‌شوند — پر کردنِ همهٔ آن‌ها الزامی نیست؛ فقط «خوش‌آمدگویی» اجباری است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tab</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های محتوای فرم</p>
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">auto_awesome</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">الگوهای بخش اضافی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($templates as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                فیلد «افزودن سریع از الگوها» فقط ابزارِ کمک‌به‌ادیت است و خودش ذخیره نمی‌شود (dehydrated false) — محتوای نهایی در Repeaterِ پایین آن قرار می‌گیرد.
            </p>
        </div>
    </div>
</div>