@php
    $pills = [
        ['id' => 'format', 'icon' => 'folder_open', 'label' => 'قالب فایل‌ها'],
        ['id' => 'sharing', 'icon' => 'share', 'label' => 'اشتراک و زمان‌بندی'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $formatRows = [
        ['icon' => 'login', 'color' => 'primary', 'label' => 'نقطهٔ ورود: index.html', 'text' => 'برنامه همیشه با فایل index.html باز می‌شود و نام آن باید دقیقاً همین باشد؛ «Index.html» یا «index.htm» پذیرفته نمی‌شود. بقیهٔ فایل‌ها هر نامی می‌توانند داشته باشند و همان‌طور که در برنامه ارجاع داده‌اید بارگذاری می‌شوند.'],
        ['icon' => 'attach_file', 'color' => 'secondary', 'label' => 'همهٔ فایل‌ها یک‌جا', 'text' => 'فایل‌ها را یک‌بار و با هم انتخاب کنید؛ ساختار باید تخت باشد (همه کنار index.html، بدون زیرپوشه) و فایل‌های داخل پوشه‌های تو در تو در دسترس نیستند. هر انتخاب تازه، انتخاب قبلی را به‌طور کامل جایگزین می‌شود؛ برای افزودن فایل، همه را از نو انتخاب کنید.'],
        ['icon' => 'rule', 'color' => 'tertiary', 'label' => 'انواع مجاز', 'text' => 'فقط html، css، js، json، txt، تصویر و فونت. فایل اجرایی یا PHP هرگز پذیرفته نمی‌شود — حتی با پسوند عوض‌شده، چون محتوای ابتدای فایل هم بررسی می‌شود. یک فایل نامعتبر، کل انتخاب را از کار می‌اندازد.'],
        ['icon' => 'sd_card_alert', 'color' => 'error', 'label' => 'سقف‌ها', 'text' => 'سقف دقیق (تعداد فایل، حجم هر فایل و مجموع) زیر دکمهٔ انتخاب فایل‌ها نوشته می‌شود. انتخاب بیش از سقفِ تعداد، در همان مرورگر پاک و اعلان می‌شود و چیزی بارگذاری نمی‌شود؛ حجم‌ها هنگام انتخاب شمرده می‌شوند و هنگام ذخیره هم دوباره بررسی می‌شوند.'],
        ['icon' => 'abc', 'color' => 'primary', 'label' => 'نام فایل‌ها', 'text' => 'نام فارسی یا دارای فاصله مشکلی ندارد. یک نام فقط یک‌بار در هر انتخاب می‌تواند باشد (بزرگی و کوچکی حروف مهم نیست؛ a.html و A.HTML یک نام شمرده می‌شوند) و نام بیش از ۱۲۰ کاراکتر پذیرفته نمی‌شود. روی سرور نام‌ها بُرش می‌خورند ولی برنامه با نام‌های اصلی خود کار می‌کند.'],
        ['icon' => 'fact_check', 'color' => 'secondary', 'label' => 'بررسی پیش از ذخیره', 'text' => 'بلافاصله پس از انتخاب، وضعیت مجموعه بررسی و دلیل رد شدن هر فایل جداگانه نمایش داده می‌شود؛ تا فهرست سبز نشود، دکمهٔ ذخیره چیزی ثبت نمی‌کند.'],
    ];

    $sharingRows = [
        ['icon' => 'group_add', 'color' => 'primary', 'label' => 'مخاطبان', 'text' => 'بدون انتخاب مخاطب، برنامه فقط برای شما و مدیران دیده می‌شود. با «افزودن اعضای بخش» همهٔ همکاران فعال همان بخش یک‌جا اضافه می‌شوند؛ مخاطب فقط از کاربران فعال انتخاب می‌شود و بعد از ساخت قابل ویرایش نیست؛ برای تغییر مخاطبان، برنامه را حذف و دوباره بسازید (شناسهٔ تازه می‌گیرد و باید دوباره به فضای کاری افزوده شود).'],
        ['icon' => 'event_available', 'color' => 'secondary', 'label' => 'شروع و پایان دسترسی', 'text' => 'بدون تاریخ، برنامه بلافاصله برای مخاطبان باز است. با تاریخ شروع، برنامه تا آن روز «زمان‌بندی‌شده» می‌ماند و باز نمی‌شود؛ با تاریخ پایان، روزِ بعد از آن برنامه باز نمی‌شود. تاریخ‌ها شمسی‌اند و روزِ آخر خودش جزو بازه است.'],
        ['icon' => 'event_busy', 'color' => 'error', 'label' => 'تاریخ انقضا', 'text' => 'برنامهٔ منقضی‌شده از فهرست مخاطبان خارج و به‌صورت خودکار پاک می‌شود (فایل‌هایش هم حذف می‌شوند). هفت روز پایانی، وضعیت برنامه با شمارش روزهای باقی‌مانده زرد نشان داده می‌شود.'],
        ['icon' => 'swap_horiz', 'color' => 'tertiary', 'label' => 'عنوان تکراری = جایگزینی', 'text' => 'برنامه قابل ویرایش نیست؛ برای عوض‌کردن محتوای برنامه، برنامهٔ تازه‌ای با همان عنوان در همان بخش بسازید تا جایگزین قبلی شود (شناسه، مخاطبان و دیده‌شدن‌ها می‌مانند و فقط محتوای برنامه — فایل‌ها یا پیوند — عوض می‌شود). جایگزینی فقط برای مالک یا مدیر ممکن است؛ عنوان تکراریِ مالک دیگری رد می‌شود.'],
        ['icon' => 'deployed_code', 'color' => 'primary', 'label' => 'سهمیهٔ بخش', 'text' => 'هر بخش سهمیهٔ مشخصی برای تعداد برنامه‌ها دارد که بالای فهرست نشان داده می‌شود؛ برنامه‌های غیرفعال هم سهمیه می‌گیرند و فقط برنامه‌های حذف‌شده آزاد می‌شوند. جایگزینی سهمیهٔ تازه نمی‌گیرد.'],
        ['icon' => 'delete', 'color' => 'secondary', 'label' => 'حذف', 'text' => 'فقط مالک یا مدیر می‌تواند برنامه را حذف کند؛ فایل‌های آن هم پاک می‌شوند و عنوانش آزاد می‌شود — ساخت دوباره با همان عنوان، برنامه‌ای با شناسهٔ تازه می‌سازد.'],
    ];

    $notes = [
        'دو نوع برنامه وجود دارد: «بارگذاری فایل» (مجموعهٔ تخت فایل‌های وب) و «پیوند به سامانه» (میان‌بر به یکی از سامانه‌های سازمان؛ در فرم با کلید «فقط پیوند به سامانهٔ دیگر» انتخاب می‌شود و فقط وقتی دیده می‌شود که برای سازمان سامانهٔ خواهر تعریف شده باشد). در نوع پیوند، سایت فقط از فهرست سایت‌های ثبت‌شده انتخاب می‌شود و آدرس هرگز دستی وارد نمی‌شود؛ مسیر اختیاری است (خالی = صفحهٔ اصلی سایت) و بازکردن برنامه در زبانهٔ تازه انجام می‌شود و نقطهٔ قرمز را مانند برنامهٔ فایلی خاموش می‌کند.',
        '«افزودن به فضای کاری» فقط برای برنامه‌های فعال روشن است؛ برنامهٔ زمان‌بندی‌شده، منقضی‌شده یا غیرفعال دکمهٔ خاموش دارد. برنامهٔ افزوده‌شده در کاشی‌های خانه با حساب خود شما باز می‌شود و دسترسی هر بار دوباره بررسی می‌شود.',
        'نقطهٔ قرمز کنار «پیشخوان برنامه‌ها» در منو یعنی برنامه‌ای با شما به اشتراک گذاشته شده و هنوز باز نکرده‌اید؛ با بازکردن همان برنامه خاموش می‌شود و برای سازندهٔ برنامه هیچ‌وقت روشن نمی‌شود.',
        'برنامه در قاب ایزوله اجرا می‌شود؛ هیچ کوکی، نشست یا دادهٔ دیگری از سامانه در دسترس آن نیست و نمی‌تواند از مرز برنامه بیرون برود؛ بنابراین localStorage، فرم‌ها، پنجره‌های alert و بازشدن پیوند در زبانهٔ تازه هم در آن کار نمی‌کند و eval هم بسته است.',
        'پیوند فایل‌ها در index.html باید نسبی و بدون اسلش ابتدایی باشد (style.css نه /style.css). پیوند به اینترنت (فونت، اسکریپت یا تصویر از CDN) بسته است؛ همهٔ فایل‌های لازم را همراه برنامه بارگذاری کنید.',
        'برنامه‌ای که فقط پنجرهٔ دسترسی آن تمام شده، «منقضی» نمایش داده می‌شود اما پاک نمی‌شود و تا حذف دستی سهمیهٔ بخش را اشغال می‌کند؛ فقط تاریخ انقضا آن را خودکار پاک می‌کند.',
        'فهرست، عنوان‌ها را با فیلتر «همه / برنامه‌های من / اشتراک با من / بخش من» و جستجوی عنوان جدا می‌کند؛ برنامه‌های غیرفعال و منقضی‌شدهٔ دیگران برای مخاطب در فهرست نمی‌آیند. بالای جدول می‌توانید ترتیب (عنوان یا زمان ساخت) را عوض کنید، ستون‌ها را پنهان یا نمایان کنید و نمایش فشرده را روشن کنید؛ انتخاب ستون‌ها و نمایش فشرده در همین مرورگر به یاد می‌ماند (ترتیب با بارگذاری دوبارهٔ صفحه به حالت پیش‌فرض برمی‌گردد).',
        'بازکردن برنامه از روی کاشی فضای کاری یا پیوند مستقیم هم همین صفحه را با همان برنامه باز می‌کند؛ اگر برنامه در دسترس نباشد، پیام روشن نمایش داده می‌شود.',
    ];
@endphp

<div x-data="{ tab: 'format' }">
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

    <div x-show="tab === 'format'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">برنامه از نوع «بارگذاری فایل» یک مجموعهٔ تخت از فایل‌های وب است که یک‌جا بارگذاری می‌شود؛ قواعد این تب دربارهٔ همین نوع است.</p>
        @foreach($formatRows as $s)
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

    <div x-show="tab === 'sharing'" x-cloak class="space-y-3">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">هر برنامه مالک، مخاطبان و پنجرهٔ دسترسی خود را دارد و بعد از ساخت قابل ویرایش نیست.</p>
        @foreach($sharingRows as $s)
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

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @foreach($notes as $note)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
            </div>
        @endforeach
    </div>
</div>
