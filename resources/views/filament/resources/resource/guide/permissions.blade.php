@php
    $flags = [
        [
            'icon' => 'select_all',
            'label' => 'دسترسی کامل',
            'code' => '{"all": true}',
            'hint' => 'کاربر به همهٔ زبانه‌های رزرو (میز کار، پارکینگ، خودرو، ملاقات) دسترسی دارد. ساده‌ترین حالت برای کاربری که همه‌چیز را رزرو می‌کند.',
        ],
        [
            'icon' => 'rule',
            'label' => 'دسترسی تفکیکی',
            'code' => '{"seat": true, "meeting": true}',
            'hint' => 'فقط نوع‌های مشخص باز می‌شوند. مثلاً کاربری که فقط میز کار و ملاقات رزرو می‌کند اما پارکینگ و خودرو نه. هر ترکیبی مجاز است.',
        ],
        [
            'icon' => 'block',
            'label' => 'بدون دسترسی',
            'code' => '(پرچم وجود ندارد)',
            'hint' => 'کاربر هیچ زبانه‌ای در صفحهٔ رزرو نمی‌بیند — صفحه خالی می‌ماند. این شایع‌ترین علت «رزرو برای کاربر نمایش داده نمی‌شود» است.',
        ],
    ];

    $gates = [
        [
            'icon' => 'key',
            'label' => 'پرچم booking روی کاربر',
            'hint' => 'در منوی «کاربران»، رکورد کاربر را ویرایش کنید و کلید booking را تنظیم کنید. بدون این پرچم، هیچ زبانه‌ای در /reservation ظاهر نمی‌شود.',
        ],
        [
            'icon' => 'event_available',
            'label' => 'نوع در سیاست فعال باشد',
            'hint' => 'حتی با داشتن پرچم booking، زبانهٔ نوعی که window_days آن صفر باشد غیرفعال (خاکستری) نمایش داده می‌شود. برای فعال بودن، window_days آن نوع باید بزرگ‌تر از صفر باشد — این در منوی «سیاست رزرو» تنظیم می‌شود و معمولاً از پیش درست است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">lock_open</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">چه کسی می‌تواند رزرو کند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        ساختن منبع کافی نیست؛ کاربر هم باید اجازهٔ رزرو داشته باشد. این اجازه با یک پرچم به‌نام <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">booking</code> روی رکورد کاربر داده می‌شود — در منوی «کاربران» و در صفحهٔ ویرایش کاربر. بدون این پرچم، صفحهٔ رزرو برای کاربر خالی است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">flag</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سه حالت پرچم booking</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($flags as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)]">{{ $f['icon'] }}</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50" dir="ltr">{{ $f['code'] }}</code>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">verified_user</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">دو دروازهٔ کنترل دسترسی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($gates as $g)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $g['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربری می‌گوید «زبانهٔ رزرو نمی‌بینم»: اول پرچم booking او را در منوی «کاربران» بررسی کنید؛ بعد window_days آن نوع را در منوی «سیاست رزرو».
            </p>
        </div>
    </div>
</div>