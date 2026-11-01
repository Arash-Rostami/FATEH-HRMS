@php
    $steps = [
        ['icon' => 'schedule', 'label' => 'همه‌چیز در لحظهٔ سررسید', 'hint' => 'نشانگر، پیام اعلانات، پیام ثابت، ایمیل و پیامک با «ساعت» سررسید فعال می‌شوند، نه با شروع روز. یادآوری ساعت ۱۸ که صبح ثبت شده، تا ۱۸ هیچ نشانی نمی‌دهد.'],
        ['icon' => 'outgoing_mail', 'label' => 'ایمیل و پیامک فقط یک‌بار', 'hint' => 'هر یادآوری حداکثر یک‌بار ایمیل یا پیامک می‌گیرد. پیش از ارسال، سیستم علامت «ارسال شد» را ثبت می‌کند و دو درخواست هم‌زمان نمی‌توانند پیام تکراری بفرستند.'],
        ['icon' => 'rule', 'label' => 'شرط‌های ارسال', 'hint' => 'ارسال انجام نمی‌شود اگر: یادآوری انجام‌شده یا قبلاً ارسال‌شده باشد، سررسید یا پایان به‌تعویق‌افتادگی هنوز نرسیده باشد، ماژول یادآوری برای این سازمان خاموش باشد، یا اصل مورد (وظیفه، تیکت، ...) به سطل زباله رفته باشد. پیامک علاوه بر این فقط وقتی می‌رود که پیامک اعلان برای سازمان روشن باشد.'],
        ['icon' => 'person_off', 'label' => 'صاحب غیرفعال', 'hint' => 'اگر صاحب یادآوری فعال نباشد، ارسال رد می‌شود ولی علامت «ارسال شد» زده نمی‌شود؛ بررسی ساعتی تا ۲۴ ساعت بعد از سررسید دوباره تلاش می‌کند و اگر کاربر فعال شود، پیام می‌رود.'],
        ['icon' => 'history', 'label' => 'جاافتاده‌ها', 'hint' => 'یادآوری ایمیل/پیامک‌دار که سررسیدش تا ۲۴ ساعت پیش گذشته و هنوز ارسال نشده، با ذخیرهٔ بعدی یا بررسی ساعتی فوراً ارسال می‌شود. قدیمی‌تر از ۲۴ ساعت، ایمیل، پیامک و پیام ثابت صفحه دیگر دنبال نمی‌شوند؛ ولی پیام اعلانات (زنگ) تا وقتی یادآوری انجام‌نشده و دیرکرد باشد، هر ساعت دوباره بازسازی می‌شود.'],
        ['icon' => 'snooze', 'label' => 'به‌تعویق‌انداختن', 'hint' => 'به‌تعویق‌انداختن علامت «ارسال شد» را پاک می‌کند تا در پایان مهلت دوباره اعلان بدهد. ملاک زمان، دیرتر از میان «سررسید» و «به‌تعویق‌افتاده تا» است.'],
    ];

    $recurrence = [
        ['icon' => 'event_repeat', 'label' => 'ساخت نوبت بعد', 'hint' => 'هنگام «انجام شد»، یک نسخهٔ تازه با همان عنوان، یادداشت و روش‌های اعلان و با سررسید بعدی ساخته می‌شود. سررسید بعدی اگر هنوز در گذشته باشد (مثلاً کاربر چند روز دیر انجام داده)، آن‌قدر جلو می‌رود تا به آینده برسد.'],
        ['icon' => 'calendar_month', 'label' => 'تکرار ماهانه شمسی', 'hint' => 'روز همان روز ماه شمسی است و اگر ماه بعد آن روز را نداشته باشد به آخرین روز ماه می‌رود (۳۱ شهریور به ۳۰ مهر). ساعت تغییر نمی‌کند.'],
        ['icon' => 'push_pin', 'label' => 'نسخهٔ بدون اصل', 'hint' => 'اگر یادآوری به هیچ موردی وصل نبود، نسخهٔ بعدی بدون روش «نشانگر» ساخته می‌شود، چون نشانگر فقط برای یادآوریِ وصل‌به‌مورد معنا دارد.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">schedule_send</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">چه زمانی و چگونه یادآوری به کاربر می‌رسد</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        اگر کاربری می‌گوید «یادآوری‌ام نیامد»، اول این قواعد را بررسی کنید. این صفحه فقط توضیح می‌دهد؛ هیچ‌کدام از این رفتارها از فرم ادمین تغییر نمی‌کند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">notifications_active</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">قواعد رساندن یادآوری</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($steps as $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">info</span>
                بستن پیام ثابتِ یادآوری توسط کاربر، آن را «انجام‌شده» می‌کند؛ به همین دلیل ایمیل و پیامک همیشه پیش از ساخته‌شدن آن پیام ارسال می‌شوند.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">autorenew</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">جزئیات تکرار</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($recurrence as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
