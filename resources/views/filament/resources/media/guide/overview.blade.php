@php
    $fields = [
        [
            'icon' => 'category',
            'label' => 'نوع',
            'tag' => 'الزامی',
            'hint' => '«سری» را برای چند جلسه انتخاب کنید و پس از ذخیره، جلسه‌ها را اضافه کنید. برای یک فایل مستقل، «صوتی» یا «تصویری» را انتخاب کنید.',
        ],
        [
            'icon' => 'label',
            'label' => 'عنوان',
            'tag' => 'الزامی',
            'hint' => 'عنوانی که در فهرست رسانه، جست‌وجوی سراسری و پخش‌کنندهٔ پایین صفحه نمایش داده می‌شود. حداکثر ۲۵۵ نویسه.',
        ],
        [
            'icon' => 'subject',
            'label' => 'موضوع',
            'tag' => 'اختیاری',
            'hint' => 'یک موضوع کوتاه (حداکثر ۱۶۰ نویسه) که زیر عنوان جلسه یا رسانه نمایش داده می‌شود.',
        ],
        [
            'icon' => 'play_circle',
            'label' => 'راهنمای ویدیویی بخش',
            'tag' => 'اختیاری',
            'hint' => 'برای ویدیو یا سری‌ای که راهنمای یک بخش است، همان بخش را انتخاب کنید. دکمهٔ «راهنمای ویدیویی» در سربرگ آن بخش، کنار راهنما دیده می‌شود و ویدیو را در «دانش سازمانی › رسانه» باز می‌کند. فقط ویدیوی فعال، منتشرشده، دارای فایل (یا سری دارای جلسهٔ قابل‌پخش) و در دسترس کاربر نمایش داده می‌شود؛ اگر چند مورد باشد، مورد سنجاق‌شده یا اولین نمایش داده می‌شود. فقط بخش‌هایی که در سربرگشان دکمهٔ راهنمای ویدیویی دارند در فهرست دیده می‌شوند.',
        ],
        [
            'icon' => 'interests',
            'label' => 'آیکون',
            'tag' => 'اختیاری',
            'hint' => 'نماد کنار عنوان رسانه را انتخاب کنید. اگر کادر را خالی بگذارید، نماد متناسب با نوع رسانه نمایش داده می‌شود.',
        ],
        [
            'icon' => 'account_tree',
            'label' => 'سری',
            'tag' => 'جلسه‌ها',
            'hint' => 'برای جلسه، نام سری را انتخاب کنید. برای فایل مستقل این قسمت را خالی بگذارید. برای تغییر ترتیب پخش، جلسه‌ها را در فهرست بکشید و جابه‌جا کنید.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'وضعیت',
            'tag' => 'انتشار',
            'hint' => 'برای نمایش جلسه، هم جلسه و هم سری را فعال کنید. فایل مستقل یا جلسهٔ بدون فایل فعال نمی‌شود. اگر هنگام فعال‌سازی گروهی حتی یک مورد فایل نداشته باشد، هیچ‌یک فعال نمی‌شوند.',
        ],
        [
            'icon' => 'schedule',
            'label' => 'تاریخ انتشار',
            'tag' => 'انتشار تدریجی',
            'hint' => 'اگر تاریخ انتشار خالی باشد یا رسیده باشد، رسانهٔ فعال در دسترس است. جلسهٔ زمان‌بندی‌شده در فهرست دیده می‌شود و از روز انتشار قابل پخش و دانلود است.',
        ],
        [
            'icon' => 'event_busy',
            'label' => 'تاریخ پایان دسترسی',
            'tag' => 'اختیاری',
            'hint' => 'در این تاریخ، دسترسی کاربران به رسانه پایان می‌یابد. برای دسترسی بدون مهلت، کادر را خالی بگذارید.',
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
            'tag' => 'فایل مستقل و جلسه',
            'hint' => 'فایل را برای هر جلسه یا رسانهٔ مستقل بارگذاری کنید. برای ذخیره بدون فایل، وضعیت را غیرفعال کنید. پس از بارگذاری، نوع فایل خودکار تعیین می‌شود. اگر مدت پخش ثبت نشد، آن را به ثانیه وارد کنید. با جایگزینی فایل، پیشرفت کاربران باقی می‌ماند. برای محتوای تازه، جلسه‌ای جداگانه بسازید.',
        ],
        [
            'icon' => 'subtitles',
            'label' => 'متن و بخش‌های جلسه',
            'tag' => 'WebVTT',
            'hint' => 'دو فایل اختیاری با قالب VTT می‌توانید اضافه کنید. یکی متن را هم‌زمان با پخش نشان می‌دهد و دیگری زمان شروع بخش‌ها را مشخص می‌کند.',
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
                با جایگزینی فایل، مدت پخش دوباره از فایل تازه خوانده می‌شود. اگر مدت ثبت نشد یا دقیق نبود، آن را به ثانیه وارد کنید.
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
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">در «پیشرفت یادگیری»، ببینید هر کاربر تا کجای جلسه پیش رفته و آن را تمام کرده است یا نه. از همین جدول می‌توانید خروجی اکسل بگیرید.</p>
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
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">کاربران رسانه‌های منتشرشده‌ای را که به آن‌ها دسترسی دارند در «دانش سازمانی › رسانه» می‌بینند. برای دیدن امکانات پخش، متن جلسه و گزارش‌های مرتبط، زبانهٔ «نمای کاربر» را باز کنید.</p>
                </div>
            </div>
        </div>
    </div>
</div>
