@php
    $pills = [
        ['id' => 'timeline', 'icon' => 'view_carousel', 'label' => 'تایم‌لاین'],
        ['id' => 'poll', 'icon' => 'ballot', 'label' => 'نظرسنجی'],
        ['id' => 'comments', 'icon' => 'forum', 'label' => 'نظرات'],
        ['id' => 'access', 'icon' => 'lock', 'label' => 'دسترسی'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $timeline = [
        ['icon' => 'open_in_full', 'color' => 'primary', 'label' => 'کارت فعال بزرگ‌تر', 'text' => 'در رایانه، خبر انتخاب‌شده بزرگ‌تر نمایش داده می‌شود و نقطهٔ آن روی خط زمان پررنگ است. تاریخ انتشار زیر هر نقطه دیده می‌شود؛ برای دیدن دستهٔ خبر، نشانگر را روی کارت نگه دارید. با دکمه‌های چپ و راست بین خبرها جابه‌جا شوید و با Esc کارت را به اندازهٔ عادی برگردانید. در موبایل، کارت‌ها هم‌اندازه‌اند و ارتفاع آن‌ها با متن تنظیم می‌شود.'],
        ['icon' => 'add_circle', 'color' => 'secondary', 'label' => 'بارگذاری خودکار', 'text' => 'در حالت عادی هر بار ۳ فید بارگذاری می‌شود و با رسیدن به انتهای تایم‌لاین، فیدهای قدیمی‌تر خودکار اضافه می‌شوند. اما وقتی جستجو یا فیلتر دسته فعال است، صفحه‌بندی غیرفعال می‌شود و همهٔ فیدهای منطبق یک‌جا می‌آیند.'],
        ['icon' => 'expand_more', 'color' => 'tertiary', 'label' => 'گسترش متن', 'text' => 'متن طولانی‌تر از ۲۸۰ کاراکتر با گرادیان جمع می‌شود؛ «مشاهده بیشتر» متن کامل را باز می‌کند. دکمهٔ گوشه کارت (فقط دسکتاپ)، فید را تمام‌صفحه می‌کند.'],
        ['icon' => 'mark_chat_read', 'color' => 'primary', 'label' => 'خوانده‌شده در ورود', 'text' => 'با باز کردن صفحهٔ فید، همهٔ فیدها خوانده‌شده علامت‌گذاری می‌شوند و نشان اعلان منو پاک می‌شود. نشان فقط فیدهای منتشرشده پس از آخرین بازدید را می‌شمارد.'],
        ['icon' => 'calendar_month', 'color' => 'secondary', 'label' => 'فیلتر ماه', 'text' => 'فیلتر ماه مانند گالری عمل می‌کند: فیدهای بارگذاری‌شده را بر اساس ماه شمسی انتشار فیلتر می‌کند. همهٔ فیدهای منطبق با جستجو/دسته همچنان در تایم‌لاین بارگذاری می‌شوند و «همه ماه‌ها» فیلتر را پاک می‌کند.'],
        ['icon' => 'dashboard', 'color' => 'tertiary', 'label' => 'نمای مجله', 'text' => 'با دکمهٔ «نمای مجله» در نوار بالا، خبرها به‌صورت کارت‌های ستونی نمایش داده می‌شوند. نوار رنگی هر کارت، دستهٔ خبر را مشخص می‌کند و تعداد نظرها و ریاکشن‌ها، نشان نظرسنجی و زمان انتشار در پایین آن دیده می‌شود. برای بازکردن خبر روی کارت بزنید. دکمه‌های چپ و راست فقط در نمای خط زمان در دسترس‌اند.'],
    ];

    $poll = [
        ['icon' => 'check_circle', 'color' => 'primary', 'label' => 'تک‌انتخابی: رأی جایگزین', 'text' => 'در حالت تک‌انتخابی، رأی جدید رأی قبلی را جایگزین می‌کند. کلیک روی همان گزینهٔ قبلی، رأی را پس می‌گیرد.'],
        ['icon' => 'add_circle', 'color' => 'secondary', 'label' => 'نظرسنجی چندانتخابی', 'text' => 'می‌توانید چند گزینه را هم‌زمان انتخاب کنید. با انتخاب دوبارهٔ هر گزینه، فقط همان گزینه لغو می‌شود. این نوع نظرسنجی با نشان «چندانتخابی» مشخص است.'],
        ['icon' => 'how_to_vote', 'color' => 'tertiary', 'label' => 'درصد و رأی من', 'text' => 'درصد آرا پس از ثبت رأی نمایش داده می‌شود. گزینهٔ انتخابی شما با علامت تأیید مشخص می‌شود و پیام «رأی شما ثبت شد» را می‌بینید.'],
        ['icon' => 'forum', 'color' => 'error', 'label' => 'نظر و ریاکشنِ نظرسنجی', 'text' => 'وقتی دسته فید «نظرسنجی» باشد، بخش نظر و ریاکشن فقط در صورتی ظاهر می‌شود که در تنظیمات نظرسنجی فعال باشند — در غیر این صورت فقط گزینه‌های رأی نمایش داده می‌شوند.'],
    ];

    $comments = [
        ['icon' => 'forum', 'color' => 'primary', 'label' => 'بارگذاری در اولین باز کردن', 'text' => 'بخش نظرات در اولین کلیک روی «نظرات» بارگذاری می‌شود، نه برای همهٔ فیدها. پس از باز کردن، نظرات و پاسخ‌ها آماده‌اند.'],
        ['icon' => 'subdirectory_arrow_left', 'color' => 'tertiary', 'label' => 'پاسخ‌های زنجیره‌ای', 'text' => 'پاسخ‌ها زیر نظر والد به‌صورت زنجیره‌ای نمایش داده می‌شوند و با دکمهٔ «N پاسخ» باز/بسته می‌شوند.'],
        ['icon' => 'edit', 'color' => 'secondary', 'label' => 'ویرایش و حذف فقط نظرات خودتان', 'text' => 'تنها نویسنده می‌تواند نظرش را ویرایش یا حذف کند. حذف یک نظر، پاسخ‌های آن را به نظر بالاتر منتقل می‌کند — پاسخ‌ها پاک نمی‌شوند.'],
        ['icon' => 'edit_note', 'color' => 'primary', 'label' => 'میان‌برهای نوشتن', 'text' => 'Enter نظر را ارسال می‌کند، Shift+Enter خط جدید می‌گذارد، **متن** پررنگ می‌شود. با دکمهٔ ایموجی، ایموجی در نظر درج می‌شود.'],
    ];

    $access = [
        ['icon' => 'public', 'color' => 'primary', 'label' => 'عمومی', 'text' => 'فیدی که واحد مخاطب ندارد برای همهٔ همکاران قابل دید است.'],
        ['icon' => 'lock', 'color' => 'tertiary', 'label' => 'مخصوص واحد', 'text' => 'فیدِ محدود فقط برای همکاران واحدهای انتخاب‌شده دیده می‌شود و اعلان آن هم فقط به همان‌ها می‌رسد؛ فیلترهای دسته‌بندی نیز فقط از فیدهای قابل دید شما ساخته می‌شوند.'],
        ['icon' => 'visibility_off', 'color' => 'secondary', 'label' => 'مخفی‌شده توسط ادمین', 'text' => 'ادمین می‌تواند فید را بدون حذف مخفی کند؛ فید مخفی در فهرست، جستجو و لینک مستقیم دیده نمی‌شود و نظرها و ریاکشن‌هایش محفوظ می‌مانند.'],
    ];

    $notes = [
        'هر کاربر فقط یک ریاکشن برای هر فید دارد: کلیک روی ایموجیِ جدید ریاکشن قبلی را جایگزین می‌کند و کلیک روی همان ایموجی آن را پس می‌گیرد. نوار ایموجی صفحه‌بندی است و با دکمهٔ کناری چرخیده می‌شود.',
        'با رفتن نشانگر روی هر ایموجیِ ریاکشن، عنوانِ نام کاربرانی که ریاکشن داده‌اند نمایش داده می‌شود.',
        'جستجوی متن و فیلتر دسته، صفحه‌بندی تایم‌لاین را غیرفعال می‌کند و همهٔ فیدهای منطبق را یک‌جا می‌آورد — برای خروج از این حالت «پاک کردن فیلتر» را بزنید.',
        'فیدهای دستهٔ «نظرسنجی» بدون بخش نظر/ریاکشنِ جداگانه هستند مگر آنکه تنظیمات نظرسنجی آن‌ها را فعال کرده باشد.',
    ];
@endphp

<div x-data="{ tab: 'timeline' }">
    <div class="flex flex-wrap gap-y-1 p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($pills as $pill)
            <button
                type="button"
                @click="tab = '{{ $pill['id'] }}'"
                :class="tab === '{{ $pill['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[17px]">{{ $pill['icon'] }}</span>
                {{ $pill['label'] }}
            </button>
        @endforeach
    </div>

    @foreach(['timeline' => $timeline, 'poll' => $poll, 'comments' => $comments, 'access' => $access] as $tabId => $rows)
        <div x-show="tab === '{{ $tabId }}'" x-cloak class="space-y-3">
            @foreach($rows as $s)
                @php
                    $chipClasses = match ($s['color']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                        <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                        <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>