@php
    $fields = [
        [
            'icon' => 'person',
            'label' => 'کاربر مشخص',
            'tag' => 'مخاطب',
            'hint' => 'برای نمایش به همهٔ کاربران، این کادر را خالی بگذارید. برای ساخت نسخهٔ اختصاصی، کاربر موردنظر را انتخاب کنید.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'فعال',
            'tag' => 'کلید',
            'hint' => 'هر مخاطب فقط یک نسخهٔ فعال دارد. با فعال‌کردن یک نسخه، نسخه‌های فعال دیگر همان مخاطب غیرفعال می‌شوند. این قانون در فرم و جدول یکسان است.',
        ],
        [
            'icon' => 'waving_hand',
            'label' => 'خوش‌آمدگویی',
            'tag' => 'محتوا',
            'hint' => 'پیام خوش‌آمدگویی که جدیدالاستخدامان هنگام اولین ورود می‌بینند. ویرایشگر غنی با رنگِ متن، جدول و تیتر است؛ تنها فیلدِ اجباریِ محتواست.',
        ],
        [
            'icon' => 'flag',
            'label' => 'مأموریت / چشم‌انداز / برنامه زمانی',
            'tag' => 'محتوا',
            'hint' => 'سه فیلد متنیِ غنی و اختیاری: مأموریت سازمان، چشم‌انداز بلندمدت، و برنامهٔ زمانی دورهٔ آشنایی. هر کدام که خالی باشد، کارتِ مربوطه در پنل کاربر نمایش داده نمی‌شود.',
        ],
        [
            'icon' => 'videocam',
            'label' => 'ویدیوها',
            'tag' => 'فهرستی',
            'hint' => 'برای هر ویدیو، عنوان، فایل (حداکثر ۹۵ مگابایت)، تصویر پیش‌نمایش (حداکثر ۵ مگابایت) و مدت زمان دلخواه را وارد کنید. می‌توانید ترتیب ویدیوها را تغییر دهید.',
        ],
        [
            'icon' => 'menu_book',
            'label' => 'راهنماها',
            'tag' => 'فهرستی',
            'hint' => 'برای هر راهنما، عنوان و فایل PDF، Word یا Excel با حجم حداکثر ۱۰ مگابایت را وارد کنید. نوع و حجم فایل هنگام ذخیره خودکار ثبت می‌شود.',
        ],
        [
            'icon' => 'dashboard_customize',
            'label' => 'بخش‌های اضافی',
            'tag' => 'فهرستی',
            'hint' => 'برای هر بخش، شناسه (مانند parking)، عنوان و متن را وارد کنید. ۴۵ گزینهٔ آماده در ۹ دسته وجود دارد. با «افزودن سریع از الگوها» می‌توانید چند بخش را یکجا اضافه کنید.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«آنبوردینگ» صفحهٔ آشنایی کارکنان جدید با سازمان است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر مجموعه در زبانهٔ «آنبوردینگ» پروفایل نمایش داده می‌شود و می‌تواند پیام خوش‌آمد، ویدیو، راهنما و بخش‌های دیگر داشته باشد. هر مجموعه برای همهٔ کاربران یا یک کاربر مشخص است. کاربر نسخهٔ اختصاصی خود را می‌بیند؛ اگر نداشته باشد، نسخهٔ عمومی نمایش داده می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر مورد</p>
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
               
            </p>
        </div>
    </div>
</div>