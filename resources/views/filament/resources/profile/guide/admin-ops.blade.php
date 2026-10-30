@php
    $ops = [
        ['icon' => 'visibility', 'label' => 'مشاهده', 'hint' => 'دکمهٔ «مشاهده» صفحهٔ نمایش پروفایل را با زبانه‌های هویت، تماس، شغل، تصویر و پیوست‌ها، مهارت‌ها، درباره من و اطلاعات تکمیلی باز می‌کند؛ تاریخ‌ها شمسی‌اند.'],
        ['icon' => 'edit', 'label' => 'ویرایش', 'hint' => 'فرم ویرایش این زبانه‌ها را دارد: هویت/تماس/شغل، تصویر و پیوست‌ها، مهارت‌ها، درباره من، و اطلاعات تکمیلی. زیر صفحه، بخش «کاربر» نمایش داده می‌شود تا نام، ایمیل، نقش، وضعیت، حضور و سهمیهٔ کاربرِ این پروفایل را ویرایش کنید.'],
        ['icon' => 'delete', 'label' => 'حذف دائمی', 'hint' => 'پروفایل برای همیشه پاک می‌شود و بازگردانی ندارد. حذف پروفایل، حساب کاربر و مهارت‌های او را پاک نمی‌کند؛ پیش از حذف مطمئن شوید که این کار موردنظر شماست.'],
        ['icon' => 'download', 'label' => 'خروجی اکسل', 'hint' => 'ردیف‌های موردنظر را انتخاب کنید و از منوی عملیات گروهی، خروجی اکسل بگیرید. انتخاب همهٔ ردیف‌های فهرستِ فیلترشده هم ممکن است.'],
    ];
    $skillsOps = [
        ['icon' => 'workspace_premium', 'label' => 'ذخیرهٔ مهارت‌ها', 'hint' => 'زبانهٔ مهارت‌ها در فرم، ردیف‌ها را با user.skillUsers مقایسه می‌کند: ردیفِ نگه‌داشته‌شده update، ردیفِ جدید create و ردیفِ حذف‌شده از فرم، از SkillUser پاک می‌شود. موفقیت/رد بودن و آخرین استفاده و پرچم‌های mentoring/private را اینجا تنظیم می‌کنید.'],
        ['icon' => 'verified_user', 'label' => 'تأیید و رد', 'hint' => 'وضعیت هر مهارت «در حال بررسی»، «تایید شده» یا «رد شده» است (پیش‌فرض: تایید شده). وقتی «تایید شده» باشد، زمان تأیید و تأییدکننده خودکار ثبت می‌شود.'],
        ['icon' => 'list_alt', 'label' => 'اطلاعات تکمیلی', 'hint' => 'هر ردیف یک عنوان از فهرست آمادهٔ اطلاعات تکمیلی است و نوع ورودی (متن، عدد، متن بلند، انتخابی، تاریخ) خودکار از عنوان مشخص می‌شود. گزینه‌های «واحد» و «بخش» از واحدِ سازمانیِ همین پروفایل خوانده می‌شوند. عنوان سفارشی هم می‌توانید بسازید. کلید «ترتیب در چارت» (فقط ادمین) جایگزین رتبهٔ سمت در چینش همکاران هر دپارتمان در چارت سازمانی می‌شود؛ عدد کوچک‌تر بالاتر؛ مقیاس رتبه‌ها: رئیس هیئت‌مدیره/مدیرعامل ۱، مدیر ارشد ۲، مدیر ۳، سرپرست ۴، ارشد ۵، کارشناس ۶، کارمند ۷؛ عدد اعشاری هم مجاز است (مثلاً ۳٫۵ بین مدیر و سرپرست)؛ برای رئیس هیئت‌مدیره/مدیرعاملِ یک شرکتِ زیرمجموعه، به‌جای عدد علامت * را بنویسید — او در ردیف بالای چارت هم‌رتبهٔ مدیرعامل می‌ماند (کمی پایین‌تر) و واحدش به‌همراه واحدهایی که «تابع» آن هستند با رنگ پس‌زمینهٔ متفاوت نمایش داده می‌شوند، و در صورت خالی بودن همان رتبهٔ سمت و سپس نام ملاک است.'],
        ['icon' => 'cloud_upload', 'label' => 'تصویر و پیوست', 'hint' => 'تصویر پروفایل (حداکثر ۵ مگابایت) و پیوست‌ها (فایل PDF، تصویر یا سند، حداکثر ۱۰ مگابایت) ذخیره می‌شوند. در اینفولیست، مدارک در دو گروه جداگانهٔ «مدارک استاندارد» و «مدارک سفارشی» نمایش داده می‌شوند و هر ردیف با لینک «مشاهده فایل» باز می‌شود؛ نام فایل سفارشی همان عنوانی است که کاربر تایپ کرده است.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما: تکمیل و نظارت بر پروندهٔ پرسنلی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        سه دکمهٔ عملیات روی هر ردیف (پس از سلول‌ها): مشاهده، ویرایش، حذف. دکمهٔ «ساخت پروفایل» در هدر قرار دارد. جستجوی سراسری پنل هم پروفایل‌ها را با نام کاربر، کد پرسنلی، شماره ملی، شناسنامه یا موبایل پیدا می‌کند.
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $op['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">tune</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکات ویرایش: مهارت‌ها، اطلاعات تکمیلی، رسانه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($skillsOps as $op)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $op['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                عنوان‌های «واحد»، «بخش»، «عنوان نمایشی» و «ترتیب در چارت» را در اطلاعات تکمیلی فقط مدیر می‌تواند تنظیم کند — کاربر واحد، بخش و عنوان نمایشی را در پنل خودش فقط می‌بیند (نه ویرایش) و «ترتیب در چارت» را اصلاً نمی‌بیند.
            </p>
        </div>
    </div>
</div>