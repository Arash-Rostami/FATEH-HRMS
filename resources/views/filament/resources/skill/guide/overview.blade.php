@php
    $d68 = convertToPersian('68');
    $d2000 = convertToPersian('2000');
    $d255 = convertToPersian('255');

    $fields = [
        ['icon' => 'label', 'label' => 'نام مهارت', 'tag' => 'یکتا', 'hint' => 'هر مهارت در سراسر سیستم با این نام شناخته می‌شود. تکراری نبودن نام هم در فارسی و هم در انگلیسی بررسی می‌شود (به «اشتراک نام و فعال‌سازی» نگاه کنید). حداکثر ' . $d255 . ' نویسه.'],
        ['icon' => 'translate', 'label' => 'نام انگلیسی', 'tag' => 'یکتا', 'hint' => 'نام انگلیسی اختیاری است، اما اگر نوشته شود در همان بررسی نام تکراری حساب می‌شود — دو مهارت نمی‌توانند نام فارسی یا نام انگلیسیِ یکسان داشته باشند.'],
        ['icon' => 'category', 'label' => 'دسته‌بندی', 'tag' => 'موضوعی', 'hint' => 'دسته‌بندی را خودتان تایپ می‌کنید: از دسته‌های موجود انتخاب می‌کنید یا با «ساخت گزینه» دستهٔ جدید می‌سازید. این دسته به‌صورت برچسب در فهرست مهارت‌های کاربر نشان داده می‌شود و جدول هم بر اساس آن گروه‌بندی می‌شود.'],
        ['icon' => 'palette', 'label' => 'نماد', 'tag' => 'متریال', 'hint' => 'یکی از ' . $d68 . ' نماد آمادهٔ فهرست. نماد پیش‌فرض «مدال» است. این نماد در پنل کاربر کنار نام مهارت نشان داده می‌شود.'],
        ['icon' => 'subject', 'label' => 'توضیحات', 'tag' => 'نمایشی', 'hint' => 'توضیح کوتاه دربارهٔ مهارت — حداکثر ' . $d2000 . ' کاراکتر، اختیاری.'],
        ['icon' => 'toggle_on', 'label' => 'فعال', 'tag' => 'وضعیت', 'hint' => 'کلید فعال/غیرفعال که از ابتدا روشن است. فقط مهارت‌های فعال در کاتالوگِ کاربر و زبانهٔ «کاتالوگ» ظاهر می‌شوند. مهارت غیرفعال در زبانهٔ «غیرفعال» می‌نشیند.'],
    ];

    $states = [
        ['icon' => 'check_circle', 'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]', 'label' => 'فعال', 'note' => 'مهارت فعال است — در کاتالوگ کاربر و زبانهٔ «کاتالوگ» دیده می‌شود.'],
        ['icon' => 'remove_circle', 'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]', 'label' => 'غیرفعال', 'note' => 'مهارت غیرفعال است — در زبانهٔ «غیرفعال» با عدد شمارش دیده می‌شود. قابل ویرایش و فعال‌سازی است.'],
        ['icon' => 'auto_awesome', 'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]', 'label' => 'تأمین‌نشده', 'note' => 'مهارتی که کاربر جستجو کرده اما در کاتالوگ نبوده؛ با جستجوی کاربر ثبت شده، نه توسط شما. در زبانهٔ «مهارت‌های جستجوشده» دیده می‌شود (زبانهٔ «جستجوی تأمین‌نشده» را ببینید).'],
    ];

    $links = [
        ['icon' => 'person', 'label' => 'دارندگان', 'hint' => 'هر مهارت به کاربرانی که آن را دارند وصل است. ستون «تعداد دارندگان» جدول این تعداد را نشان می‌دهد و تغییر یا حذف این ارتباط از صفحهٔ کاربر یا پروفایل انجام می‌شود، نه اینجا.'],
        ['icon' => 'verified_user', 'label' => 'تأیید مهارت', 'hint' => 'صفِ تأیید یا ردِ درخواست‌های مهارتِ کاربران در بخش جداگانهٔ «تأیید مهارت» قرار دارد؛ با دکمهٔ «تأیید مهارت‌ها» در بالای همین صفحه به آن بروید (در منو ورودی جداگانه ندارد) — آنجا وضعیت «در حال بررسی»، «تایید شده» و «رد شده» را مدیریت می‌کنید.'],
        ['icon' => 'groups', 'label' => 'مهارت‌های کاربر', 'hint' => 'در صفحهٔ ویرایش هر کاربر، بخش «مهارت‌ها» فقط برای مشاهده نمایش داده می‌شود و مهارت‌های آن کاربر (وضعیت، سطح، تأییدها، آخرین استفاده) را فهرست می‌کند.'],
        ['icon' => 'workspace_premium', 'label' => 'پنل کاربر (پروفایل / استعدادها)', 'hint' => 'کار کاربران با مهارت‌ها — انتخاب، پیشنهاد، تأیید همکار و سطح‌بندی خودکار — در زبانهٔ «استعدادها»ی پروفایل و فهرست استعدادها انجام می‌شود. راهنمای آن بخش در راهنمای ماژول «پروفایل» (زبانهٔ «تجربهٔ کاربر») آمده است.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">bolt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«مهارت» یک مورد از کاتالوگ است که پنل کاربر و فهرست استعدادها از آن استفاده می‌کنند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر مورد در این صفحه یک مهارت سازمانی است (مثلاً طراحی محصول، پایتون، مذاکره). این ماژول کاملاً مدیریتی است — شما کاتالوگ مهارت‌ها را تعریف و نگهداری می‌کنید؛ کاربران از زبانهٔ «استعدادها»ی پروفایل خود از همین کاتالوگ انتخاب می‌کنند یا مهارت جدید پیشنهاد می‌دهند. یک مهارت سه وضعیت دارد: فعال، غیرفعال و تأمین‌نشده.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلدهای هر مهارت</p>
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
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">{{ $f['tag'] }}</span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                فهرست مهارت‌های فعال برای سرعت بیشتر تا یک روز ذخیره می‌شود و پس از هر ذخیره یا حذف مهارت خودکار تازه می‌شود — تغییر شما بلافاصله در فهرست مهارت‌های کاربر دیده می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">toggle_on</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سه وضعیت یک مهارت</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($states as $s)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $s['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['note'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">چه چیزهایی به یک مهارت وصل است؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($links as $l)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $l['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $l['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $l['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>