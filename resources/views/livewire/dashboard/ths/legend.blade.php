@php
    $pills = [
        ['id' => 'roles', 'icon' => 'group', 'label' => 'نقش‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];

    $rows = [
        'requester' => [
            'note' => 'شما این تیکت را ثبت کرده‌اید و پیگیر آن هستید.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'در تب «تاریخچه»، حالت پیش‌فرض («تیکت‌های من») فقط تیکت‌های خودتان را نشان می‌دهد.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'تا زمانی که تیکت باز است می‌توانید در «پاسخ و پیگیری» پیام بدهید؛ پس از بسته‌شدن، در تب «ارزیابی» به آن امتیاز رضایت بدهید. تا قبل از بسته‌شدن می‌توانید تیکت را «لغو درخواست» بزنید؛ تیکتِ لغوشده بسته می‌شود و امتیاز نمی‌خواهد.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'زنگوله با هر پاسخ تازه یا بسته‌شدن تیکت روشن می‌شود؛ نشان کارتابل برای شما فعال نمی‌شود چون اقدامی از سمت شما لازم نیست.'],
        ],
        'head' => [
            'note' => 'تیکت به واحد شما ارجاع شده یا در حال بررسیِ زیرمجموعه شماست.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'در تب «تاریخچه»، دکمهٔ «نیاز به اقدام من» را روشن کنید تا فهرست به تیکت‌های بازِ بدون‌مسئولِ واحدتان و تیکت‌های در حال بررسیِ محول‌شده به شما محدود شود؛ با دکمهٔ «تیکت‌های واحد من» همهٔ تیکت‌های واحدتان را در هر وضعیتی (باز، در حال بررسی، بسته‌شده) می‌بینید. اگر دو نفر هم‌رتبه باشند، هر دو مدیر واحد حساب می‌شوند.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'تیکت را باز کنید و از «پاسخ و پیگیری» یک مسئول رسیدگی تعیین کنید یا خودتان پاسخ دهید؛ به‌عنوان مدیر واحد می‌توانید تیکت را با «ارجاع به واحد دیگر» به واحد دیگری بفرستید (با توضیح؛ تیکت دوباره باز و بدون‌مسئول می‌شود) و امتیاز «اثربخشی» را هم شما ثبت کنید (پیش‌نیاز بستن تیکت).'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'نشان کارتابل تا وقتی تیکتی از واحدتان بدون مسئول باز بماند یا خودتان مسئول رسیدگیِ تیکتی در حال بررسی باشید، روشن می‌ماند؛ زنگوله وقتی خودتان درخواست‌دهنده یا مسئول رسیدگی باشید با پاسخ‌های تازه فعال می‌شود.'],
        ],
        'assignee' => [
            'note' => 'تیکت به شما محول شده و در حال بررسی است.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'همان دکمهٔ «نیاز به اقدام من» در تب «تاریخچه»، تیکت‌های محول‌شده به شما را نشان می‌دهد.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'در «پاسخ و پیگیری» به درخواست‌دهنده پاسخ دهید و امتیاز «اثربخشی» را ثبت کنید؛ بدون این امتیاز تیکت بسته نمی‌شود. اگر نمی‌توانید رسیدگی کنید، با «بازگشت به صف» (با توضیح) تیکت را رها کنید تا دوباره بدون‌مسئول شود.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'نشان کارتابل تا وقتی تیکتِ محول‌شده به شما بسته نشده روشن می‌ماند؛ زنگوله با هر پاسخ تازهٔ درخواست‌دهنده.'],
        ],
    ];

    $noteGroups = [
        'display' => [
            'label' => 'نمایش و شناسه',
            'icon' => 'visibility',
            'notes' => [
                'شناسهٔ هر تیکت به‌صورت «پیشوند-سال‌ماه-شماره» است؛ پیشوند برابر کدِ واحد هدف است (مثلاً TN-۲۶۰۸-۰۰۰۱) و با انتخاب «پشتیبانی عمومی» پیشوند T می‌شود.',
                'کنار شناسهٔ تیکت در بالای پنجرهٔ جزئیات، دکمهٔ کپی همان شناسه را در کلیپ‌بورد شما قرار می‌دهد.',
                'در بالای پنجرهٔ جزئیات تیکت، یک نوار سه‌مرحله‌ای (باز ← در حال بررسی ← بسته‌شده) جایگاه فعلی تیکت را نشان می‌دهد؛ مرحله‌های طی‌شده پررنگ، مرحلهٔ جاری با حلقهٔ رنگی مشخص است.',
                'اگر برای تیکت مهلت انجام تعیین شده باشد، زیر نشان وضعیت در فهرست یک چیپ می‌بینید: تا وقتی تیکت باز است شمار روز/ساعت تا سررسید (یا گذشته از آن)، و پس از بسته‌شدن، نتیجهٔ نهایی «پیش از موعد» یا «با تأخیر».',
                'زیر نشان وضعیت در فهرست، اگر تیکت پاسخی داشته باشد شمار پاسخ‌ها و اگر فایلی پیوست شده باشد شمار پیوست‌ها را هم می‌بینید — بدون باز کردن تیکت.',
            ],
        ],
        'workflow' => [
            'label' => 'گردش کار',
            'icon' => 'bolt',
            'notes' => [
                'تیکتِ بسته‌شده قطعی است: گفتگوی آن فقط قابل‌مطالعه است و نه دوباره باز می‌شود و نه مسئولش عوض می‌شود.',
                'وظیفه‌های تب «وظیفه‌ها» هم همین گفتگوی دوطرفه را دارند: فقط ایجادکننده و مسئول وظیفه می‌توانند در آن پیام بدهند.',
                'به‌محض تعیین مسئول رسیدگی، یک وظیفهٔ پیگیری متناظر هم در «وظیفه‌ها» برای درخواست‌دهنده و مسئول رسیدگی ساخته می‌شود (از ستون «انجام نشده» شروع می‌کند)؛ گفتگو همچنان فقط از طریق همین تیکت انجام می‌شود. اگر مسئول رها یا تیکت ارجاع شود، وظیفهٔ پیگیری هم برداشته می‌شود.',
                'هنگام بسته‌شدن تیکت، فیلد «نتیجه اقدام» به‌صورت خودکار از روی گفتگو ساخته و فقط‌خواندنی می‌شود؛ در پنل کاربر این فیلد دیده نمی‌شود ولی در خروجی اکسلِ ادمین هست.',
                'در بخش «گفتگو»، هنگام باز کردن تیکت یا پس از ارسال پاسخ تازه، فهرست خودکار به آخرین پیام اسکرول می‌شود؛ دکمهٔ «ارسال پاسخ» هم تا وقتی متنی ننوشته‌اید یا فایلی پیوست نکرده‌اید غیرفعال است.',
                'گزینهٔ «پشتیبانی عمومی» در فرم ثبت تیکت به‌محض این‌که حداقل یک واحد حوزه‌های درخواست اختصاصی خودش را تنظیم کند، خودکار حذف می‌شود.',
            ],
        ],
        'personal' => [
            'label' => 'شخصی‌سازی',
            'icon' => 'tune',
            'notes' => [
                'با بردن نشانگر روی هر ردیف تیکت، گوشهٔ چپ ستون «اقدام» یک دکمهٔ «پالت» ظاهر می‌شود (در موبایل همیشه نمایان است)؛ با آن می‌توانید تیکت را رنگ‌آمیزی کنید تا کل پس‌زمینهٔ ردیف همان رنگ را بگیرد و در فهرست سریع‌تر پیدا شود (مثل کارت‌های تسک‌بورد). رنگ انتخابی فقط در مرورگر خودتان ذخیره می‌شود و روی دادهٔ تیکت اثری ندارد.',
                'کنار همان دکمهٔ پالت، یک آیکون زنگ یادآوری هم هست که فقط با نگه‌داشتن نشانگر روی ردیف نمایان می‌شود (در موبایل همیشه نمایان است)؛ اگر برای آن تیکت یادآوری تنظیم کرده باشید، همیشه با رنگ اصلی برنامه نمایان می‌ماند و با کلیک، همان یادآوری موجود برای ویرایش باز می‌شود.',
                'از منوی view_column در سرستون «اقدام» می‌توانید ستون‌های وضعیت، حوزه درخواست، درخواست‌دهنده و مسئول را مخفی کنید یا برگردانید؛ انتخاب شما به‌خاطر سپرده می‌شود و ستون‌های اصلی (شناسه/تاریخ و موضوع) همیشه می‌مانند.',
                'کنار همان منو، دکمهٔ فشرده‌سازی فاصلهٔ ردیف‌های جدول را کم می‌کند تا تیکت‌های بیشتری هم‌زمان دیده شوند؛ این حالت هم برای شما ذخیره می‌شود. دکمهٔ پیکان‌های افقی آخر همین سطر، همهٔ ابزارهای سرستون را جمع می‌کند و یک دکمهٔ روشن برای برگرداندنشان می‌ماند («حالت خلوت» منوی همبرگری هم آن را جمع می‌کند). جستجوی تیکت‌ها از دکمهٔ شناور پایین-چپ انجام می‌شود که با جمع‌شدن ابزارها، در دسکتاپ و موبایل، پنهان می‌شود و با برگرداندنشان دوباره می‌آید. در حالت «بزرگ کردن» فقط خود جدول بزرگ می‌شود و دکمهٔ «بارگذاری بیشتر» دیده نمی‌شود؛ برای دیدن ردیف‌های بیشتر ابتدا از حالت بزرگ‌شده خارج شوید. در فهرست «تیکت‌های من» می‌توانید با دکمهٔ مرتب‌سازی یا با زدن روی «شناسه / تاریخ» و «موضوع»، فهرست را بر اساس تاریخ ثبت، موضوع، وضعیت یا اولویت مرتب کنید.',
                'تیکت‌های دارای مهلت که نزدیک سررسید یا از آن گذشته‌اند، در فهرست با نوار رنگی فوریت در لبهٔ ردیف مشخص می‌شوند (همان مکانیزم تسک‌بورد)؛ با نگه‌داشتن نشانگر روی ردیف، برچسب وضعیت فوریت («از مهلت گذشته»، «نزدیک سررسید» و…) هم دیده می‌شود.',
                'در پنجرهٔ تیکت، انتخاب مسئول رسیدگی با آواتار واقعی کاربران انجام می‌شود، نه یک فهرست متنی ساده — نقطهٔ بالا-چپ آواتار آنلاین‌بودنِ واقعیِ فرد را نشان می‌دهد (پر یعنی در ۱۰ دقیقهٔ اخیر فعال بوده، توخالی یعنی آفلاین) و نشان پایین-راست، وضعیتِ انتخابیِ او را؛ اگر فرد در «مرخصی» یا «مأموریت» باشد، برچسب «پاسخ‌گو نیست» کنار نامش می‌آید — این فقط هشدار است و تخصیص را مسدود نمی‌کند؛ در گفتگوی تیکت هم پیام‌ها با آواتار فرستنده می‌نشینند و اگر کسی آواتار نداشته باشد، حرف اول نامش جای آن را می‌گیرد.',
            ],
        ],
    ];
@endphp

<div x-data="{ tab: 'roles', role: 'requester', note: 'display' }">
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

    <div x-show="tab === 'roles'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach([
                ['id' => 'requester', 'icon' => 'person', 'label' => 'درخواست‌دهنده'],
                ['id' => 'head', 'icon' => 'shield_person', 'label' => 'مدیر واحد'],
                ['id' => 'assignee', 'icon' => 'engineering', 'label' => 'مسئول رسیدگی'],
            ] as $r)
                <button
                    type="button"
                    @click="role = '{{ $r['id'] }}'"
                    :class="role === '{{ $r['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-2 py-2 rounded-xl text-[12px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $r['icon'] }}</span>
                    {{ $r['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($rows as $roleId => $sections)
            <div x-show="role === '{{ $roleId }}'" x-cloak class="space-y-3">
                <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">{{ $sections['note'] }}</p>
                @foreach(array_slice($sections, 1) as $s)
                    @php
                        $chipClasses = match ($s['color']) {
                            'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                            'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                            'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
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
    </div>

    <div x-show="tab === 'notes'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl">
            @foreach($noteGroups as $key => $group)
                <button
                    type="button"
                    @click="note = '{{ $key }}'"
                    :class="note === '{{ $key }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-2 py-2 rounded-xl text-[12px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $group['icon'] }}</span>
                    {{ $group['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($noteGroups as $key => $group)
            <div x-show="note === '{{ $key }}'" x-cloak class="space-y-2">
                @foreach($group['notes'] as $note)
                    <div class="flex items-start gap-2 px-1">
                        <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                        <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
