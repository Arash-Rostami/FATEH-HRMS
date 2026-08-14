@php
    $tabs = [
        [
            'icon' => 'event_upcoming',
            'label' => 'پیش‌رو',
            'hint' => 'رزروهای فعالِ آینده، مرتب از نزدیک‌ترین زمان. هر ردیف یک دکمهٔ لغو دارد (آیکون delete) که با کلیک، پنجرهٔ تأیید لغو را باز می‌کند. این تنها زبانه‌ای است که کاربر می‌تواند از درونِ آن رزرو را لغو کند.',
        ],
        [
            'icon' => 'history',
            'label' => 'قبلی',
            'hint' => 'رزروهای گذشتهٔ انجام‌شده. هر ردیف یک نشان «check» می‌گیرد و امکان لغو ندارد — گذشته دیگر قابل‌لغو نیست.',
        ],
        [
            'icon' => 'event_busy',
            'label' => 'لغو شده',
            'hint' => 'رزروهای لغوشده با نامِ خط‌خورده نمایش داده می‌شوند و یک نشانِ «چه کسی لغو کرده» (مدیریت/شخصی) و آیکون block می‌گیرند — ردیف‌ها بر اساس زمانِ لغو مرتب می‌شوند.',
        ],
        [
            'icon' => 'autorenew',
            'label' => 'آزادشده',
            'hint' => 'رزروهایی که کاربر آزاد کرده (وضعیت Released). آیکون autorenew می‌گیرند و همچنان در محاسبهٔ سقف ماهانه می‌شمارند — آزادشده جای سقف را باز نمی‌کند.',
        ],
    ];

    $badges = [
        [
            'icon' => 'admin_panel_settings',
            'chip' => 'مدیریت',
            'tone' => 'error',
            'hint' => 'وقتی شما (ادمین) رزرو را از همین صفحهٔ مدیریت لغو می‌کنید، وضعیت CancelledAdmin ثبت می‌شود و کاربر در زبانهٔ «لغو شده» یک نشان قرمز «مدیریت» می‌بیند. این تنها سیگنال بصریِ کاربر برای تشخیصِ لغوی ادمین است.',
        ],
        [
            'icon' => 'person',
            'chip' => 'شخصی',
            'tone' => 'neutral',
            'hint' => 'وقتی کاربر خودش از زبانهٔ «پیش‌رو» رزرو را لغو کند، وضعیت CancelledUser ثبت می‌شود و نشان خاکستری «شخصی» ظاهر می‌شود. این لغو در سقفِ لغوی ماهانهٔ کاربر (max_cancel_count) می‌شمارَد؛ لغوی ادمین نمی‌شمارَد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ رزرو چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ /reservation کاربر یک پنلِ «تاریخچه من» دارد که رزروهای خودش را در چهار زبانه نشان می‌دهد. وقتی کاربری از وضعیتِ رزروی‌اش شکایت می‌کند، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است — به‌خصوص نشانِ «چه کسی لغو کرده» که اثرِ عملِ ادمین را برای کاربر آشکار می‌کند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tab</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چهار زبانهٔ «تاریخچه من»</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">label</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">نشان «چه کسی لغو کرده»</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($badges as $b)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $b['icon'] }}</span>
                        </span>
                        <span @class([
                            'text-[10px] font-bold px-2 py-0.5 rounded-md',
                            'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' => $b['tone'] === 'error',
                            'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]' => $b['tone'] === 'neutral',
                        ])>{{ $b['chip'] }}</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $b['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌پرسد «چرا رزرویم لغو شده؟» — اگر نشانِ «مدیریت» دارد، یعنی ادمین آن را لغو کرده؛ اگر «شخصی»، خودش لغو کرده.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">repeat</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">سری تکرارشونده و هشدار لغو</p>
        </div>
        <div class="p-5 flex flex-col gap-3">
            <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium">
                رزروهای یک سریِ تکرارشونده در تاریخچه زیر یک ردیف جمع می‌شوند و یک نشانِ «repeat + N» با عنوان «سری تکرارشونده — N رزرو» می‌گیرند؛ N تعداد کلِ اعضای آن سری است.
            </p>
            <div class="flex items-start gap-2.5 rounded-xl bg-[var(--md-sys-color-error-container)]/40 p-3.5">
                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-error)] mt-0.5">warning</span>
                <p class="text-[12px] text-[var(--md-sys-color-on-surface)] leading-6 font-semibold">
                    در زبانهٔ «پیش‌رو»، اگر سیاست <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">allow_partial_cancel</code> خاموش باشد و رزرو بخشی از یک سری باشد، دکمهٔ لغو این هشدار را نشان می‌دهد: «هشدار: لغو این رزرو، تمام رزروهای این سری تکرارشونده را لغو می‌کند» — یعنی لغوی یک عضو، کلِ سری را لغو می‌کند.
                </p>
            </div>
            <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium">
                تفاوتِ مهم: لغوی ادمین از همین صفحهٔ مدیریت همیشه فقط همان یک ردیفِ انتخاب‌شده را لغو می‌کند، نه کلِ سری را — حتی اگر allow_partial_cancel خاموش باشد. جمع‌زدنِ کلِ سری فقط رفتارِ سمتِ کاربر است.
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">expand_more</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            بارگذاریِ تدریجی: تاریخچه در دسته‌های ۵تایی و فهرستِ منابع در دسته‌های ۶تایی بارگذاری می‌شود؛ کاربر با دکمهٔ «موارد بیشتر» بقیه را می‌بیند. اگر کاربر می‌گوید رزروی نمی‌بیند، اول زبانهٔ درستِ تاریخچه را بررسی کنید — رزروهای لغوشده/آزادشده در «پیش‌رو» نیستند.
        </p>
    </div>
</div>