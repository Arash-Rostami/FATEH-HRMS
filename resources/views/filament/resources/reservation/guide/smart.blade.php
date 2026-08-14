@php
    $features = [
        [
            'icon' => 'repeat',
            'label' => 'رزرو تکرارشونده (روزانه/هفتگی)',
            'hint' => 'کاربر می‌تواند هنگام رزرو، گزینهٔ «تکراری» را روشن کند. الگو روزانه یا هفتگی است و تعداد تکرار بین ۲ تا ۵۲ (پیش‌فرض ۴). یک رزرو «اصلی» ثبت می‌شود و رزروهای «تکرار» با parent_id به آن گره می‌خورند. هر تکرار به‌صورت جداگانه اعتبارسنجی می‌شود؛ اگر یک روز خاص خطا دهد (مثلاً منبع занبوده)، آن تکرار رد می‌شود ولی بقیهٔ سری ثبت می‌مانند. سیاست allow_repeat اگر خاموش باشد، ثبتِ سری کلاً ممنوع می‌شود (ERR-010).',
        ],
        [
            'icon' => 'balance',
            'label' => 'سقف ماهانه (max_per_user) + نوار پیشرفت',
            'hint' => 'برای هر نوع منبع، سیاست max_per_user تعداد رزروی فعال + آزادشدهٔ یک کاربر در یک ماه را محدود می‌کند. در صفحهٔ کاربر نوار پیشرفت این سقف را نشان می‌دهد و وقتی به حد برسد، دکمهٔ رزرو با پیام «به سقف رزرو ماهانه رسیده‌اید — ابتدا یکی را لغو کنید» مسدود می‌شود (ERR-012). در این صفحه می‌توانید با لغو یا آزادسازی، جای سقف را باز کنید — ولی یادتان باشد آزادسازی جای سقف را آزاد نمی‌کند (آزادشده همچنان می‌شمارد).',
        ],
        [
            'icon' => 'block',
            'label' => 'سقف لغوی ماهانه (max_cancel_count)',
            'hint' => 'برای جلوگیری از سوءاستفاده، تعداد لغوهای خودِ کاربر در یک پنجرهٔ سی‌روزه محدود می‌شود (max_cancel_count). وقتی کاربر به این سقف برسد، ثبتِ رزرو جدید موقتاً برایش مسدود می‌شود (ERR-011) تا پایان پنجره. این سقف فقط لغوهای «توسط کاربر» را می‌شمارد؛ لغوی ادمین از این صفحه به این سقف اضافه نمی‌شود. اگر کاربر به‌خاطر لگدِ ادمینِ مکرر در این حالت گیر کرده، می‌توانید با لغوی ادمین از این صفحه، سقف لغوی او را تحت کنترل نگه دارید.',
        ],
        [
            'icon' => 'schedule',
            'label' => 'حداقل پیش‌آگاهی (window_hours)',
            'hint' => 'سیاست window_hours تعداد ساعتی که رزرو باید قبل از شروع ثبت شود را تعیین می‌کند. در صفحهٔ کاربر، اسلات‌هایی که قبل از «اکنون + window_hours» هستند با حالت «soon» خاکستری می‌شوند و انتخاب‌پذیر نیستند. اگر window_hours روی ۰ باشد این محدودیت برداشته می‌شود. خطای EARLY (ERR-004) در صورت تلاش به رزروِ زیرِ این حد بازمی‌گردد. این سیاست برای کاربر اعمال می‌شود؛ ادمین از آن معاف است.',
        ],
        [
            'icon' => 'event_available',
            'label' => 'روزها و ساعت‌های مجاز (allowed_days / allowed_hours)',
            'hint' => 'allowed_days فهرست روزهای هفتهٔ مجاز را محدود می‌کند (مثلاً فقط روزهای کاری) — روز خارج از لیست ERR-005. allowed_hours بازهٔ ساعتی فعالیت را تعیین می‌کند (start/end) و صفحهٔ کاربر اسلات‌ها را روی گرید ۳۰ دقیقه‌ای می‌سازد. این بازه می‌تواند از کنارِ نیمه‌شب هم عبور کند (پایان < شروع یعنی تا فردا صبح). slot خارج از بازه ERR-009 می‌شود. این دو فقط روی رزرو کاربر اعمال می‌شوند؛ ادمین معاف است.',
        ],
        [
            'icon' => 'timer',
            'label' => 'کران‌های مدت (min/max_duration_minutes)',
            'hint' => 'برای رزروهای ساعتی (نوع ملاقات)، حداقل و حداکثر مدت به دقیقه قابل تنظیم است. مدت کوتاه‌تر از حداقل ERR-007 و بلندتر از حداکثر ERR-008. در صفحهٔ کاربر، برچسب مدتِ انتخابی و اعتبارش زنده نمایش داده می‌شود. رزرو تمام‌روز از این کران‌ها معاف است.',
        ],
        [
            'icon' => 'swap_horiz',
            'label' => 'هم‌پوشانی با آزادشده‌ها (allow_overlap_release)',
            'hint' => 'به‌طور پیش‌فرض یک منبع در بازه‌ای که یک رزرو «آزادشده» دارد، قابل رزرو نیست (آزادشده همچنان جای منبع را اشغال می‌کند). اگر allow_overlap_release روشن باشد، رزروهای «آزادشده» به‌عنوان مانع در نظر گرفته نمی‌شوند و فقط رزروهای «فعال» هم‌پوشانی را مسدود می‌کنند — یعنی می‌توان روی یک آزادشده رزرو جدید زد. منبعِ فعال همیشه مانع است (ERR-013).',
        ],
        [
            'icon' => 'link',
            'label' => 'لغو یک‌تکه یا کلِ سری (allow_partial_cancel)',
            'hint' => 'وقتی کاربر یکی از رزروهای یک سری تکرارشونده را لغو می‌کند، سیاست allow_partial_cancel تعیین می‌کند چه اتفاقی بیفتد. اگر روشن باشد (پیش‌فرض): فقط همان یک رزرو لغو می‌شود. اگر خاموش باشد: لغوی یک عضو، کلِ سری (اصلی + همهٔ تکرارهای فعال) را لغو می‌کند — و در صفحهٔ کاربر هشدار «لغو این رزرو، تمام رزروهای این سری تکرارشونده را لغو می‌کند» نمایش داده می‌شود. ادمین از این صفحه فقط همان یک ردیف را لغو می‌کند (CancelAction همیشه روی اهدافِ انتخاب‌شده اعمال می‌شود، نه کل سری).',
        ],
        [
            'icon' => 'center_focus_strong',
            'label' => 'پیوند عمیق ?open=resource (focus)',
            'hint' => 'در صفحهٔ کاربر /reservation پارامتر کوئری open با آیدی یک منبع، آن منبع را در زبانهٔ درست (نوعش) بالا می‌آورد، فیلتر طبقه را پاک می‌کند، صفحه‌بندی را گسترش می‌دهد و کارتِ منبع را به بالای فهرست می‌آورد تا اسکرول و فلش روی آن برود. تمرکز روی «منبع» است (نه رزرو) — focusRecord آیدی را به Resource::find می‌دهد. این مسیری برای اسکرول مستقیم به یک رزروِ خاص با آیدی رزرو نیست.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tips_and_updates</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">رفتارهای هوشمند رزرو — برای مرجعِ اداره</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        این‌ها قوانینی هستند که در صفحهٔ کاربر به‌صورت خودکار اعمال می‌شوند و ریشهٔ اکثر پیام‌های خطا (ERR-XXX) هستند. ادمین از بیشترِ آن‌ها معاف است (UserActive، BookingPermission، TimeWindow، AllowedDays/Hours، Duration، Recurrence، CancellationLimit، ActiveLimit، UserConflict) ولی دو قانون همیشه حتی برای ادمین اجرا می‌شوند: TypeActive (نوع منبع فعال باشد) و ResourceAvailability (منبع در آن بازه آزاد باشد). برای جزئیات هر کد خطا و سیاستِ متناظر، راهنمای «سیاست‌ها» (ReservationPolicyResource) را ببینید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">auto_awesome</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">نه قابلیت هوشمند</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($features as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                همهٔ این سیاست‌ها بر اساس نوع منبع (seat/spot/car/meeting) در ReservationPolicyResource تنظیم می‌شوند و کش می‌شوند — پس پس از تغییر سیاست، کش پالیسی را پاک کنید تا فوراً اعمال شود.
            </p>
        </div>
    </div>
</div>