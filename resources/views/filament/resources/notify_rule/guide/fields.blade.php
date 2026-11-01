@php
    $sections = [
        ['icon' => 'badge', 'color' => 'primary', 'label' => 'اطلاعات قانون', 'text' => 'نام (تا ۱۹۱ نویسه)، ماژول، رویدادها، کانال‌ها و «فقط با تغییر این اطلاعات». دست‌کم یک رویداد و یک کانال باید انتخاب شود. فهرست ماژول‌ها فقط ماژول‌هایی را دارد که خودتان به آن‌ها دسترسی دارید؛ اما قانونی که قبلاً برای ماژولی ساخته شده و دسترسی‌اش را ندارید، هنگام ویرایش همان ماژول را در فهرست نگه می‌دارد.'],
        ['icon' => 'filter_alt', 'color' => 'secondary', 'label' => 'شرط‌ها', 'text' => 'این بخش تا وقتی ماژول انتخاب نشده نمایش داده نمی‌شود. اطلاعات قابل انتخاب به ماژول بستگی دارد و با عوض‌شدن اطلاعات، شرط و مقدار پاک می‌شوند. بر اساس نوع اطلاعات، شرط‌هایی مثل «برابر است با»، «شامل»، «بزرگ‌تر از»، «پیش از» یا «یکی از» پیشنهاد می‌شود؛ با «پر شده باشد» و «خالی باشد» مقدار لازم نیست و با «یکی از» و «هیچ‌یک از» می‌توان چند مقدار را یکجا انتخاب کرد. حداکثر ۱۰ شرط مجاز است. تاریخ را شمسی بنویسید، مثلاً ۱۴۰۴/۰۸/۰۶.'],
        ['icon' => 'group', 'color' => 'tertiary', 'label' => 'گیرندگان', 'text' => 'سه نوع مخاطب: «کاربران انتخابی»، «همهٔ کارکنان» و «واحدهای انتخابی». در حالت کاربران انتخابی دست‌کم یک کاربر یا یک گیرندهٔ خارج از سیستم لازم است؛ اگر یکی پر باشد، دیگری اجباری نیست. دکمهٔ «افزودن واحد» کنار فهرست کاربران، همهٔ کاربران آن واحد را اضافه می‌کند ولی مجموع از ۱۰۰ نفر بیشتر نمی‌شود. گیرندگان خارج از سیستم تا ۲۰ نفر و هرکدام یا ایمیل معتبر یا شمارهٔ موبایل معتبرِ یکی از کانال‌های روشن‌اند.'],
        ['icon' => 'chat', 'color' => 'primary', 'label' => 'متن پیام', 'text' => 'ظاهر ایمیل (پیش‌فرض «ساده»)، موضوع (تا ۱۹۱ نویسه) و متن. متن از ۱ تا ۱۰ سطر است و هر سطر تا ۵۰۰ نویسه. پنج عبارت همیشگی {نام ماژول}، {نوع تغییر}، {شناسه}، {انجام‌دهنده} و {تاریخ و ساعت} در همه‌جا هست و اطلاعات ماژول انتخابی هم به آن‌ها اضافه می‌شود.'],
        ['icon' => 'schedule', 'color' => 'secondary', 'label' => 'فعال‌سازی و بازهٔ اعتبار', 'text' => 'شروع و پایان اختیاری‌اند. اگر فقط تاریخ را بدهید، ساعت شروع ۰۰:۰۰ و ساعت پایان ۲۳:۵۹ در نظر گرفته می‌شود. پایان پیش از شروع پذیرفته نمی‌شود و خطا روی تاریخ پایان نمایش داده می‌شود. کلید «فعال» به‌طور پیش‌فرض روشن است.'],
    ];

    $resets = [
        ['icon' => 'swap_horiz', 'label' => 'عوض‌کردن ماژول', 'hint' => 'اطلاعات «فقط با تغییر این اطلاعات» و همهٔ شرط‌ها پاک می‌شوند، چون به ماژول قبلی تعلق داشتند.'],
        ['icon' => 'visibility_off', 'label' => 'برداشتن رویداد «ویرایش»', 'hint' => 'گزینهٔ «فقط با تغییر این اطلاعات» پنهان می‌شود و با ذخیره حذف می‌گردد.'],
        ['icon' => 'domain_disabled', 'label' => 'برگشت از «واحدهای انتخابی»', 'hint' => 'اگر مخاطبان را از «واحدهای انتخابی» به حالت دیگری ببرید، واحدهای ذخیره‌شده با ذخیره پاک می‌شوند.'],
        ['icon' => 'rule', 'label' => 'اعتبارسنجی هنگام ذخیره', 'hint' => 'شرط‌ها یک‌بار دیگر کنترل می‌شوند و اطلاعات یا شرط نامعتبر، یا مقدار خالی، ذخیره را رد می‌کند. اگر بعداً اطلاعاتی از ماژول حذف شود، قانون حتی در حالت «هر یک از شرط‌ها» دیگر پیامی نمی‌فرستد و راهنمای ستون «شرط‌ها» در فهرست هشدار «اطلاعات دیگر وجود ندارد» را نشان می‌دهد؛ شرط را اصلاح کنید.'],
    ];

    $colors = [
        'primary'   => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'tertiary'  => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">edit_note</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">فرم قانون: پنج بخش و حد و مرزهای هرکدام</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فرم ساخت و ویرایش از پنج بخش پشت‌سرهم تشکیل شده است. مفهوم هر جزء در زبانهٔ «بررسی» توضیح داده شده؛ این‌جا فقط سقف‌ها، اجبارها و رفتارهای پنهان فرم آمده است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">list_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">بخش‌های فرم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($sections as $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $colors[$s['color']] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برای «همهٔ کارکنان» و «واحدهای انتخابی» سقف ۵٬۰۰۰ گیرنده در هر ارسال برقرار است؛ بیش از آن هیچ پیامی نمی‌رود و در گزارش ارسال ثبت می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">published_with_changes</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">وابستگی فیلدها به یکدیگر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($resets as $r)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">{{ $r['icon'] }}</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $r['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
