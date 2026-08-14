@php
    $types = [
        [
            'icon' => 'desk',
            'label' => 'میز کار',
            'code' => 'seat',
            'full' => true,
            'hint' => 'یک میز یا ایستگاه کاری قابل رزرو. رزرو تمام‌روز است؛ تاریخ را انتخاب می‌کنید و کل روز آن میز برای شما کنار گذاشته می‌شود. طبقه و داخلی اختیاری است.',
        ],
        [
            'icon' => 'local_parking',
            'label' => 'پارکینگ',
            'code' => 'spot',
            'full' => true,
            'hint' => 'یک جای پارکینگ مشخص. رزرو تمام‌روز است؛ فقط تاریخ را انتخاب می‌کنید. طبقه اختیاری است.',
        ],
        [
            'icon' => 'directions_car',
            'label' => 'خودرو',
            'code' => 'car',
            'full' => true,
            'hint' => 'خودروی سازمانی قابل رزرو. رزرو تمام‌روز است؛ فقط تاریخ را انتخاب می‌کنید. ظرفیت (سرنشین) اختیاری است.',
        ],
        [
            'icon' => 'person',
            'label' => 'ملاقات',
            'code' => 'meeting',
            'full' => false,
            'hint' => 'وقت ملاقات با یک همکار. تنها نوع ساعتی است: ساعت شروع و پایان را انتخاب می‌کنید. نام این منبع باید نام همان شخص باشد (در زبانهٔ جداگانه توضیح داده شده).',
        ],
    ];

    $statuses = [
        [
            'icon' => 'check_circle',
            'label' => 'فعال',
            'hint' => 'منبع در صفحهٔ رزرو کاربر نمایش داده می‌شود و قابل رزرو است. فقط منابع فعال در پرس‌وجوی موجودی لحاظ می‌شوند.',
        ],
        [
            'icon' => 'pause_circle',
            'label' => 'غیرفعال',
            'hint' => 'منبع موقتاً از دسترس خارج می‌شود؛ رزرو جدید برای آن ممکن نیست ولی رزروهای قبلی سرجایش می‌مانند. برای تعمیر، مرخصی یا خارج‌کردن موقت یک منبع از گردش استفاده می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«منبع» همان چیزی است که کاربران رزرو می‌کنند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این صفحه یک چیز قابل‌رزرو است — یک میز، یک جای پارکینگ، یک خودروی سازمانی، یا وقت ملاقات با یک همکار. کاربر در صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/reservation</code> زبانهٔ نوع را باز می‌کند و روی کارت یکی از منابعِ موجود دکمهٔ «رزرو» را می‌زند. شما اینجا فقط فهرست منابع را می‌سازید و فعال/غیرفعال می‌کنید؛ بقیهٔ کار خودکار است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">category</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چهار نوع منبع</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($types as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $t['code'] }}</code>
                            @if($t['full'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                                    <span class="material-symbols-rounded text-[12px]">event_available</span> تمام‌روز
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                                    <span class="material-symbols-rounded text-[12px]">schedule</span> ساعتی
                                </span>
                            @endif
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">toggle_on</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">وضعیت منبع: فعال یا غیرفعال</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($statuses as $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)]">{{ $s['icon'] }}</span>
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
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                قانون کلیدی: فقط منبع «فعال» قابل رزرو است. اگر منبعی در صفحهٔ کاربر ظاهر نمی‌شود، اول وضعیت آن را اینجا بررسی کنید.
            </p>
        </div>
    </div>
</div>