@php
    $fields = [
        [
            'icon' => 'work',
            'label' => 'عنوان موقعیت (position)',
            'tag' => 'نمایشی',
            'hint' => 'عنوان دقیق موقعیت شغلی — در ستون جدول، در جستجوی سراسری پنل و روی کارتِ کاربر نمایش داده می‌شود. اجباری نیست ولی خالی بودن آن جای «تعریف نشده» می‌گیرد. حداکثر ' . convertToPersian('255') . ' کاراکتر.',
        ],
        [
            'icon' => 'badge',
            'label' => 'جنسیت (gender)',
            'tag' => 'enum',
            'hint' => 'سه حالت دارد: آقایان (Male — آبی)، خانم‌ها (Female — صورتی)، همه (Any — سبز). مقدار پیش‌فرض «همه» است و آگهی بدون محدودیت جنسیتی منتشر می‌شود. ستون جدول، اینفولیست و خروجی اکسل همگی از همین enum برچسب و رنگ و آیکون می‌خوانند.',
        ],
        [
            'icon' => 'verified',
            'label' => 'وضعیت (active)',
            'tag' => 'بولی',
            'hint' => 'تاگلِ وضعیت در فرم و در ستون جدول. «فعال» آگهی را در پنل کاربر (/ads) نمایش می‌دهد؛ «غیرفعال» آن را از دید کاربر پنهان می‌کند بدون اینکه حذف شود. «غیرفعال» در پنل کاربر همان «بایگانی شده» است.',
        ],
        [
            'icon' => 'link',
            'label' => 'لینک آگهی (link)',
            'tag' => 'الزامی',
            'hint' => 'آدرس کامل صفحهٔ ثبت‌نام یا اطلاعات شغلی — باید با http(s) شروع شود (اعتبارسنجی url). در جدول و اینفولیست به‌صورت لینکِ بازشونده در زبانهٔ جدید نمایش داده می‌شود. حداکثر ' . convertToPersian('500') . ' کاراکتر.',
        ],
        [
            'icon' => 'school',
            'label' => 'مدرک تحصیلی (certificate)',
            'tag' => 'متنی',
            'hint' => 'مدارک تحصیلی مورد نیاز — روی کارتِ کاربر (رویِ face) دیده می‌شود. nullable است؛ فیلتر «دارای شرط مدرک» روی wasNotNull آن کار می‌کند. حداکثر ' . convertToPersian('2000') . ' کاراکتر.',
        ],
        [
            'icon' => 'psychology',
            'label' => 'مهارت‌ها (skill)',
            'tag' => 'متنی',
            'hint' => 'مهارت‌های فنی و نرم‌افزاری مورد نیاز — روی پشتِ کارتِ کاربر نمایش داده می‌شود. nullable؛ فیلتر «دارای شرط مهارت» روی آن است. حداکثر ' . convertToPersian('2000') . ' کاراکتر.',
        ],
        [
            'icon' => 'work_history',
            'label' => 'سابقه کاری (experience)',
            'tag' => 'متنی',
            'hint' => 'حداقل سابقه کاری مرتبط — روی پشتِ کارتِ کاربر نمایش داده می‌شود. nullable؛ فیلتر «دارای سابقه کاری» روی آن است. حداکثر ' . convertToPersian('2000') . ' کاراکتر.',
        ],
        [
            'icon' => 'checklist',
            'label' => 'سایر توضیحات (extra)',
            'tag' => 'JSON array',
            'hint' => 'فیلد Repeater با جفت‌های «عنوان + توضیحات». به‌صورت آرایهٔ JSON ذخیره می‌شود، قابل افزودن/حذف/مرتب‌سازی/کپی. در خروجی اکسل به‌صورت «key: value, key: value» درمی‌آید و روی کارتِ کاربر هر آیتم در یک کارتِ جدا دیده می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">campaign</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«فرصت شغلی» یک آگهی استخدامی است که کاربران در پنل کاربری می‌بینند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این صفحه یک موقعیت شغلی است. شما عنوان، جنسیت، لینک، مدرک، مهارت و سابقهٔ موردنیاز را تعریف می‌کنید و آگهی با تاگلِ «وضعیت» در پنل کاربر (صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/ads</code>) ظاهر یا پنهان می‌شود. کاربران آگهی‌ها را به‌صورت کارت‌های برگشت‌پذیر می‌بینند و لینک ثبت‌نام را کپی می‌کنند — ساخت و ویرایش آگهی فقط از طرف ادمین است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر فرصت شغلی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($fields as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                                {{ $f['tag'] }}
                            </span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                «غیرفعال» کردن آگهی آن را حذف نمی‌کند — فقط از پنل کاربر پنهان می‌شود و در زبانهٔ «غیرفعال» همین صفحه باقی می‌ماند.
            </p>
        </div>
    </div>
</div>