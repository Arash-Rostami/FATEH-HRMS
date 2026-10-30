<div class="flex flex-col gap-5" dir="rtl">
    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کارهای مدیریتی شما روی پروژه‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانهٔ «وظایف» در صفحهٔ ویرایش هر پروژه، همهٔ وظایف آن پروژه را نشان می‌دهد. از همان‌جا می‌توانید وضعیت یا مسئول وظیفه را عوض کنید یا وظیفهٔ تازه‌ای برای این پروژه بسازید؛ لازم نیست به بخش «تسک بورد» بروید.
    </p>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        حذف موقت (Delete) یک پروژه آن را از فهرست‌ها پنهان می‌کند اما وظایف و گروه آن دست‌نخورده باقی می‌مانند؛ چرخه‌های کاریِ در حال اجرا یا پیش‌نویس با دلیل «حذف پروژه» لغو می‌شوند و با بازیابی پروژه، به‌صورت پیش‌نویس برمی‌گردند. پروژه‌های حذف‌شده پس از ۳۰ روز برای همیشه پاک می‌شوند؛ در آن لحظه وظایف مرتبط فقط از پروژه جدا می‌شوند و حذف نمی‌شوند.
    </p>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        تا پیش از هرس خودکار، یک پروژهٔ حذف‌شده را می‌توان با اکشن «بازیابی» (Restore) روی همان ردیف بازگرداند — ردیف‌های حذف‌شده در فهرست پنهان نیستند چون کوئریِ پایهٔ این صفحه <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">withoutGlobalScope(SoftDeletingScope)</code> را اعمال می‌کند. اکشن حذف فقط روی ردیف‌های حذف‌نشده و بازیابی فقط روی حذف‌شده‌ها ظاهر می‌شود.
    </p>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        ستون «پیشرفت» در همین زبانه، درصد تکمیل چک‌لیست هر وظیفه را با یک نوار نشان می‌دهد؛ این عدد با درصد پیشرفت کلی پروژه که در زبانهٔ «بررسی» است فرق دارد.
    </p>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        دکمهٔ «مشاهدهٔ برگهٔ کارکرد» کنار هر پروژه، گزارش کارکرد مالک همان پروژه را در زبانهٔ تازهٔ مرورگر باز می‌کند.
    </p>
    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">settings</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">تنظیمات پروژه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <p class="px-4 pt-3 text-[12px] text-[var(--md-sys-color-on-surface-variant)]">هر تغییر در این تنظیمات، از هر پنلی که انجام شود، خودکار با عنوان «تغییر تنظیمات پروژه» در فعالیت‌های پروژه (پنل کاربر) ثبت می‌شود.</p>
            @foreach([
                ['icon' => 'verified', 'label' => 'نیازمند تأیید مدیر پروژه', 'hint' => 'فعال‌سازی آن چرخهٔ تأیید را برای وظایف همین پروژه روشن می‌کند: وظیفه‌ای که «انجام‌شده» می‌شود تا تأیید مدیرِ پروژه در حالت «منتظر تأیید» می‌ماند. مدیر خودش اگر وظیفه را انجام دهد، خودکار تأیید می‌شود. در پروژه‌های بدون این تنظیم هیچ تغییری در رفتار همیشگی نیست.'],
                ['icon' => 'timer', 'label' => 'مهلت پیش‌فرض (ساعت)', 'hint' => 'عددی اختیاری. اگر وظیفه‌ای بدون مهلت ساخته شود، مهلتش خودکار «اکنون + این تعداد ساعت» می‌شود (حداکثر تا مهلت پروژه). وظیفهٔ قدیمیِ بدون مهلت هم اگر بیش از این ساعت باز بماند، نوار هشدار می‌گیرد. اگر خالی بماند هیچ‌کدام اعمال نمی‌شود.'],
                ['icon' => 'event_busy', 'label' => 'مهلت پروژه', 'hint' => 'تاریخ اختیاری (انتخابگر شمسی است و با دکمهٔ تقویمِ نوار بالا به میلادی تغییر می‌کند؛ در هر دو حالت همان تاریخ ذخیره می‌شود) که سقفِ ضرب‌الاجلِ همهٔ وظایف همین پروژه است؛ ثبت یا تغییرِ مهلت وظیفه به تاریخی فراتر از آن در هر دو پنل رد می‌شود (وظیفهٔ قدیمی با مهلت بالاتر همچنان ویرایش‌پذیر است؛ مهلتِ پروژه نمی‌تواند در گذشته باشد). کاربر پنل کاربر همین مقدار را در تنظیمات پروژهٔ خودش می‌بیند و تغییرش دوسویه است.'],
                ['icon' => 'tune', 'label' => 'فیلدهای سفارشی وظایف', 'hint' => 'فهرستی از فیلدها (نام و عنوان) که بخش «دیتای سفارشی» وظایف این پروژه را از حالت آزاد (نام و مقدار دلخواه) به فرمی با همین فیلدها و عنوان فارسی تبدیل می‌کند. نام هر فیلد باید یکتا باشد و فقط از حروف کوچک انگلیسی، رقم و زیرخط (_) تشکیل شود.'],
                ['icon' => 'sell', 'label' => 'فهرست برچسب‌های مجاز', 'hint' => 'حداکثر ۳۰ برچسب (هر کدام تا ۳۰ حرف). اگر این فهرست پر باشد، وظایف پروژه برای برچسب‌های تازه فقط از همین فهرست انتخاب می‌کنند؛ برچسب‌های قبلی وظایف می‌مانند. اگر خالی باشد، هر برچسبی مجاز است.'],
                ['icon' => 'archive', 'label' => 'بایگانی خودکار پس از (روز)', 'hint' => 'عدد اختیاری (پیش‌فرض ۴۵)؛ وظیفهٔ انجام‌شده و تأییدشده پس از این تعداد روز بایگانی می‌شود.'],
                ['icon' => 'library_add', 'label' => 'تنظیمات دلخواه', 'hint' => 'تنظیمات دلخواه (نام و مقدار) مالک پروژه، علاوه بر تنظیمات بالا. مثلاً escalation_days یعنی «چند روز تأخیر تا ارجاع به جوابگو» و due_soon_hours یعنی «چند ساعت مانده به سررسید هشدار داده شود». مالک پروژه همین‌ها را در پنل کاربری هم می‌بیند و ویرایش می‌کند و تغییر در هر طرف در طرف دیگر هم دیده می‌شود؛ با حذف یک مورد، آن تنظیم از پروژه برداشته می‌شود. مواردی که نام یا مقدارشان خالی باشد نادیده گرفته می‌شود و نامی که با تنظیمات اصلی یکی باشد پذیرفته نمی‌شود.'],
            ] as $s)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
