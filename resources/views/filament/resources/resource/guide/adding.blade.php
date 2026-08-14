@php
    $fields = [
        [
            'icon' => 'badge',
            'label' => 'نام',
            'tag' => 'همهٔ نوع‌ها',
            'hint' => 'الزامی. نامی کوتاه و مشخص؛ برای میز/پارکینگ/خودرو مثل «میز ۱۲ - طبقه ۲» یا «پارکینگ A-۷». برای ملاقات باید دقیقاً نام یک کاربر واقعی باشد (در زبانهٔ ملاقات توضیح داده شده). نام‌ها را یکتا انتخاب کنید تا با جست‌وجو و زبانهٔ رزرو اشتباه نگیرید.',
        ],
        [
            'icon' => 'category',
            'label' => 'نوع',
            'tag' => 'الزامی',
            'hint' => 'یکی از میز کار / پارکینگ / خودرو / ملاقات. به‌محض تغییر نوع، فیلدهای فرادادهٔ مربوط به همان نوع ظاهر یا پنهان می‌شوند.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'وضعیت',
            'tag' => 'پیش‌فرض: فعال',
            'hint' => 'فقط منبع «فعال» در صفحهٔ رزرو کاربر نمایش داده و قابل رزرو است. برای موقت خارج‌کردن یک منبع، آن را «غیرفعال» کنید.',
        ],
        [
            'icon' => 'image',
            'label' => 'تصویر',
            'tag' => 'اختیاری',
            'hint' => 'تصویر کارت منبع در صفحهٔ رزرو. اگر برای نوع ملاقات تصویری تنظیم نکنید، خودکار تصویر پروفایل همان شخص جایگزین می‌شود. فایل در پوشهٔ resources ذخیره می‌شود.',
        ],
    ];

    $metadata = [
        [
            'icon' => 'layers',
            'label' => 'طبقه',
            'types' => 'میز کار · پارکینگ',
            'hint' => 'شماره طبقه. در صفحهٔ رزرو کاربر به‌صورت فیلتر طبقه نمایش داده می‌شود و منابع را بر اساس طبقه دسته‌بندی می‌کند. برای نوع خودرو و ملاقات نمایش داده نمی‌شود.',
        ],
        [
            'icon' => 'call',
            'label' => 'داخلی',
            'types' => 'میز کار · ملاقات',
            'hint' => 'شماره داخلی تلفن. فقط برای میز کار و ملاقات. در کارت منبع با قلم یکنواخت (mono) نمایش داده می‌شود.',
        ],
        [
            'icon' => 'group',
            'label' => 'ظرفیت',
            'types' => 'خودرو · ملاقات',
            'hint' => 'عددی. برای خودرو = سرنشین، برای ملاقات = تعداد حاضران. فقط برای این دو نوع نمایش داده می‌شود.',
        ],
        [
            'icon' => 'sticky_note_2',
            'label' => 'یادداشت',
            'types' => 'همهٔ نوع‌ها',
            'hint' => 'توضیح کوتاه و دلخواه (تا ۱۰۰۰ نویسه). در صفحهٔ ویرایش منبع و جزئیات نمایش داده می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">add_circle</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">افزودن یک منبع جدید</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        از دکمهٔ «ایجاد منبع» یک فرم سه‌بخشی باز می‌شود: بخش اصلی (نام/نوع/وضعیت)، بخش فراداده (طبقه/داخلی/ظرفیت/یادداشت)، و بخش تصویر. فقط نام و نوع و وضعیت الزامی‌اند؛ بقیه اختیاری. نوعی که انتخاب می‌کنید تعیین می‌کند کدام فیلدهای فراداده نمایش داده شوند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">edit_note</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای اصلی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($fields as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]">{{ $f['tag'] }}</span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tune</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای فراداده (بر اساس نوع نمایش داده می‌شوند)</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($metadata as $m)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">{{ $m['icon'] }}</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $m['label'] }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">{{ $m['types'] }}</span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $m['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                خلاصهٔ فراداده بر نوع: میز کار ← طبقه + داخلی · پارکینگ ← طبقه · خودرو ← ظرفیت · ملاقات ← داخلی + ظرفیت · یادداشت برای همه.
            </p>
        </div>
    </div>
</div>