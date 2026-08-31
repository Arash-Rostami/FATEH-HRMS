<div class="flex flex-col gap-5" dir="rtl">
    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات ادمین روی پروژه‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانهٔ «وظایف» در صفحهٔ ویرایش هر پروژه، تمام وظایف آن پروژه را نمایش می‌دهد — می‌توانید از همان‌جا وضعیت/مسئول را تغییر دهید یا وظیفهٔ جدیدی مستقیماً برای این پروژه بسازید، بدون نیاز به رفتن به بخش «تسک بورد».
    </p>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        حذف نرم (Delete) یک پروژه آن را از فهرست‌ها پنهان می‌کند اما وظایف و کانال آن دست‌نخورده باقی می‌مانند. پروژه‌های حذف‌شده پس از ۳۰ روز به‌صورت خودکار حذف کامل می‌شوند — در این لحظه وظایف مرتبط فقط از پروژه جدا می‌شوند، نه حذف.
    </p>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        تا پیش از هرس خودکار، یک پروژهٔ حذف‌شده را می‌توان با اکشن «بازیابی» (Restore) روی همان ردیف بازگرداند — ردیف‌های حذف‌شده در فهرست پنهان نیستند چون کوئریِ پایهٔ این صفحه <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">withoutGlobalScope(SoftDeletingScope)</code> را اعمال می‌کند. اکشن حذف فقط روی ردیف‌های حذف‌نشده و بازیابی فقط روی حذف‌شده‌ها ظاهر می‌شود.
    </p>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        ستون «پیشرفت» در همین زبانه، درصد تکمیلِ چک‌لیستِ هر وظیفه را به‌صورت نوار میله‌ای نشان می‌دهد (از ReportingService محاسبه می‌شود؛ جداگانه از درصد پیشرفتِ کلی پروژه که در بخش «بررسی» است).
    </p>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        اکشن «مشاهده تسک‌شیت» روی هر ردیف، گزارش عملکرد مالکِ همان پروژه را در تب جدید باز می‌کند.
    </p>
    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">settings</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">تنظیمات پروژه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <p class="px-4 pt-3 text-[12px] text-[var(--md-sys-color-on-surface-variant)]">هر تغییر در این چهار تنظیم — از هر پنلی — به‌طور خودکار یک ردیف «تغییر تنظیمات پروژه» در فعالیت‌های پروژه (پنل کاربر) ثبت می‌کند.</p>
            @foreach([
                ['icon' => 'verified', 'label' => 'نیازمند تأیید مدیر', 'hint' => 'فعال‌سازی آن چرخهٔ تأیید را برای وظایف همین پروژه روشن می‌کند: وظیفه‌ای که «انجام‌شده» می‌شود تا تأیید مدیرِ پروژه در حالت «منتظر تأیید» می‌ماند. مدیر خودش اگر وظیفه را انجام دهد، خودکار تأیید می‌شود. در پروژه‌های بدون این تنظیم هیچ تغییری در رفتار همیشگی نیست.'],
                ['icon' => 'timer', 'label' => 'SLA (ساعت)', 'hint' => 'عدد اختیاری؛ وظیفه‌ای که بیش از این مقدار ساعت باز مانده باشد، نوار فوریتِ «نقض SLA» روی کارتش در پنل کاربر می‌گیرد. خالی بماند این هشدار اصلاً صادر نمی‌شود.'],
                ['icon' => 'event_busy', 'label' => 'سقف مهلت پروژه', 'hint' => 'تاریخ اختیاری که سقفِ ضرب‌الاجلِ همهٔ وظایف همین پروژه است؛ ثبت یا ویرایش وظیفه با مهلتی فراتر از آن در هر دو پنل رد می‌شود. کاربر پنل کاربر همین مقدار را در تنظیمات پروژهٔ خودش می‌بیند و تغییرش دوسویه است.'],
                ['icon' => 'tune', 'label' => 'دیتای سفارشی (custom_schema)', 'hint' => 'فهرست کلید/برچسب‌ها که تب «دیتای سفارشی» وظایفِ همین پروژه را از حالت کلید/مقدار آزاد به فرمِ دقیقِ همان فیلدها با برچسب فارسی تبدیل می‌کند. کلید باید یکتا و فقط با حروف کوچک انگلیسی/رقم/زیرخط باشد.'],
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
