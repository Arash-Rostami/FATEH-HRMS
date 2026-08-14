@php
    $fields = [
        [
            'icon' => 'title',
            'label' => 'عنوان (title)',
            'tag' => 'محتوایی',
            'hint' => 'عنوان خبر یا اطلاعیه است. فیلد از نوع RichEditor است (نه متن ساده)؛ پس می‌تواند رنگ، بولد، لینک و لیست داشته باشد و در جدول و عنوان جستجوی سراسری با strip_tags نمایش داده می‌شود. حداکثر ' . convertToPersian('700') . ' کاراکتر.',
        ],
        [
            'icon' => 'subject',
            'label' => 'محتوا (body)',
            'tag' => 'محتوایی',
            'hint' => 'متن کامل اعلانات با ویرایشگر غنی: رنگ متن، تیتر، فهرست، جداول، نقل‌قول، لینک و خط افقی. هنگام ذخیره از ContentSanitizerService می‌گذرد و کدهای خطرناک را پاک می‌کند. حداکثر ' . convertToPersian('50000') . ' کاراکتر.',
        ],
        [
            'icon' => 'image',
            'label' => 'تصویر (image)',
            'tag' => 'عمومی',
            'hint' => 'تصویر اصلی اعلان؛ روی دیسک public و پوشهٔ post/image ذخیره می‌شود (حداکثر ' . convertToPersian('2') . ' مگابایت، فقط jpg/png/gif/webp). نام فایل هنگام ذخیره تصادفی می‌شود تا هم‌نشانی جلوگیری شود. نمایش کاربری از طریق imageUrl که از HasPublicAssetUrl می‌آید.',
        ],
        [
            'icon' => 'push_pin',
            'label' => 'سنجاق‌شده (pinned)',
            'tag' => 'کلیدی',
            'hint' => 'وقتی روشن باشد، اعلان در پنل کاربری در نوار «سنجاق شده» و بالای فهرست تازه‌ترین‌ها نمایش داده می‌شود و از فهرست عادی کنار می‌رود. در جدول مدیر نیز سنجاق‌ها پیش‌فرض بالاتر می‌نشینند.',
        ],
        [
            'icon' => 'person',
            'label' => 'نویسنده (user_id)',
            'tag' => 'سیستمی',
            'hint' => 'ثبت‌کنندهٔ اعلان خودکار شما (ادمین) هستید و فیلد قفل است (disabled + dehydrated)؛ نمی‌توان آن را عوض کرد. در پنل کاربری نام نویسنده روی کارت و در صفحهٔ جزئیات دیده می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">campaign</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«اعلانات» کانال انتشار خبر سازمانی است؛ سنجاق‌ها ماندگار می‌مانند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این صفحه یک اعلان یا خبر است که کاربران در زبانهٔ «اعلانات» پنل خود می‌بینند. اعلان از عنوان (RichEditor)، محتوا (RichEditor)، یک تصویر و یک پرچم سنجاق می‌سازد. این ماژول دو طرفی است: شما در پنل ادمین اعلان را می‌سازید و سنجاق می‌کنید، و کاربر در پنل خود آن را در نوار سنجاق‌شده، فهرست تازه‌ترین‌ها و صفحهٔ جزئیات می‌بیند و می‌تواند آن را به اشتراک بگذارد.
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
                عنوان و محتوا هر دو RichEditor هستند؛ پس کاراکترهای HTML در جدول با strip_tags و در پنل کاربری با superClean پاک می‌شوند تا خلاصهٔ متنی نمایش داده شود.
            </p>
        </div>
    </div>
</div>