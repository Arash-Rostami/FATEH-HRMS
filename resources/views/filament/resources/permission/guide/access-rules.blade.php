@php
    $tabMap = [
        ['tab' => 'تیکت‌ها', 'module' => 'ths'],
        ['tab' => 'پیشنهادها', 'module' => 'suggestion'],
        ['tab' => 'تسک‌ها', 'module' => 'task'],
        ['tab' => 'گزارش‌ها', 'module' => 'report'],
        ['tab' => 'اعتبارنامه‌ها', 'module' => 'credential'],
        ['tab' => 'تست‌های انرژی', 'module' => 'energy_test'],
        ['tab' => 'پروفایل', 'module' => 'profile'],
        ['tab' => 'رزروها', 'module' => 'reservation'],
        ['tab' => 'مهارت‌ها', 'module' => 'skill_request'],
        ['tab' => 'کاربران (داخل کانال و دپارتمان)', 'module' => 'user'],
        ['tab' => 'دسترسی‌ها', 'module' => 'فقط مدیر ارشد / developer'],
    ];
    $gates = [
        [
            'icon' => 'login',
            'label' => 'دروازهٔ ۱ — ورود به پنل (بر اساس نقش)',
            'hint' => 'user هرگز وارد نمی‌شود. admin فقط وقتی وارد می‌شود که ردیف دسترسی داشته باشد و آن ردیف مدیر ارشد باشد یا دست‌کم یک ability داشته باشد. developer همیشه وارد می‌شود، بدون ردیف.',
        ],
        [
            'icon' => 'rule',
            'label' => 'دروازهٔ ۲ — ماژول × عملیات',
            'hint' => 'کلید module همان نام ماژول است و عملیات یکی از view / create / update / delete / restore. مدیر ارشد همه‌چیز را دارد مگر ماژول‌های excluded_modules؛ مدیر عادی فقط ترکیب‌های ثبت‌شده در abilities را. developer از هر دو دروازه بدون ردیف و بدون استثنا عبور می‌کند.',
        ],
    ];
    $governs = [
        ['icon' => 'door_open', 'label' => 'ورود به صفحه و منوی کناری', 'hint' => 'بدون «مشاهدهٔ» ماژول، نه منو دیده می‌شود و نه آدرس مستقیم باز می‌شود.'],
        ['icon' => 'search', 'label' => 'جستجوی سراسری', 'hint' => 'نتیجه‌های ماژولی که نمی‌توانید مشاهده کنید نمایش داده نمی‌شود.'],
        ['icon' => 'smart_button', 'label' => 'دکمه‌های داخل جدول و هدر', 'hint' => 'ویرایش و حذف ردیف، حذف گروهی و دکمه‌های هدر: دکمه‌ای که اجازه‌اش را ندارید مخفی می‌شود و اگر درخواستش دستی هم فرستاده شود، سرور آن را رد می‌کند.'],
        ['icon' => 'download', 'label' => 'خروجی اکسل — استثنا', 'hint' => 'خروجی اکسل جداگانه قفل نیست؛ هر کس بتواند ماژول را ببیند می‌تواند از ردیف‌های انتخاب‌شده خروجی بگیرد.'],
    ];
    $permRules = [
        ['icon' => 'admin_panel_settings', 'label' => 'فقط مدیر ارشد و developer', 'hint' => 'یک مدیر عادی حتی اگر ماژول «دسترسی‌ها» به‌نحوی در ردیفش باشد نمی‌تواند این صفحه را باز کند.'],
        ['icon' => 'person_remove', 'label' => 'حذفِ ردیفِ خود ممکن نیست', 'hint' => 'هیچ‌کس — حتی developer یا مدیر ارشد — نمی‌تواند ردیف دسترسی خودش را حذف کند: نه از جدول، نه هدر صفحهٔ ویرایش، نه زیرِ صفحهٔ کاربر و نه با حذف گروهی.'],
        ['icon' => 'block', 'label' => 'ماژول «دسترسی‌ها» قابل اعطا نیست', 'hint' => 'این ماژول در انتخابگرهای abilities و excluded_modules نمایش داده نمی‌شود، پس نه می‌توان آن را به مدیری داد و نه استثنا کرد.'],
    ];
    $open = [
        ['icon' => 'folder_shared', 'label' => 'DMS: خوانندگان', 'hint' => 'زبانهٔ خوانندگان رکورد DMS.'],
        ['icon' => 'dynamic_feed', 'label' => 'فید: نظرات، نظرسنجی‌ها و واکنش‌ها', 'hint' => 'سه زبانهٔ زیرِ رکورد فید.'],
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
        'دسترسی در سطح فیلد وجود ندارد — اگر ماژولی را ویرایش‌کردنی بدهید، همهٔ فیلدهایش قابل ویرایش است.',
        'خروجی اکسل جدا گیت نمی‌شود (بالا را ببینید).',
        'از آخرین مدیر ارشد محافظتی نمی‌شود — اگر ردیف او (توسط مدیر ارشدِ دیگر یا developer) حذف یا محدود شود، سیستم مانع نمی‌شود.',
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
            زیرِ صفحهٔ ویرایش (کاربر، کانال، دپارتمان) هر زبانه‌ای که برای خودش ماژول جدا دارد، فقط وقتی دیده می‌شود که «مشاهدهٔ» همان ماژول را داشته باشید. دکمه‌های ساخت/ویرایش/حذفِ آن زبانه هم از همان ماژول پیروی می‌کنند. خودِ صفحه هم بالای محتوا به‌طور خودکار یادآوری می‌کند کدام زبانه‌ها به‌دلیل سطح دسترسی شما پنهان شده‌اند و هر کدام کدام ماژول را می‌خواهد.
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
                دکمه‌های افزودن/جداکردنِ عضو (Attach / Detach / Associate / Dissociate) از دسترسی «ویرایش» ماژولِ صاحبِ صفحه پیروی می‌کنند، نه ماژول زبانه.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_1</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">ترتیب دروازه‌ها</p>
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
            این زبانه‌ها ماژول مستقل ندارند و فقط داخل رکورد اصلی وجود دارند. هر کس بتواند رکورد اصلی را باز کند آن‌ها را می‌بیند و در صفحهٔ ویرایش می‌تواند مدیریتشان کند — مجوز اضافه‌ای لازم نیست (تصمیم آگاهانه).
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
