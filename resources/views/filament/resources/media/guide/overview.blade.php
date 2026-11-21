@php
    $fields = [
        [
            'icon' => 'category',
            'label' => 'نوع',
            'tag' => 'الزامی',
            'hint' => 'سه حالت: «سری» (مجموعه‌ای از جلسه‌ها)، «صوتی» و «تصویری» (موارد تک‌قسمتی). نوع تعیین می‌کند مورد در پنل کاربر چگونه پخش و دسته‌بندی شود.',
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
            'label' => 'سری والد',
            'tag' => 'جلسه‌ها',
            'hint' => 'برای جلسه‌های یک سری، سری والد انتخاب می‌شود. موارد سطح اصلی (سری یا تک‌قسمتی) این فیلد را خالی می‌گذارند. ترتیب جلسه‌ها با کشیدن و رها کردن در بخش «جلسه‌های سری» تعیین می‌شود.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'وضعیت',
            'tag' => 'انتشار',
            'hint' => 'کلید انتشار است. غیرفعال = بدون حذف، از دسترس کاربران خارج می‌شود. جلسهٔ غیرفعال حتی اگر سریش فعال باشد نمایش داده نمی‌شود.',
        ],
        [
            'icon' => 'schedule',
            'label' => 'تاریخ انتشار',
            'tag' => 'انتشار تدریجی',
            'hint' => 'اگر خالی باشد یا گذشته باشد، مورد منتشرشده است. تاریخ آینده جلسه را «قفل» می‌کند: در فهرست دیده می‌شود اما پخش و دانلود آن تا روز انتشار ممکن نیست.',
        ],
        [
            'icon' => 'event_busy',
            'label' => 'تاریخ انقضا',
            'tag' => 'اختیاری',
            'hint' => 'تاریخ شمسی‌ای که پس از آن مورد به‌صورت خودکار از دید کاربران پنهان می‌شود. خالی = بدون انقضا.',
        ],
        [
            'icon' => 'folder_open',
            'label' => 'مواد همراه (گزارش‌ها)',
            'tag' => 'اختیاری',
            'hint' => 'اسناد پیوست این رسانه از بین گزارش‌های موجود انتخاب می‌شوند و در زبانهٔ «مواد همراه» کاربر نمایش داده می‌شوند. گزارش‌هایی که بعداً حذف شوند خودکار از فهرست کنار می‌روند.',
        ],
        [
            'icon' => 'movie',
            'label' => 'فایل رسانه',
            'tag' => 'اختیاری',
            'hint' => 'فایل صوتی (mp3، m4a، wav، ogg) یا ویدیویی (mp4، webm)؛ حداکثر حجم طبق تنظیمات بارگذاری ویدیو. مدت فایل بعد از بارگذاری خودکار خوانده و در فیلد «مدت» قرار می‌گیرد.',
        ],
        [
            'icon' => 'subtitles',
            'label' => 'رونوشت و فصل‌ها',
            'tag' => 'WebVTT',
            'hint' => 'دو فایل اختیاری با فرمت vtt: رونوشت متنِ همزمان پخش و فصل‌ها که نقاط شروع را روی نوار پخش نشان می‌دهد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">video_library</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«رسانه» محتوای آموزشیِ قابل پخش برای همکاران است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر مورد یک رسانه است: یک فایل صوتی یا ویدیویی به‌عنوان محتوای اصلی، یک آیکون، تصویر جلد، توضیح و در صورت تمایل رونوشت و فصل‌ها. موارد می‌توانند جلسه‌های یک «سری» باشند که با تاریخ انتشار تدریجی باز می‌شوند. این بخش با «گزارشات» و «پیشرفت» زیر یک مدخل واحد در نوار کنار قرار دارد و با دکمه‌های سوییچ بالای فهرست بین آن‌ها جابه‌جا می‌شوید.
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
                «مدت» بعد از بارگذاری فایل خودکار خوانده می‌شود اما قابل ویرایش است. برای فایل‌های mp3 با کدگذاری VBR مقدار خوانده‌شده ممکن است دقیق نباشد؛ در این صورت مدت را دستی وارد کنید.
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
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">گزارشات</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">از فیلد «مواد همراه» می‌توانید اسناد پیوست رسانه را از بین گزارش‌های موجود انتخاب کنید؛ همان گزارش‌ها در پنل کاربر با دکمهٔ دانلود در دسترس‌اند.</p>
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
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">کاربران رسانه‌های «فعال» و «منتشرشده» را در صفحهٔ «رسانه» داشبورد می‌بینند: فهرست سری‌ها و موارد، پخش با نوار پایین، رونوشت، فصل‌ها و مواد همراه. جزئیات در زبانهٔ «تجربهٔ کاربر».</p>
                </div>
            </div>
        </div>
    </div>
</div>
