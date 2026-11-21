@php
    $fields = [
        [
            'icon' => 'category',
            'label' => 'نوع',
            'tag' => 'الزامی',
            'hint' => 'برای مجموعه‌ای از چند جلسه «سری» را انتخاب کنید. برای فایل مستقل، گزینهٔ «صوتی» یا «تصویری» را متناسب با فایل انتخاب کنید.',
        ],
        [
            'icon' => 'label',
            'label' => 'عنوان',
            'tag' => 'الزامی',
            'hint' => 'عنوانی که در فهرست رسانه، جستجوی سراسری و پخش‌کنندهٔ پایین صفحه نمایش داده می‌شود. حداکثر ۲۵۵ نویسه.',
        ],
        [
            'icon' => 'subject',
            'label' => 'موضوع',
            'tag' => 'اختیاری',
            'hint' => 'یک موضوع کوتاه (حداکثر ۱۶۰ نویسه) که زیر عنوان جلسه یا رسانه نمایش داده می‌شود.',
        ],
        [
            'icon' => 'interests',
            'label' => 'آیکون',
            'tag' => 'اختیاری',
            'hint' => 'آیکون مورد در فهرست کاربر، همراه برچسب فارسی انتخاب می‌شود. اگر خالی بماند، آیکون پیش‌فرض صوتی/تصویری نمایش داده می‌شود.',
        ],
        [
            'icon' => 'account_tree',
            'label' => 'سری مربوط',
            'tag' => 'جلسه‌ها',
            'hint' => 'برای جلسه، نام سری را انتخاب کنید. برای فایل مستقل این قسمت را خالی بگذارید. برای تغییر ترتیب پخش، جلسه‌ها را در فهرست بکشید و جابه‌جا کنید.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'وضعیت',
            'tag' => 'انتشار',
            'hint' => 'برای پنهان‌کردن رسانه، وضعیت آن را غیرفعال کنید. برای نمایش یک جلسه، وضعیت خود جلسه و سری مربوط باید فعال باشد.',
        ],
        [
            'icon' => 'schedule',
            'label' => 'تاریخ انتشار',
            'tag' => 'انتشار تدریجی',
            'hint' => 'اگر تاریخ انتشار خالی باشد یا رسیده باشد، رسانهٔ فعال در دسترس است. جلسهٔ زمان‌بندی‌شده در فهرست دیده می‌شود و از روز انتشار قابل پخش و دانلود است.',
        ],
        [
            'icon' => 'event_busy',
            'label' => 'تاریخ انقضا',
            'tag' => 'اختیاری',
            'hint' => 'تاریخ شمسی‌ای که پس از آن مورد به‌صورت خودکار از دید کاربران پنهان می‌شود. خالی = بدون انقضا.',
        ],
        [
            'icon' => 'folder_open',
            'label' => 'گزارش‌های مرتبط',
            'tag' => 'اختیاری',
            'hint' => 'گزارش‌های موجود را به رسانه وصل کنید. کاربر آن‌ها را در «گزارش‌های مرتبط» می‌بیند، به شرطی که به خود گزارش دسترسی داشته باشد.',
        ],
        [
            'icon' => 'movie',
            'label' => 'فایل رسانه',
            'tag' => 'اختیاری',
            'hint' => 'فایل صوتی (mp3، m4a، wav، ogg) یا ویدیویی (mp4، webm)؛ حداکثر حجم طبق تنظیمات بارگذاری ویدیو. مدت فایل بعد از بارگذاری خودکار خوانده و در فیلد «مدت» قرار می‌گیرد.',
        ],
        [
            'icon' => 'subtitles',
            'label' => 'متن و فصل‌های جلسه',
            'tag' => 'WebVTT',
            'hint' => 'دو فایل اختیاری با فرمت vtt: یکی برای متن هم‌زمان با پخش و دیگری برای زمان شروع بخش‌های جلسه.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">video_library</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«رسانه» محتوای آموزشیِ قابل پخش برای همکاران است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        یک فایل صوتی یا ویدیویی را مستقل منتشر کنید یا چند جلسه را در یک «سری» قرار دهید. هر جلسه می‌تواند جلد، توضیح، متن و تاریخ انتشار جدا داشته باشد. برای رفتن به گزارش‌ها یا پیشرفت یادگیری، از دکمه‌های بالای فهرست استفاده کنید.
    </p>

    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        در منوی مدیریت، گزارش‌ها، رسانه‌ها و پیشرفت یادگیری زیر یک گزینهٔ «دانش سازمانی» هستند و با دکمه‌های بالای فهرست بین آن‌ها جابه‌جا می‌شوید. هر جدول به دسترسی خودش وابسته است و آمار رسانه در زبانهٔ «رسانه‌ها»ی داشبورد دیده می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر رسانه</p>
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
                مدت پس از بارگذاری فایل خودکار پر می‌شود. اگر عدد دقیق نیست، مدت درست را به ثانیه وارد کنید.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">اتصال به سایر بخش‌ها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">description</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">گزارش‌های مرتبط</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">از فیلد «گزارش‌های مرتبط» می‌توانید اسناد پیوست رسانه را از بین گزارش‌های موجود انتخاب کنید؛ همان گزارش‌ها در پنل کاربر با دکمهٔ دانلود در دسترس‌اند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">monitoring</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">پیشرفت</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">زیر مدخل «پیشرفت» دیده می‌شود که هر کاربر تا کجای کدام جلسه پیش رفته و چه جلسه‌هایی را تمام کرده است؛ خروجی اکسل هم دارد.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">visibility</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">پنل کاربر (دانش سازمانی › رسانه)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">کاربران رسانه‌های «فعال» و «منتشرشده» را در صفحهٔ «رسانه» داشبورد می‌بینند: فهرست سری‌ها و موارد، پخش با نوار پایین، متن جلسه، فصل‌ها و گزارش‌های مرتبط. جزئیات در زبانهٔ «نمای کاربر».</p>
                </div>
            </div>
        </div>
    </div>
</div>
