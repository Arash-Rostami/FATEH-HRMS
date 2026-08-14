@php
    $rows = [
        [
            'icon' => 'alt_route',
            'label' => 'منطق مسیریابی هوشمند',
            'hint' => 'وقتی «آدرس داخلی» پر شود، ستون «مسیریابی هوشمند» در جدول روشن می‌شود (آیکون چیپ، رنگ هشدار). در پنل کاربر، با هر کلیک، آی‌پیِ کاربر با فهرستِ «آی‌پی‌های شبکه داخلی» مقایسه می‌شود: اگر آی‌پی کاربر در فهرست بود → آدرس داخلی، وگرنه → آدرس خارجی.',
        ],
        [
            'icon' => 'apartment',
            'label' => 'بدون آی‌پی = همیشه داخلی',
            'hint' => 'اگر «آدرس داخلی» پر شده ولی فهرست آی‌پی خالی باشد، مسیریابی برای همه کاربران به آدرس داخلی می‌رود — یعنی «بدون آی‌پی» یعنی «همه داخلی». برای محدود کردن به شبکهٔ داخلی، حداقل یک آی‌پی وارد کنید.',
        ],
        [
            'icon' => 'link',
            'label' => 'قانون همراهی فیلدها',
            'hint' => '«آدرس داخلی» و «آی‌پی‌های شبکه داخلی» باید با هم پر یا با هم خالی باشند. اگر یکی را پر کنید و دیگری را خالی بگذارید، اعتبارسنجی رد می‌شود. این قانون روی هر دو فیلد اجرا می‌شود تا فرقی نکند کدام فیلد را تغییر دادید.',
        ],
        [
            'icon' => 'swap_horiz',
            'label' => 'رفتار زبانهٔ کاربر',
            'hint' => 'لینک داخلی وقتی به آدرس داخلی برود، در همان زبانه باز می‌شود (_self)؛ وقتی به آدرس خارجی بیفتد، در زبانهٔ جدید (_blank) باز می‌شود. لینکِ نوعِ «خارجی» همیشه در زبانهٔ جدید باز می‌شود. این رفتار از مقصد نهایی می‌آید، نه از نوع لینک.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">alt_route</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">مسیریابی هوشمند: یک لینک، دو مقصد</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        مسیریابی هوشمند یعنی یک لینکِ «داخلی» می‌تواند برای کاربرِ داخل شبکه به یک آدرس و برای کاربرِ بیرون از شبکه به آدرسِ دیگری برود. کلید این رفتار دو فیلد است: «آدرس داخلی» (مقصدِ داخل) و «آی‌پی‌های شبکه داخلی» (فهرست آی‌پی‌های مجاز). اگر «آدرس داخلی» خالی باشد، مسیریابی هوشمند خاموش است و همه به «آدرس خارجی» می‌روند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">route</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">قواعد مسیریابی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $r)
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برای بررسی کدام لینک‌ها مسیریابی هوشمند دارند، از فیلتر سه‌حالتهٔ «مسیریابی هوشمند» در جدول استفاده کنید؛ ابزارِ.setToolTipText ستون، شمار آی‌پی‌های ثبت‌شده را نشان می‌دهد.
            </p>
        </div>
    </div>
</div>