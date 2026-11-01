@php
    $header = [
        ['icon' => 'swap_horiz', 'label' => 'قوانین اعلان / گزارش ارسال', 'hint' => 'دو دکمهٔ بالای صفحه بین فهرست قانون‌ها و گزارش ارسال جابه‌جا می‌شوند؛ دکمهٔ صفحهٔ فعلی پررنگ است. دکمهٔ راهنما در هر دو صفحه هست.'],
        ['icon' => 'outgoing_mail', 'label' => 'ارسال فوری', 'hint' => 'پیام یک‌باره بدون قانون؛ جزئیاتش در زبانهٔ «بررسی» آمده است. هر ارسال فوری یک ردیف با نام «ارسال دستی» در فهرست قانون‌ها می‌سازد.'],
    ];

    $rowActions = [
        ['icon' => 'visibility', 'label' => 'مشاهده', 'hint' => 'جزئیات فقط‌خواندنی: ماژول، رویدادها، کانال‌ها، گیرندگان، گیرندگان خارج از سیستم، شرط‌ها، موضوع و سطرهای پیام، وضعیت، بازهٔ اعتبار، تاریخ ایجاد و تعداد ارسال.'],
        ['icon' => 'mail', 'label' => 'ایمیل آزمایشی', 'hint' => 'پیام نمونه را فقط به ایمیل خودتان می‌فرستد. فقط وقتی دیده می‌شود که قانون کانال ایمیل داشته باشد، آن کانال برای سازمان روشن باشد، دسترسی ویرایش داشته باشید و ماژول قانون جزو ماژول‌های مجاز شما باشد. اگر ایمیل شما ثبت نشده باشد، نتیجه همین را اعلام می‌کند.'],
        ['icon' => 'sms', 'label' => 'پیامک آزمایشی', 'hint' => 'همان رفتار، با شمارهٔ موبایل خودتان. اگر سرویس پیامک هنوز پیکربندی نشده باشد، نتیجه «سرویس این کانال هنوز پیکربندی نشده است» را نشان می‌دهد. آزمایشی در گزارش ارسال ثبت نمی‌شود و شرط‌های قانون را بررسی نمی‌کند.'],
        ['icon' => 'edit', 'label' => 'ویرایش', 'hint' => 'برای قانون‌های «ارسال دستی» نمایش داده نمی‌شود و آن‌ها اصلاً قابل ویرایش نیستند؛ همچنین کلید فعال/غیرفعال آن‌ها قفل است. ویرایش به دسترسی «به‌روزرسانی» ماژول نیاز دارد.'],
        ['icon' => 'delete', 'label' => 'حذف', 'hint' => 'قانون را برای همیشه برمی‌دارد و همهٔ ردیف‌های گزارش ارسال آن هم با آن حذف می‌شوند. اگر فقط می‌خواهید ارسال متوقف شود، کلید «فعال» را خاموش کنید. «حذف گروهی» روی ردیف‌های انتخاب‌شده همین کار را می‌کند.'],
    ];

    $columns = [
        ['label' => 'ماژول', 'hint' => 'برچسب آبی نام ماژول است؛ برچسب زرد «ارسال دستی» یعنی ارسال فوریِ یک‌باره، نه قانون.'],
        ['label' => 'شرط‌ها', 'hint' => 'تعداد شرط‌ها به‌صورت «۲ شرط» یا خط تیره؛ با نگه‌داشتن اشاره‌گر، فهرست شرط‌ها خوانده می‌شود.'],
        ['label' => 'گیرندگان', 'hint' => 'برای کاربران انتخابی، جمع کاربران و گیرندگان خارج از سیستم؛ برای دو حالت دیگر عبارت «همهٔ کارکنان» یا «واحدهای انتخابی» که اشاره‌گر روی دومی نام واحدها را نشان می‌دهد.'],
        ['label' => 'فعال', 'hint' => 'کلید روشن/خاموش مستقیم در جدول؛ بدون باز کردن فرم. قانونِ روشن ولی خارج از بازهٔ اعتبار هم ارسال نمی‌کند.'],
        ['label' => 'بازهٔ اعتبار / تاریخ ایجاد', 'hint' => 'این دو ستون پیش‌فرض پنهان‌اند. بازه به‌صورت «شروع ← پایان» شمسی نشان داده می‌شود و سمت خالی با «…» پر می‌شود.'],
        ['label' => 'تعداد ارسال', 'hint' => 'تعداد ردیف‌های گزارش ارسال همین قانون (قابل مرتب‌سازی)، شامل ردیف‌های خلاصهٔ اجرا.'],
    ];

    $filters = [
        ['label' => 'ماژول', 'hint' => 'یکی از ماژول‌های قابل ردیابی، یا «ارسال دستی» برای دیدن فقط ارسال‌های فوری.'],
        ['label' => 'فعال', 'hint' => 'سه‌حالته: همه، فقط روشن‌ها یا فقط خاموش‌ها بر اساس کلید.'],
        ['label' => 'در حال اجرا', 'hint' => 'قانون‌هایی که همین الان واقعاً ارسال می‌کنند: روشن‌اند و زمان فعلی داخل بازهٔ اعتبارشان است. خاموشِ این فیلتر، روشن‌های خارج از بازه را هم نشان می‌دهد.'],
        ['label' => 'بازهٔ تاریخ ایجاد', 'hint' => 'فیلتر شمسی بر اساس تاریخ ساخت.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: مدیریت قانون‌ها، آزمایش و پاکسازی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف پنج دکمهٔ عملیات (پس از سلول‌ها) دارد: مشاهده، ایمیل آزمایشی، پیامک آزمایشی، ویرایش و حذف. جستجوی جدول روی «نام قانون» کار می‌کند و پیش‌فرض جدول بر اساس شناسه از جدیدترین است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">smart_button</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">دکمه‌های بالای صفحه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($header as $h)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $h['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $h['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $h['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات روی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rowActions as $op)
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">auto_delete</span>
                ردیف‌های «ارسال دستی» ۹۰ روز پس از ساخت به‌صورت خودکار پاک می‌شوند؛ قانون‌های عادی هرگز خودکار پاک نمی‌شوند.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">view_column</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">ستون‌های جدول</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($columns as $c)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">table_rows</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $c['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $c['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">فیلترها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">filter_list</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
