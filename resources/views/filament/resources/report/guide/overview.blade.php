@php
    $fields = [
        [
            'icon' => 'label',
            'label' => 'عنوان (title)',
            'tag' => 'الزامی',
            'hint' => 'عنوان گزارش که در فهرست، جستجوی سراسری پنل و کارتِ کاربر نمایش داده می‌شود. حداکثر ۲۵۵ کاراکتر.',
        ],
        [
            'icon' => 'subject',
            'label' => 'توضیحات (description)',
            'tag' => 'متنی غنی',
            'hint' => 'بدنهٔ گزارش با ویرایشگر RichEditor: رنگ متن، عنوان‌ها، جدول، بلوک‌کد و لینک. هنگام ذخیره توسط ContentSanitizerService پاک‌سازی می‌شود. در کارت کاربر به‌صورت HTML نمایش داده می‌شود.',
        ],
        [
            'icon' => 'badge',
            'label' => 'واحد سازمانی (department_id)',
            'tag' => 'اختیاری',
            'hint' => 'واحدِ مرتبط با گزارش. اگر خالی بگذارید، گزارش «عمومی» تلقی می‌شود. کاربران در زبانهٔ گزارشات می‌توانند بر اساس همین واحد فیلتر کنند. کلید اتصال «کد واحد» است (نه آیدی عددی).',
        ],
        [
            'icon' => 'person',
            'label' => 'کاربر (user_id)',
            'tag' => 'الزامی',
            'hint' => 'نویسنده یا مسئول ارائهٔ گزارش. در فهرست، اینفولیست و خروجی اکسل به‌صورت نام کاربر نمایش داده می‌شود.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'وضعیت (active)',
            'tag' => 'انتشار',
            'hint' => 'تاگلِ انتشار است. فعال = گزارش در زبانهٔ «گزارشات» پنل کاربر ظاهر و قابل دانلود است. غیرفعال = بدون حذف، از دسترس کاربران خارج می‌شود (کاربران فقط رکوردهای active را می‌بینند).',
        ],
        [
            'icon' => 'share',
            'label' => 'دسترسی واحدها (departments)',
            'tag' => 'مخاطبان',
            'hint' => 'آرایهٔ JSON از کدهای واحدهایی که مجاز به دیدن گزارش‌اند. خالی = عمومی (همه). پر = فقط کاربران این واحدها. مستقل از «واحد سازمانی» (موضوع گزارش). جزئیات در زبانهٔ «دسترسی و اشتراک».',
        ],
        [
            'icon' => 'bookmark',
            'label' => 'سنجاق (pinned)',
            'tag' => 'نمایش',
            'hint' => 'سنجاق کردن گزارش را در صدر فهرستِ زبانهٔ کاربر (بالای ترتیبِ تاریخ) نگه می‌دارد.',
        ],
        [
            'icon' => 'calendar_month',
            'label' => 'تاریخ گزارش (report_date)',
            'tag' => 'اختیاری',
            'hint' => 'تاریخ یا دورهٔ شمسی که گزارش به آن تعلق دارد؛ مستقل از تاریخ بارگذاری. در جدول sortable و دارای فیلترِ بازه‌ای.',
        ],
        [
            'icon' => 'event_busy',
            'label' => 'تاریخ انقضا (expires_at)',
            'tag' => 'اختیاری',
            'hint' => 'تاریخ شمسیِ پس از آن گزارش به‌صورت خودکار از زبانهٔ کاربر مخفی می‌شود (بدون حذف). خالی = بدون انقضا.',
        ],
        [
            'icon' => 'image',
            'label' => 'تصویر جلد (cover_image)',
            'tag' => 'اختیاری',
            'hint' => 'تصویر جلد گزارش روی دیسک public در مسیر reports/covers. حداکثر ۲ مگابایت. اگر خالی باشد، تصویر پیش‌نمایش از روی فرمت فایل ساخته می‌شود.',
        ],
        [
            'icon' => 'file_present',
            'label' => 'فایل گزارش (file_path)',
            'tag' => 'الزامی',
            'hint' => 'فایل اصلی گزارش روی دیسک public در مسیر reports/files. فقط PDF یا Word (pdf/doc/docx)، حداکثر ۵ مگابایت. این فایل است که کاربر دانلود می‌کند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">description</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«گزارش» یک سندِ منتشرشده برای همکاران است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف یک گزارش است: یک فایل PDF یا Word به‌عنوان محتوای اصلی، یک تصویر جلد، یک بدنهٔ متنی غنی، یک نویسنده و در صورت تمایل یک واحد سازمانی. تاگلِ «وضعیت» کنترل می‌کند که گزارش در زبانهٔ «گزارشات» پنل کاربر ظاهر شود یا نه. این ماژول دو طرفی است: ادمین اینجا گزارش را می‌سازد و منتشر می‌کند، کاربر در زبانهٔ «گزارشات» داشبورد آن را می‌بیند و دانلود می‌کند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر گزارش</p>
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
                «نوع فایل» (file_type) فیلد دیتابیس نیست — یک اکسسور روی مدل است که پسوند file_path را برمی‌گرداند. «پیش‌نمایش» (thumbnail) نیز اکسسور است: اگر cover_image باشد همان، وگرنه بر اساس فرمت فایل (pdf.png / doc.png / report.png) ساخته و کش می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">اتصال به سایر ماژول‌ها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">groups</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">واحدهای سازمانی (Departments)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">زیرِ صفحهٔ ویرایش هر واحد، مدیریت ارتباط «گزارش‌ها» گزارش‌های منتشرشدهٔ آن واحد را فهرست می‌کند. از آنجا می‌توانید مستقیم گزارش بسازید یا ویرایش کنید — بدون رفتن به صفحهٔ گزارشات.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">person</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">کاربران (Users)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">زیرِ صفحهٔ ویرایش هر کاربر، مدیریت ارتباط «گزارش‌ها» گزارش‌های منتشرشدهٔ آن نویسنده را نشان می‌دهد.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">visibility</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">پنل کاربر (زبانهٔ گزارشات)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">کاربران گزارش‌های «فعال» را در زبانهٔ «گزارشات» داشبورد می‌بینند — به‌صورت کارت یا فهرست، با فیلترِ واحد و دانلود فایل. جزئیات در زبانهٔ «تجربهٔ کاربر».</p>
                </div>
            </div>
        </div>
    </div>
</div>