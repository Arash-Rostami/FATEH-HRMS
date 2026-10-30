@php
    $fields = [
        [
            'icon' => 'title',
            'label' => 'عنوان',
            'tag' => 'محتوایی',
            'hint' => 'عنوان خبر یا اطلاعیه است. عنوان با ویرایشگر متن غنی نوشته می‌شود (نه متن ساده)؛ پس می‌تواند رنگ، پررنگی، پیوند و فهرست داشته باشد، اما در جدول و جستجوی سراسری به‌صورت متن ساده نمایش داده می‌شود. حداکثر ' . convertToPersian('700') . ' نویسه.',
        ],
        [
            'icon' => 'subject',
            'label' => 'محتوا',
            'tag' => 'محتوایی',
            'hint' => 'متن کامل اعلانات با ویرایشگر غنی: رنگ متن، تیتر، فهرست، جداول، نقل‌قول، لینک و خط افقی. هنگام ذخیره، کدهای خطرناک به‌طور خودکار پاک می‌شوند. حداکثر ' . convertToPersian('50000') . ' نویسه.',
        ],
        [
            'icon' => 'image',
            'label' => 'تصویر',
            'tag' => 'عمومی',
            'hint' => 'تصویر اصلی اعلان؛ در فضای ذخیره‌سازی ذخیره می‌شود (حداکثر ' . convertToPersian('5') . ' مگابایت، فقط jpg/png/gif/webp). نام فایل هنگام ذخیره تصادفی می‌شود تا نام‌های تکراری پیش نیایند.',
        ],
        [
            'icon' => 'push_pin',
            'label' => 'سنجاق‌شده',
            'tag' => 'کلیدی',
            'hint' => 'وقتی روشن باشد، اعلان در پنل کاربری در نوار «سنجاق‌شده» و بالای فهرست تازه‌ترین‌ها نمایش داده می‌شود و از فهرست عادی کنار می‌رود. در جدول مدیر نیز سنجاق‌ها پیش‌فرض بالاتر می‌نشینند.',
        ],
        [
            'icon' => 'person',
            'label' => 'نویسنده',
            'tag' => 'سیستمی',
            'hint' => 'ثبت‌کنندهٔ اعلان خودکار شما (ادمین) هستید و فیلد قفل است و نمی‌توان آن را عوض کرد. در پنل کاربری نام نویسنده روی کارت و در صفحهٔ جزئیات دیده می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">campaign</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«اعلانات» کانال انتشار خبر سازمانی است؛ سنجاق‌ها ماندگار می‌مانند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این صفحه یک اعلان یا خبر است که کاربران در زبانهٔ «اعلانات» پنل خود می‌بینند. هر اعلان از عنوان، محتوا، یک تصویر و یک گزینهٔ سنجاق ساخته می‌شود. این ماژول دو طرفی است: شما در پنل ادمین اعلان را می‌سازید و سنجاق می‌کنید، و کاربر در پنل خود آن را در نوار سنجاق‌شده، فهرست تازه‌ترین‌ها و صفحهٔ جزئیات می‌بیند و می‌تواند آن را به اشتراک بگذارد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر اعلان</p>
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
                عنوان و محتوا هر دو با ویرایشگر متن غنی نوشته می‌شوند؛ پس قالب‌بندی آن‌ها در جدول و خلاصهٔ پنل کاربری حذف می‌شود تا فقط متن ساده نمایش داده شود.
            </p>
        </div>
    </div>
</div>