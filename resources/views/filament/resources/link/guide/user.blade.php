@php
    $panels = [
        [
            'icon' => 'dataset_linked',
            'label' => 'بخش داخلی',
            'hint' => 'لینک‌های نوع «داخلی» اینجا می‌نشینند. هر کارت با آی‌پیِ کاربر مقایسه می‌شود: داخل شبکه → آدرس داخلی (در همان زبانه باز می‌شود)، بیرون شبکه → آدرس خارجی (در زبانهٔ جدید). اگر مدیر هیچ لینک داخلی تعریف نکرده باشد، این بخش با پیام خالی نشان داده نمی‌شود.',
        ],
        [
            'icon' => 'public',
            'label' => 'بخش خارجی',
            'hint' => 'لینک‌های نوع «خارجی» اینجا می‌نشینند و همیشه با آدرس خارجی و در زبانهٔ جدید باز می‌شوند. اگر مدیر هیچ لینک خارجی تعریف نکرده باشد، پیام «هیچ سامانه خارجی تعریف نشده» ظاهر می‌شود.',
        ],
        [
            'icon' => 'history',
            'label' => 'اخیراً باز شده',
            'hint' => 'فهرستی از لینک‌هایی که اخیراً باز کرده‌اید — فقط در همین مرورگر و روی همین دستگاه ذخیره می‌شود (localStorage، نهایتاً ' . convertToPersian('6') . ' مورد) و با حساب کاربری هماهنگ نمی‌شود. دکمهٔ «پاک کردن» فقط همین فهرست را پاک می‌کند.',
        ],
    ];
    $nuggets = [
        [
            'icon' => 'swap_horiz',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'یک لینک، دو مقصد',
            'hint' => 'لینک داخلی می‌تواند برای کاربرِ داخل شبکه به سامانهٔ درون‌سازمانی و برای کاربرِ بیرون از شبکه به آدرس اینترنتی برود — همان لینک، مقصد متفاوت. این رفتار از آی‌پیِ کاربر می‌آید، نه از تنظیمات مرورگر.',
        ],
        [
            'icon' => 'arrow_outward',
            'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'label' => 'همان زبانه یا زبانهٔ جدید',
            'hint' => 'وقتی لینک به آدرس داخلی می‌رود، در همان زبانه باز می‌شود (_self)؛ وقتی به آدرس خارجی می‌افتد، در زبانهٔ جدید باز می‌شود (_blank). لینکِ خارجی همیشه زبانهٔ جدید است.',
        ],
        [
            'icon' => 'badge',
            'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'label' => 'آیکونِ کارت از «توضیح آیکون» می‌آید',
            'hint' => 'وقتی تصویر بنر نباشد، آیکونِ کارت از فیلد «توضیح آیکون» مدیر ساخته می‌شود — این فیلد نام یک گلایفِ Material Symbols است (مثل link یا open_in_new). نام نامعتبر به‌صورت متن خام نشان داده می‌شود.',
        ],
        [
            'icon' => 'apps',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'نمای صفحه‌انداز (Launchpad)',
            'hint' => 'کنار نوار ریلی، دکمهٔ نمای صفحه‌انداز لینک‌ها را به‌صورت شبکهٔ فشرده با دو بخش داخلی/خارجی و کلیدهای ۱ تا ۹ برای دسترسی سریع نشان می‌دهد. انتخاب کاربر در نشست ذخیره می‌شود و دکمه‌های چپ/راست فقط در نوار ریلی ظاهر می‌شوند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در زبانهٔ «لینک‌ها» چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانهٔ «لینک‌ها و مسیرهای دیجیتال سازمان» سه بخش دارد: داخلی، خارجی و اخیراً باز شده. وقتی کاربری می‌گوید «این لینک برای من به جای دیگری می‌رود» یا «لینک داخلی را نمی‌بینم»، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">بخش‌های صفحهٔ کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($panels as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $p['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">tips_and_updates</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکات غیربدیهی که کاربر ممکن است درباره‌شان سوال بپرسد</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($nuggets as $n)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $n['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $n['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $n['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $n['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                فهرستِ داخلی و خارجی کاربر از کشِ دو‌ساعته می‌آید؛ پس اگر لینکی را اینجا ساختید و کاربر هنوز نمی‌بیند، یا کش را صبر دهید یا یک بار صفحه را بازنشانی کنید.
            </p>
        </div>
    </div>
</div>