@php
    $n3 = convertToPersian('3');
    $n4 = convertToPersian('4');
    $n5 = convertToPersian('5');
    $n3600 = convertToPersian('3600');

    $panels = [
        [
            'icon' => 'support',
            'label' => 'دکمهٔ پشتیبانی و بازشدن',
            'hint' => 'در پنل کاربر یک دکمهٔ گرد با آیکون <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">support</code> بازشده را با یک کلیک باز می‌کند. همچنین رویداد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">open-release-request.window</code> می‌تواند مودال را با یک نوع پیش‌انتخاب‌شده باز کند — مثلاً وقتی کاربر از جایی «گزارش باگ» را انتخاب می‌کند.',
        ],
        [
            'icon' => 'edit_note',
            'label' => 'زبانهٔ «ثبت درخواست جدید»',
            'hint' => 'کاربر یکی از سه نوع (پشتیبانی / پیشنهاد / باگ) را با دکمه‌های رنگی انتخاب می‌کند، عنوان و متن را وارد می‌کند و تا ' . $n5 . ' فایل پیوست (تصویر یا سند، حداکثر ' . $n4 . ' مگابایت) ضمیمه می‌کند. حداقل طولِ عنوان ' . $n3 . ' و متن ' . $n5 . ' کاراکترِ معتبر است (تگ‌های HTML در شمارش لحاظ نمی‌شوند).',
        ],
        [
            'icon' => 'timer',
            'label' => 'سقف ثبت — ' . $n5 . ' درخواست در ساعت',
            'hint' => 'برای جلوگیری از سوءاستفاده، کاربر می‌تواند نهایتاً ' . $n5 . ' درخواست در بازهٔ ' . $n3600 . ' ثانیه ثبت کند. پس از رسیدن به سقف، پیام خطا روی فیلد «متن» نمایش داده می‌شود و شمارش معکوس ثانیه‌های باقی‌مانده را نشان می‌دهد.',
        ],
        [
            'icon' => 'history',
            'label' => 'زبانهٔ «درخواست‌های من»',
            'hint' => 'فهرست درخواست‌های کاربر، مرتب از جدیدترین، با شمارندهٔ صفحه‌بندی ' . $n5 . 'تایی و دکمهٔ «موارد بیشتر». هر ردیف نوع و وضعیت را با چیپ‌های رنگی نشان می‌دهد، عنوان و متن را خلاصه می‌کند، پیوست‌ها را لینک می‌کند و در صورت وجود پاسخ، آن را در یک قاب رنگی (قرمز برای رد‌شده، اصلی برای حل‌شده) نمایش می‌دهد.',
        ],
    ];

    $roles = [
        [
            'icon' => 'send',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'ثبت توسط کاربر',
            'hint' => 'کاربر درخواست را با وضعیت «باز» ثبت می‌کند. پیوست‌ها روی دیسک public ذخیره و به ردیف متصل می‌شوند. کاربر نمی‌تواند وضعیت را خودش تغییر دهد — فقط ادمین این کار را می‌کند.',
        ],
        [
            'icon' => 'forum',
            'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'label' => 'پاسخ ادمین',
            'hint' => 'وقتی ادمین فیلد «پاسخ» را پر می‌کند (یا درخواست را رد می‌کند)، آن پاسخ در زبانهٔ «درخواست‌های منِ» کاربر ظاهر می‌شود. کاربر فقط نتیجه را می‌بیند — امکان ویرایش یا حذف درخواست پس از ثبت را ندارد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در پنل خود چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربر از یک مودالِ ساده با دو زبانه استفاده می‌کند: «ثبت درخواست جدید» و «درخواست‌های من». وقتی کاربری می‌گوید درخواستی ثبت کرده ولی پاسخی نگرفته، این زبانه مرجعِ شما برای فهمیدنِ آنچه در پنل خودش می‌بیند است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">قابلیت‌های پنل کاربر</p>
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $p['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">forum</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">ثبت کاربر در برابر پاسخ ادمین</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($roles as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $r['chip'] }}">
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
                اگر کاربر می‌گوید «نمی‌توانم درخواست ثبت کنم»، احتمالاً به سقف ساعتی رسیده — شمارش معکوس در پیام خطا روی فیلد متن ظاهر می‌شود.
            </p>
        </div>
    </div>
</div>