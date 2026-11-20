@php
    $tabs = [
        [
            'icon' => 'event_upcoming',
            'label' => 'پیش‌رو',
            'hint' => 'رزروهای فعالِ هنوز تمام‌نشده در این بخش‌اند. کاربر با دکمهٔ لغو، متن تأیید را می‌بیند. رزرو ساعتی پیش از شروع و تمام‌روز تا پایان همان روز، در صورت اجازهٔ سقف لغو قابل لغو است.',
        ],
        [
            'icon' => 'history',
            'label' => 'قبلی',
            'hint' => 'رزروهایی که زمانشان گذشته است؛ کاربر دیگر نمی‌تواند آن‌ها را لغو کند.',
        ],
        [
            'icon' => 'event_busy',
            'label' => 'لغو شده',
            'hint' => 'رزروهای لغوشده توسط کاربر یا مدیریت. در صورت ثبت، علت لغو و نام لغوکننده هم نمایش داده می‌شوند.',
        ],
        [
            'icon' => 'autorenew',
            'label' => 'آزادشده',
            'hint' => 'رزروهایی که مدیریت آزاد کرده است. آزادسازی سهمیهٔ ماهانه را برنمی‌گرداند و با لغو فرق دارد.',
        ],
    ];

    $badges = [
        [
            'icon' => 'admin_panel_settings',
            'chip' => 'مدیریت',
            'tone' => 'error',
            'hint' => 'این رزرو را مدیر سامانه لغو کرده است. این لغو به تعداد لغوهای خود کاربر اضافه نمی‌شود.',
        ],
        [
            'icon' => 'person',
            'chip' => 'شخصی',
            'tone' => 'neutral',
            'hint' => 'صاحب رزرو آن را لغو کرده است. این مورد در سقف لغوهای او و بازهٔ شمارش تعیین‌شده حساب می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ رزرو چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        در صفحهٔ رزرو، بخش «تاریخچه من» رزروهای کاربر را در چهار زبانه نشان می‌دهد. نشان لغو مشخص می‌کند رزرو را کاربر لغو کرده است یا مدیریت.
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
                نشان «مدیریت» یعنی رزرو را مدیر لغو کرده است. نشان «شخصی» یعنی صاحب رزرو آن را لغو کرده است.
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
                رزروهای تکراری ممکن است در تاریخچه در یک ردیف جمع شوند. نشان تعداد، اندازهٔ همان گروه را نشان می‌دهد؛ نمایش یک ردیف به معنی ثبت فقط یک نوبت نیست.
            </p>
            <div class="flex items-start gap-2.5 rounded-xl bg-[var(--md-sys-color-error-container)]/40 p-3.5">
                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-error)] mt-0.5">warning</span>
                <p class="text-[12px] text-[var(--md-sys-color-on-surface)] leading-6 font-semibold">
                    متن تأیید لغو می‌گوید فقط همان نوبت لغو می‌شود یا نوبت‌های مرتبط هم لغو می‌شوند. کاربر باید پیش از تأیید، این پیام را بخواند.
                </p>
            </div>
            <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium">
                دکمهٔ «لغو رزرو» در مدیریت نیز قواعد گروه تکرار را رعایت می‌کند. اگر لغو تک‌نوبت مجاز نباشد، نوبت‌های مرتبطِ قابل لغو هم لغو می‌شوند.
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">expand_more</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            اگر رزروی دیده نمی‌شود، زبانه، فیلتر و بازهٔ انتخابی را بررسی کنید. رزروهای لغوشده و آزادشده در زبانهٔ خودشان هستند. دکمهٔ «موارد بیشتر» ادامهٔ فهرست را نشان می‌دهد.
        </p>
    </div>
</div>
