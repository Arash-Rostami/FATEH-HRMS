@php
    $tabMap = [
        ['tab' => 'تیکت‌ها', 'module' => 'ths'],
        ['tab' => 'پیشنهادها', 'module' => 'suggestion'],
        ['tab' => 'تسک‌ها', 'module' => 'task'],
        ['tab' => 'گزارش‌ها', 'module' => 'report'],
        ['tab' => 'اطلاعات ورود', 'module' => 'credential'],
        ['tab' => 'تست‌های انرژی', 'module' => 'energy_test'],
        ['tab' => 'پروفایل', 'module' => 'profile'],
        ['tab' => 'رزروها', 'module' => 'reservation'],
        ['tab' => 'مهارت‌ها', 'module' => 'skill_request'],
        ['tab' => 'کاربران (داخل کانال و واحد)', 'module' => 'user'],
        ['tab' => 'دسترسی‌ها', 'module' => 'فقط مدیر ارشد و توسعه‌دهنده'],
    ];
    $gates = [
        [
            'icon' => 'login',
            'label' => 'مرحلهٔ ۱ — ورود به پنل (بر اساس نقش)',
            'hint' => 'کاربر عادی هرگز وارد نمی‌شود. ادمین فقط وقتی وارد می‌شود که دسترسی‌اش تعریف شده باشد و یا مدیر ارشد باشد یا دست‌کم یک ماژول مجاز داشته باشد. توسعه‌دهنده همیشه وارد می‌شود، بدون نیاز به تعریف دسترسی.',
        ],
        [
            'icon' => 'rule',
            'label' => 'مرحلهٔ ۲ — ماژول و کار مجاز',
            'hint' => 'هر کار مجاز یکی از این‌هاست: مشاهده، ایجاد، ویرایش، حذف یا بازیابی. مدیر ارشد همه‌چیز را دارد مگر ماژول‌های محدودشده؛ مدیر عادی فقط ماژول‌ها و کارهای انتخاب‌شده را. توسعه‌دهنده از هر دو مرحله بدون تعریف دسترسی و بدون محدودیت عبور می‌کند.',
        ],
    ];
    $governs = [
        ['icon' => 'door_open', 'label' => 'ورود به صفحه و منوی کناری', 'hint' => 'بدون «مشاهدهٔ» ماژول، نه منو دیده می‌شود و نه آدرس مستقیم باز می‌شود.'],
        ['icon' => 'search', 'label' => 'جستجوی سراسری', 'hint' => 'نتیجه‌های ماژولی که نمی‌توانید مشاهده کنید نمایش داده نمی‌شود.'],
        ['icon' => 'smart_button', 'label' => 'دکمه‌های داخل جدول و هدر', 'hint' => 'ویرایش و حذف هر مورد، حذف گروهی و دکمه‌های بالای صفحه: دکمه‌ای که اجازه‌اش را ندارید مخفی می‌شود و اگر درخواستش دستی هم فرستاده شود، سرور آن را رد می‌کند.'],
        ['icon' => 'download', 'label' => 'خروجی اکسل — بدون قفل جداگانه', 'hint' => 'خروجی اکسل جداگانه قفل نیست؛ هر کس بتواند ماژول را ببیند می‌تواند از موردهای انتخاب‌شده خروجی بگیرد.'],
    ];
    $permRules = [
        ['icon' => 'admin_panel_settings', 'label' => 'فقط مدیر ارشد و developer', 'hint' => 'یک مدیر عادی حتی اگر ماژول «دسترسی‌ها» به‌نحوی در ردیفش باشد نمی‌تواند این صفحه را باز کند.'],
        ['icon' => 'person_remove', 'label' => 'حذف دسترسی خودتان ممکن نیست', 'hint' => 'هیچ‌کس، حتی توسعه‌دهنده یا مدیر ارشد، نمی‌تواند دسترسی خودش را حذف کند: نه از جدول، نه از بالای صفحهٔ ویرایش، نه از صفحهٔ کاربر و نه با حذف گروهی.'],
        ['icon' => 'block', 'label' => 'ماژول «دسترسی‌ها» قابل اعطا نیست', 'hint' => 'این ماژول در انتخابگرهای abilities و excluded_modules نمایش داده نمی‌شود، پس نه می‌توان آن را به مدیری داد و نه استثنا کرد.'],
    ];
    $open = [
        ['icon' => 'folder_shared', 'label' => 'DMS: خوانندگان', 'hint' => 'زبانهٔ خوانندگان هر مورد DMS.'],
        ['icon' => 'dynamic_feed', 'label' => 'فید: نظرات، نظرسنجی‌ها و واکنش‌ها', 'hint' => 'سه زبانهٔ زیرِ هر مورد فید.'],
        ['icon' => 'lightbulb', 'label' => 'پیشنهاد: بازبینی‌ها', 'hint' => 'زبانهٔ بازبینی‌های یک پیشنهاد.'],
        ['icon' => 'forum', 'label' => 'کانال: پیام‌ها', 'hint' => 'زبانهٔ پیام‌های یک کانال.'],
        ['icon' => 'conversion_path', 'label' => 'پروژه و کاربر: چرخه‌های کاری', 'hint' => 'زبانهٔ چرخه‌های کاری زیرِ پروژه و زیرِ کاربر.'],
        ['icon' => 'event', 'label' => 'رویداد: اشتراک‌ها', 'hint' => 'زبانهٔ اشتراک‌های یک رویداد.'],
    ];
    $examples = [
        ['who' => 'فقط user:view', 'what' => 'کاربر را می‌بیند، اما زبانه‌های تیکت‌ها، تسک‌ها و پیشنهادها را نه (آن‌ها ماژول خودشان را می‌خواهند).'],
        ['who' => 'dms:view', 'what' => 'DMS و زبانهٔ خوانندگان را می‌بیند، ولی هیچ دکمهٔ ساخت، ویرایش یا حذفی ندارد.'],
        ['who' => 'feed:update', 'what' => 'در صفحهٔ ویرایش فید می‌تواند نظرسنجی اضافه یا حذف و نظرها را حذف کند.'],
        ['who' => 'task:update ولی بدون user', 'what' => 'به کاربران راه ندارد؛ تسک‌ها را از خودِ ماژول تسک مدیریت می‌کند.'],
        ['who' => 'user:view و task:view', 'what' => 'زبانهٔ تسک‌ها زیرِ کاربر را می‌بیند؛ دکمه‌های ساخت/ویرایش/حذف آن از task:create / update / delete پیروی می‌کنند.'],
        ['who' => 'channel:view و user:view بدون channel:update', 'what' => 'زبانهٔ اعضا را می‌بیند، ولی دکمه‌های افزودن/جداکردنِ عضو را نه (این دکمه‌ها از ویرایشِ ماژول صاحبِ صفحه، یعنی کانال، پیروی می‌کنند).'],
    ];
    $notEnforced = [
        'دسترسی برای تک‌تک اطلاعات یک ماژول جداگانه تعریف نمی‌شود؛ اگر ویرایش یک ماژول را مجاز کنید، همهٔ اطلاعات آن قابل ویرایش است.',
        'خروجی اکسل جداگانه قفل نمی‌شود (بالا را ببینید).',
        'از آخرین مدیر ارشد محافظت نمی‌شود؛ اگر دسترسی او (توسط مدیر ارشد دیگر یا توسعه‌دهنده) حذف یا محدود شود، سیستم مانع نمی‌شود.',
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">policy</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">یک مجموعه دسترسی دقیقاً چه چیزی را باز یا بسته می‌کند؟</p>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade ring-2 ring-[var(--md-sys-color-primary)]/30">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">help</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">چرا بعضی زبانه‌ها را نمی‌بینم؟</p>
        </div>
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium px-5 pt-4">
            زیرِ صفحهٔ ویرایش (کاربر، کانال، واحد) هر زبانه‌ای که برای خودش ماژول جدا دارد، فقط وقتی دیده می‌شود که «مشاهدهٔ» همان ماژول را داشته باشید. دکمه‌های ساخت/ویرایش/حذفِ آن زبانه هم از همان ماژول پیروی می‌کنند. خودِ صفحه هم بالای محتوا به‌طور خودکار یادآوری می‌کند کدام زبانه‌ها به‌دلیل سطح دسترسی شما پنهان شده‌اند و هر کدام کدام ماژول را می‌خواهد.
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y divide-[var(--md-sys-color-outline-variant)] sm:divide-y-0 mt-2">
            @foreach($tabMap as $m)
                <div class="flex items-center gap-3 px-5 py-2.5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">subdirectory_arrow_left</span>
                    <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $m['tab'] }}</p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold font-mono bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">{{ $m['module'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)] mt-2">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                دکمه‌های افزودن/جداکردنِ عضو از دسترسی «ویرایش» ماژولِ صاحبِ صفحه پیروی می‌کنند، نه ماژول زبانه.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_1</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">ترتیب مرحله‌ها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($gates as $g)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $g['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">gpp_good</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">این بررسی چه چیزهایی را کنترل می‌کند؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($governs as $g)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">{{ $g['icon'] }}</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                تغییر یک ردیف دسترسی بلافاصله اثر می‌کند؛ لازم نیست کاربر خارج و دوباره وارد شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">shield</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">خودِ ماژول «دسترسی‌ها»</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($permRules as $p)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">{{ $p['icon'] }}</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $p['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">lock_open</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های عمداً باز</p>
        </div>
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium px-5 pt-4">
            این زبانه‌ها ماژول مستقل ندارند و فقط داخل مورد اصلی وجود دارند. هر کس بتواند مورد اصلی را باز کند آن‌ها را می‌بیند و در صفحهٔ ویرایش می‌تواند مدیریتشان کند — دسترسی اضافه‌ای لازم نیست (تصمیمی آگاهانه است).
        </p>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)] mt-2">
            @foreach($open as $o)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">{{ $o['icon'] }}</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $o['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $o['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-secondary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-secondary-container)]">checklist</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-secondary-container)]">مثال‌های عملی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($examples as $e)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold font-mono bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">{{ $e['who'] }}</span>
                    <p class="flex-1 text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $e['what'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-error-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-error-container)]">report</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-error-container)]">چه چیزهایی اجرا نمی‌شود؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($notEnforced as $n)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-error)] mt-0.5">close</span>
                    <p class="flex-1 text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $n }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
