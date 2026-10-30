@php
    $panels = [
        [
            'icon' => 'dataset_linked',
            'label' => 'بخش داخلی',
            'hint' => 'لینک‌های نوع «داخلی» اینجا نمایش داده می‌شوند. اگر کاربر داخل شبکه باشد، لینک به آدرس داخلی می‌رود و در همان زبانه باز می‌شود؛ اگر بیرون شبکه باشد، به آدرس خارجی می‌رود و در زبانهٔ جدید باز می‌شود. اگر هیچ لینک داخلی تعریف نشده باشد، این بخش اصلاً نمایش داده نمی‌شود.',
        ],
        [
            'icon' => 'public',
            'label' => 'بخش خارجی',
            'hint' => 'لینک‌های نوع «خارجی» اینجا نمایش داده می‌شوند و همیشه با آدرس خارجی و در زبانهٔ جدید باز می‌شوند. اگر هیچ لینک خارجی تعریف نشده باشد، پیام «هیچ سامانه خارجی تعریف نشده» دیده می‌شود.',
        ],
        [
            'icon' => 'history',
            'label' => 'اخیراً باز شده',
            'hint' => 'فهرست لینک‌هایی که کاربر اخیراً باز کرده است (حداکثر ' . convertToPersian('6') . ' مورد). این فهرست فقط در همان مرورگر و همان دستگاه ذخیره می‌شود و با حساب کاربری به دستگاه دیگر نمی‌رود. دکمهٔ «پاک کردن» فقط همین فهرست را خالی می‌کند.',
        ],
    ];
    $nuggets = [
        [
            'icon' => 'swap_horiz',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'یک لینک، دو مقصد',
            'hint' => 'لینک داخلی برای کاربری که داخل شبکه است به سامانهٔ درون‌سازمانی و برای کاربری که بیرون از شبکه است به آدرس اینترنتی می‌رود؛ یعنی یک لینک با دو مقصد. این کار بر اساس آی‌پی کاربر انجام می‌شود، نه تنظیمات مرورگر او.',
        ],
        [
            'icon' => 'arrow_outward',
            'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'label' => 'همان زبانه یا زبانهٔ جدید',
            'hint' => 'وقتی لینک به آدرس داخلی می‌رود، در همان زبانه باز می‌شود؛ وقتی به آدرس خارجی می‌رود، در زبانهٔ جدید باز می‌شود. لینک «خارجی» همیشه در زبانهٔ جدید باز می‌شود.',
        ],
        [
            'icon' => 'badge',
            'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'label' => 'آیکون کارت از «آیکون جایگزین» می‌آید',
            'hint' => 'وقتی تصویر بنر نباشد، آیکون کارت همان آیکونی است که شما در کادر «آیکون جایگزین» انتخاب کرده‌اید.',
        ],
        [
            'icon' => 'apps',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'نمای صفحه‌انداز',
            'hint' => 'کنار نوار ریلی، دکمهٔ نمای صفحه‌انداز لینک‌ها را فشرده و کنار هم، در دو بخش داخلی و خارجی، نشان می‌دهد و با کلیدهای ۱ تا ۹ می‌شود سریع به آن‌ها رفت. انتخاب کاربر برای همان نشست حفظ می‌شود و دکمه‌های چپ و راست فقط در نوار ریلی دیده می‌شوند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در زبانهٔ «لینک‌ها» چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانهٔ «لینک‌ها و مسیرهای دیجیتال سازمان» سه بخش دارد: داخلی، خارجی و اخیراً باز شده. وقتی کاربری می‌گوید «این لینک برای من به جای دیگری می‌رود» یا «لینک داخلی را نمی‌بینم»، با این توضیحات می‌فهمید او در صفحهٔ خودش دقیقاً چه می‌بیند.
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
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکاتی که کاربران ممکن است دربارهٔ آن‌ها بپرسند</p>
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
                لینک‌های داخلی و خارجی تا دو ساعت در پنل کاربر نگه داشته می‌شوند؛ پس اگر لینکی ساخته‌اید و کاربر هنوز آن را نمی‌بیند، کمی صبر کنید یا یک بار صفحه را دوباره باز کنید.
            </p>
        </div>
    </div>
</div>