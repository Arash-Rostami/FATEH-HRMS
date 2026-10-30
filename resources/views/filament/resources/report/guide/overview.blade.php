@php
    $fields = [
        [
            'icon' => 'label',
            'label' => 'عنوان',
            'tag' => 'الزامی',
            'hint' => 'عنوان گزارش که در فهرست، جستجوی سراسری پنل و کارتِ کاربر نمایش داده می‌شود. حداکثر ۲۵۵ نویسه.',
        ],
        [
            'icon' => 'subject',
            'label' => 'توضیحات',
            'tag' => 'متنی غنی',
            'hint' => 'متن گزارش با ویرایشگر متن: رنگ، عنوان، جدول و لینک. هنگام ذخیره، محتوای خطرناک پاک می‌شود. در کارت گزارش برای کاربر با همین قالب‌بندی دیده می‌شود.',
        ],
        [
            'icon' => 'badge',
            'label' => 'واحد سازمانی',
            'tag' => 'اختیاری',
            'hint' => 'واحدِ موضوعِ گزارش — یعنی گزارش دربارهٔ کدام واحد است، نه اینکه چه کسی مجاز به دیدنش باشد (آن نقش را فیلد «دسترسی واحدها» زیر بازی می‌کند، کاملاً مستقل از این فیلد). کاربران در زبانهٔ گزارش‌ها می‌توانند بر اساس همین واحد فیلتر کنند.',
        ],
        [
            'icon' => 'person',
            'label' => 'نویسنده',
            'tag' => 'الزامی',
            'hint' => 'نویسنده یا مسئول ارائهٔ گزارش. در فهرست، صفحهٔ جزئیات و خروجی اکسل به‌صورت نام کاربر نمایش داده می‌شود.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'وضعیت',
            'tag' => 'انتشار',
            'hint' => 'کلید انتشار است. فعال = گزارش در زبانهٔ «گزارش‌ها» پنل کاربر ظاهر و قابل دانلود است. غیرفعال = بدون حذف، از دسترس کاربران خارج می‌شود (کاربران فقط گزارش‌های فعال را می‌بینند).',
        ],
        [
            'icon' => 'share',
            'label' => 'دسترسی واحدها',
            'tag' => 'مخاطبان',
            'hint' => 'فهرست واحدهایی که مجاز به دیدن گزارش‌اند. خالی = عمومی (همه). پر = فقط کاربران این واحدها. مستقل از «واحد سازمانی» (موضوع گزارش). جزئیات در زبانهٔ «دسترسی واحدها».',
        ],
        [
            'icon' => 'bookmark',
            'label' => 'سنجاق',
            'tag' => 'نمایش',
            'hint' => 'سنجاق کردن گزارش را در صدر فهرستِ زبانهٔ کاربر (بالای ترتیبِ تاریخ) نگه می‌دارد.',
        ],
        [
            'icon' => 'calendar_month',
            'label' => 'تاریخ گزارش',
            'tag' => 'اختیاری',
            'hint' => 'تاریخ یا دورهٔ شمسی که گزارش به آن تعلق دارد؛ مستقل از تاریخ بارگذاری. در جدول قابل مرتب‌سازی است و فیلتر بازه‌ای دارد.',
        ],
        [
            'icon' => 'event_busy',
            'label' => 'تاریخ انقضا',
            'tag' => 'اختیاری',
            'hint' => 'تاریخ شمسی‌ای که پس از آن گزارش به‌صورت خودکار از زبانهٔ کاربر پنهان می‌شود (بدون حذف). خالی = بدون انقضا.',
        ],
        [
            'icon' => 'image',
            'label' => 'تصویر جلد',
            'tag' => 'اختیاری',
            'hint' => 'حداکثر ۵ مگابایت. اگر خالی باشد، یک تصویر پیش‌فرض متناسب با فرمت فایل نمایش داده می‌شود.',
        ],
        [
            'icon' => 'file_present',
            'label' => 'فایل گزارش',
            'tag' => 'الزامی',
            'hint' => 'فایل اصلی گزارش؛ فقط PDF یا Word، حداکثر ۱۰ مگابایت. کاربر همین فایل را دانلود می‌کند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">description</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«گزارش» یک سندِ منتشرشده برای همکاران است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر مورد یک گزارش است: یک فایل PDF یا Word به‌عنوان محتوای اصلی، یک تصویر جلد، یک بدنهٔ متنی غنی، یک نویسنده و در صورت تمایل یک واحد سازمانی. کلید «وضعیت» تعیین می‌کند که گزارش در زبانهٔ «گزارش‌ها» پنل کاربر ظاهر شود یا نه. این ماژول دو طرفی است: ادمین اینجا گزارش را می‌سازد و منتشر می‌کند، کاربر در زبانهٔ «گزارش‌ها» داشبورد آن را می‌بیند و دانلود می‌کند.
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
                «نوع فایل» را خودکار از پسوند فایل گزارش تشخیص می‌دهد و قابل ویرایش نیست. «پیش‌نمایش» هم خودکار است: اگر تصویر جلد باشد همان نمایش داده می‌شود، وگرنه یک تصویر پیش‌فرض متناسب با نوع فایل.
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
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">واحدهای سازمانی</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">زیرِ صفحهٔ ویرایش هر واحد، بخش «گزارش‌ها» گزارش‌های منتشرشدهٔ آن واحد را فهرست می‌کند. از آنجا می‌توانید مستقیم گزارش بسازید یا ویرایش کنید — بدون رفتن به صفحهٔ گزارش‌ها.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">person</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">کاربران</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">زیرِ صفحهٔ ویرایش هر کاربر، بخش «گزارش‌ها» گزارش‌های منتشرشدهٔ آن نویسنده را نشان می‌دهد.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">visibility</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">پنل کاربر (زبانهٔ گزارش‌ها)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">کاربران گزارش‌های «فعال» را در زبانهٔ «گزارش‌ها» داشبورد می‌بینند — به‌صورت کارت یا فهرست، با فیلترِ واحد و دانلود فایل. جزئیات در زبانهٔ «تجربهٔ کاربر».</p>
                </div>
            </div>
        </div>
    </div>
</div>