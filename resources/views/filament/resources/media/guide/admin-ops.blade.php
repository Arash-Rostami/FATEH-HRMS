@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» پنجرهٔ جزئیات رسانه را باز می‌کند: جلد، نوع، توضیح، وضعیت، تاریخ‌های شمسی و مدت. از همین پنجره هم می‌توانید ویرایش یا حذف کنید.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'فرم رسانه با دو زبانه: «اطلاعات اصلی» (نوع، عنوان، موضوع، آیکون، واحد، نویسنده، وضعیت، تاریخ‌ها، سری مربوط، گزارش‌های مرتبط، توضیح) و «فایل‌ها» (جلد، فایل رسانه، مدت، متن جلسه، فصل‌ها). زبانهٔ باز هنگام بارگذاری دوبارهٔ صفحه حفظ می‌شود.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'با حذف سری، جلسه‌هایش هم حذف و فایل‌های همهٔ موارد پاک می‌شود. برای پنهان‌کردن موقت، به‌جای حذف وضعیت را غیرفعال کنید.',
        ],
        [
            'icon' => 'swap_horiz',
            'label' => 'جابه‌جایی بین بخش‌ها',
            'hint' => 'با دکمه‌های بالای فهرست بین گزارش‌ها، رسانه‌ها، پیشرفت یادگیری و گزارش‌های هوشمند جابه‌جا شوید.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از عملیات گروهی برای موارد انتخاب‌شده خروجی اکسل بگیرید. در «پیشرفت یادگیری»، خروجی شامل کاربر، جلسه، سری، بیشترین پیشرفت، وضعیت و آخرین فعالیت است.',
        ],
    ];
    $form = [
        [
            'icon' => 'movie',
            'label' => 'فایل رسانه و مدت',
            'hint' => 'فایل جلسه را بارگذاری کنید تا مدت آن خودکار پر شود. حداکثر اندازه زیر فیلد نوشته می‌شود و برابر کمترین مقدار میان سقف برنامه (۹۵ مگابایت) و سقف سرور (upload_max_filesize) است. اگر زمان دقیق نیست، مقدار درست را به ثانیه وارد کنید.',
        ],
        [
            'icon' => 'account_tree',
            'label' => 'ساخت سری',
            'hint' => 'اول یک مورد با نوع «سری» بسازید؛ سپس در صفحهٔ ویرایش آن، از بخش «جلسه‌های سری» جلسه‌ها را اضافه و با کشیدن و رها کردن مرتب کنید.',
        ],
        [
            'icon' => 'monitoring',
            'label' => 'پیشرفت کاربران هر رسانه',
            'hint' => 'در صفحهٔ ویرایش هر رسانه، زبانهٔ «پیشرفت کاربران» فقط وقتی دیده می‌شود که کسی آن رسانه را پخش کرده باشد: هر کاربر، بیشترین پیشرفت، وضعیت و آخرین فعالیت.',
        ],
        [
            'icon' => 'schedule',
            'label' => 'انتشار تدریجی',
            'hint' => 'برای زمان‌بندی یک جلسه، «تاریخ انتشار» را در آینده بگذارید. کاربر جلسه و تاریخ آن را در فهرست می‌بیند و از روز انتشار می‌تواند آن را پخش کند.',
        ],
        [
            'icon' => 'subtitles',
            'label' => 'متن و فصل‌های جلسه',
            'hint' => 'برای هر جلسه می‌توانید دو فایل با فرمت vtt بارگذاری کنید: متن هم‌زمان با پخش و فصل‌ها (دکمه‌های عنوان کوتاه در زبانهٔ «متن جلسه» که پخش را به همان لحظه می‌برند). جمله‌های کامل را در «متن جلسه» بگذارید، نه در «فصل‌ها».',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما: ساخت رسانه، بارگذاری فایل و انتشار</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        برای ساخت یک مجموعه، ابتدا رسانه‌ای از نوع «سری» ثبت کنید. سپس در صفحهٔ ویرایش آن، جلسه‌ها را اضافه کنید. برای یافتن رسانه در پنل، عنوان یا موضوع را جست‌وجو کنید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات روی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ops as $op)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $op['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">edit</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فرم ویرایش — دو زبانه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($form as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                فایل‌های رسانه عمومی ذخیره می‌شوند؛ کنترل دسترسی از راه «وضعیت»، «تاریخ انتشار» و «تاریخ انقضا» انجام می‌شود، نه از نشانی فایل.
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">tips_and_updates</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            برای انتشار فوری، «وضعیت» را فعال بگذارید و «تاریخ انتشار» را خالی یا گذشته — رسانه بلافاصله در صفحهٔ «رسانه» کاربر ظاهر می‌شود. برای دنباله‌دار کردن یک سری، تاریخ انتشار جلسه‌های بعدی را آینده‌تر بگذارید.
        </p>
    </div>
</div>
