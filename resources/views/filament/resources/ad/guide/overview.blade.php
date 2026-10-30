@php
    $fields = [
        [
            'icon' => 'work',
            'label' => 'عنوان موقعیت',
            'tag' => 'نمایشی',
            'hint' => 'عنوان دقیق موقعیت شغلی — در ستون جدول، در جستجوی کلی و روی کارتِ کاربر نمایش داده می‌شود. اجباری نیست ولی خالی بودن آن جای «تعریف نشده» می‌گیرد. حداکثر ' . convertToPersian('255') . ' نویسه.',
        ],
        [
            'icon' => 'badge',
            'label' => 'جنسیت',
            'tag' => 'enum',
            'hint' => 'سه حالت دارد: آقایان (آبی)، خانم‌ها (صورتی)، همه (سبز). مقدار پیش‌فرض «همه» است و آگهی بدون محدودیت جنسیتی منتشر می‌شود. ستون جدول، صفحهٔ جزئیات و خروجی اکسل همگی برچسب، رنگ و آیکون را از همین مقدار می‌گیرند.',
        ],
        [
            'icon' => 'verified',
            'label' => 'وضعیت',
            'tag' => 'روشن/خاموش',
            'hint' => 'کلید وضعیت در فرم و در ستون جدول. «فعال» آگهی را در پنل کاربر (/ads) نمایش می‌دهد؛ «غیرفعال» آن را از دید کاربر پنهان می‌کند بدون اینکه حذف شود. «غیرفعال» در پنل کاربر همان «بایگانی شده» است.',
        ],
        [
            'icon' => 'link',
            'label' => 'لینک آگهی',
            'tag' => 'الزامی',
            'hint' => 'آدرس کامل صفحهٔ ثبت‌نام یا اطلاعات شغلی — باید با http:// یا https:// شروع شود. در جدول و صفحهٔ جزئیات به‌صورت لینکِ بازشونده در زبانهٔ جدید نمایش داده می‌شود. حداکثر ' . convertToPersian('500') . ' نویسه.',
        ],
        [
            'icon' => 'school',
            'label' => 'مدرک تحصیلی',
            'tag' => 'متنی',
            'hint' => 'مدارک تحصیلی مورد نیاز — روی کارتِ کاربر دیده می‌شود. اختیاری است؛ فیلتر «دارای شرط مدرک» بر اساس پر یا خالی بودن آن کار می‌کند. حداکثر ' . convertToPersian('2000') . ' نویسه.',
        ],
        [
            'icon' => 'psychology',
            'label' => 'مهارت‌ها',
            'tag' => 'متنی',
            'hint' => 'مهارت‌های فنی و نرم‌افزاری مورد نیاز — روی پشتِ کارتِ کاربر نمایش داده می‌شود. اختیاری؛ فیلتر «دارای شرط مهارت» بر اساس پر یا خالی بودن آن کار می‌کند. حداکثر ' . convertToPersian('2000') . ' نویسه.',
        ],
        [
            'icon' => 'work_history',
            'label' => 'سابقه کاری',
            'tag' => 'متنی',
            'hint' => 'حداقل سابقه کاری مرتبط — روی پشتِ کارتِ کاربر نمایش داده می‌شود. اختیاری؛ فیلتر «دارای سابقه کاری» بر اساس پر یا خالی بودن آن کار می‌کند. حداکثر ' . convertToPersian('2000') . ' نویسه.',
        ],
        [
            'icon' => 'checklist',
            'label' => 'سایر توضیحات',
            'tag' => 'JSON array',
            'hint' => 'فهرستی از جفت‌های «عنوان + توضیحات» که می‌توانید اضافه، حذف، جابه‌جا یا کپی کنید. در خروجی اکسل به‌صورت «عنوان: توضیح، عنوان: توضیح» درمی‌آید و روی کارتِ کاربر هر آیتم در یک کارتِ جدا دیده می‌شود.',
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