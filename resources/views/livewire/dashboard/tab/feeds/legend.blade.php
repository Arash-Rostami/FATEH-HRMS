@php
    $pills = [
        ['id' => 'timeline', 'icon' => 'view_carousel', 'label' => 'تایم‌لاین'],
        ['id' => 'poll', 'icon' => 'ballot', 'label' => 'نظرسنجی'],
        ['id' => 'comments', 'icon' => 'forum', 'label' => 'نظرات'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $timeline = [
        ['icon' => 'open_in_full', 'color' => 'primary', 'label' => 'کارت فعال بزرگ‌تر', 'text' => 'کارتِ فیدِ فعال در تایم‌لاین ۱.۱۵× بزرگ‌نمایی می‌شود و نقطهٔ آن روی خط زمان پررنگ می‌شود. با دکمه‌های چپ/راست بین فیدها جابه‌جا شوید؛ Esc کارتِ بازشده را کوچک می‌کند.'],
        ['icon' => 'add_circle', 'color' => 'secondary', 'label' => 'بارگذاری خودکار', 'text' => 'در حالت عادی هر بار ۳ فید بارگذاری می‌شود و با رسیدن به انتهای تایم‌لاین، فیدهای قدیمی‌تر خودکار اضافه می‌شوند. اما وقتی جستجو یا فیلتر دسته فعال است، صفحه‌بندی غیرفعال می‌شود و همهٔ فیدهای منطبق یک‌جا می‌آیند.'],
        ['icon' => 'expand_more', 'color' => 'tertiary', 'label' => 'گسترش متن', 'text' => 'متن طولانی‌تر از ۱۶۰ کاراکتر با گرادیان جمع می‌شود؛ «مشاهده بیشتر» متن کامل را باز می‌کند. دکمهٔ گوشه کارت، فید را تمام‌صفحه می‌کند.'],
        ['icon' => 'mark_chat_read', 'color' => 'primary', 'label' => 'خوانده‌شده در ورود', 'text' => 'با باز کردن صفحهٔ فید، همهٔ فیدها خوانده‌شده علامت‌گذاری می‌شوند و نشان اعلان منو پاک می‌شود. نشان فقط فیدهای منتشرشده پس از آخرین بازدید را می‌شمارد.'],
        ['icon' => 'calendar_month', 'color' => 'secondary', 'label' => 'فیلتر ماه', 'text' => 'فیلتر ماه مانند گالری عمل می‌کند: فیدهای بارگذاری‌شده را بر اساس ماه شمسی انتشار فیلتر می‌کند. همهٔ فیدهای منطبق با جستجو/دسته همچنان در تایم‌لاین بارگذاری می‌شوند و «همه ماه‌ها» فیلتر را پاک می‌کند.'],
        ['icon' => 'dashboard', 'color' => 'tertiary', 'label' => 'نمای مجله', 'text' => 'دکمهٔ نمای مجله در نوار بالا، فیدها را به‌جای تایم‌لاین به‌صورت کارت‌های فشردهٔ ستونی (masonry) نمایش می‌دهد؛ نوار رنگی بالای هر کارت نشان‌دهندهٔ دسته است و شمارشِ نظر/واکنش و نشانِ نظرسنجی و زمانِ نسبی در پایین کارت می‌آید. کلیک روی هر کارت، همان فید را در تایم‌لاین باز می‌کند. دکمه‌های چپ/راست فقط در تایم‌لاین ظاهر می‌شوند.'],
    ];

    $poll = [
        ['icon' => 'check_circle', 'color' => 'primary', 'label' => 'تک‌انتخابی: رأی جایگزین', 'text' => 'در حالت تک‌انتخابی، رأی جدید رأی قبلی را جایگزین می‌کند. کلیک روی همان گزینهٔ قبلی، رأی را پس می‌گیرد.'],
        ['icon' => 'add_circle', 'color' => 'secondary', 'label' => 'چندانتخابی: toggle مستقل', 'text' => 'در حالت چندانتخابی، هر گزینه مستقل toggle می‌شود — می‌توانید چند گزینه را همزمان انتخاب یا لغو کنید. نشان «چندانتخابی» بالای گزینه‌ها ظاهر می‌شود.'],
        ['icon' => 'how_to_vote', 'color' => 'tertiary', 'label' => 'درصد و رأی من', 'text' => 'درصد فقط وقتی رأی ثبت شده باشد نمایش داده می‌شود. گزینهٔ انتخابی شما با check_circle نشان داده می‌شود و «رأی شما ثبت شد» ظاهر می‌گردد.'],
        ['icon' => 'forum', 'color' => 'error', 'label' => 'نظر و واکنشِ نظرسنجی', 'text' => 'وقتی دسته فید «نظرسنجی» باشد، بخش نظر و واکنش فقط در صورتی ظاهر می‌شود که در تنظیمات نظرسنجی فعال باشند — در غیر این صورت فقط گزینه‌های رأی نمایش داده می‌شوند.'],
    ];

    $comments = [
        ['icon' => 'forum', 'color' => 'primary', 'label' => 'بارگذاری در اولین باز کردن', 'text' => 'بخش نظرات در اولین کلیک روی «نظرات» بارگذاری می‌شود، نه برای همهٔ فیدها. پس از باز کردن، نظرات و پاسخ‌ها آماده‌اند.'],
        ['icon' => 'subdirectory_arrow_left', 'color' => 'tertiary', 'label' => 'پاسخ‌های زنجیره‌ای', 'text' => 'پاسخ‌ها زیر نظر والد به‌صورت زنجیره‌ای نمایش داده می‌شوند و با دکمهٔ «N پاسخ» باز/بسته می‌شوند.'],
        ['icon' => 'edit', 'color' => 'secondary', 'label' => 'ویرایش و حذف فقط نظرات خودتان', 'text' => 'تنها نویسنده می‌تواند نظرش را ویرایش یا حذف کند. حذف یک نظر، پاسخ‌های آن را به نظر بالاتر منتقل می‌کند — پاسخ‌ها پاک نمی‌شوند.'],
        ['icon' => 'edit_note', 'color' => 'primary', 'label' => 'میان‌برهای نوشتن', 'text' => 'Enter نظر را ارسال می‌کند، Shift+Enter خط جدید می‌گذارد، **متن** پررنگ می‌شود. با دکمهٔ ایموجی، ایموجی در نظر درج می‌شود.'],
    ];

    $notes = [
        'هر کاربر فقط یک واکنش برای هر فید دارد: کلیک روی ایموجیِ جدید واکنش قبلی را جایگزین می‌کند و کلیک روی همان ایموجی آن را پس می‌گیرد. نوار ایموجی صفحه‌بندی است و با دکمهٔ کناری چرخیده می‌شود.',
        'با رفتن نشانگر روی هر ایموجیِ واکنش، عنوانِ نام کاربرانی که واکنش داده‌اند نمایش داده می‌شود.',
        'جستجوی متن و فیلتر دسته، صفحه‌بندی تایم‌لاین را غیرفعال می‌کند و همهٔ فیدهای منطبق را یک‌جا می‌آورد — برای خروج از این حالت «پاک کردن فیلتر» را بزنید.',
        'فیدهای دستهٔ «نظرسنجی» بدون بخش نظر/واکنشِ جداگانه هستند مگر آنکه تنظیمات نظرسنجی آن‌ها را فعال کرده باشد.',
    ];
@endphp

<div x-data="{ tab: 'timeline' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
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

    @foreach(['timeline' => $timeline, 'poll' => $poll, 'comments' => $comments] as $tabId => $rows)
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