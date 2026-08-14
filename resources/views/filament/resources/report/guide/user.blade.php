@php
    $areas = [
        [
            'icon' => 'slideshow',
            'label' => 'نمای کارتی و تایم‌لاین',
            'hint' => 'در دسکتاپ، گزارش‌ها به‌صورت کارت‌های اسکرولِ افقی با snap نمایش داده می‌شوند. ریلِ تایم‌لاین صرفاً تزئینی است و با دکمهٔ «نمایش/مخفی تایم‌لاین» پنهان می‌شود. کارتِ فعال ۱.۱۵ برابر بزرگ‌تر می‌شود و تاریخ و نوع فایل روی آن ظاهر می‌گردد.',
        ],
        [
            'icon' => 'view_list',
            'label' => 'نمای لیستی و موبایل',
            'hint' => 'نمای لیستی فشرده‌تر است. در صفحه‌های کوچک‌تر از ۷۶۸ پیکسل، سیستم به‌صورت خودکار به نمای لیستی سوییچ می‌کند و انتخاب کاربر در جلسه ذخیره می‌شود. دکمهٔ grid_view / view_list بالای فهرست نما را عوض می‌کند.',
        ],
        [
            'icon' => 'category',
            'label' => 'فیلترِ واحد سازمانی',
            'hint' => 'چیپ‌های واحد بالای فهرست از روی گزارش‌های «فعال» که واحد دارند ساخته می‌شوند (نه کل واحدها) — پس فقط واحدهایی نشان داده می‌شوند که گزارش دارند. این لیست ۴ ساعت کش می‌شود. کلیک روی یک واحد، فقط گزارش‌های همان واحد را فیلتر می‌کند؛ کلیکِ دوباره آن را لغو می‌کند.',
        ],
        [
            'icon' => 'search',
            'label' => 'جستجو',
            'hint' => 'جستجوی هم‌زمان در عنوان و توضیحات گزارش‌های فعال. وقتی با فیلتر چیپ ترکیب شود، هر دو شرط اعمال می‌شود. در صورت خالی بودن نتیجه، حالت خالیِ «فیلترشده» نمایش داده می‌شود.',
        ],
        [
            'icon' => 'slideshow',
            'label' => 'بارگذاریِ بیشتر',
            'hint' => 'در نمای کارتی، وقتی به انتهای ریل برسید، گزارش‌های بعدی به‌صورت خودکار بارگذاری می‌شوند (IntersectionObserver). هر بار ۱۰ گزارش اضافه می‌شود. در نمای لیستی، دکمهٔ «بارگذاری بیشتر» پایین فهرست است.',
        ],
        [
            'icon' => 'open_in_full',
            'label' => 'مشاهدهٔ جزئیات',
            'hint' => 'کلیک روی هر کارت یک پنجرهٔ کشویی (slideOver) باز می‌کند: تصویر جلدِ بزرگ، عنوان، نوع فایل، تاریخ و بدنهٔ HTML توضیحات. کلیک روی تصویر، لایت‌باکسِ تمام‌صفحه را باز می‌کند.',
        ],
        [
            'icon' => 'download',
            'label' => 'دانلود فایل',
            'hint' => 'دکمهٔ دانلود فایل اصلی گزارش (PDF/Word) را دریافت می‌کند. اگر گزارش غیرفعال شود، دانلود با ۴۰۳ مسدود می‌شود — هرچند کاربر از ابتدا فقط گزارش‌های فعال را می‌بیند و این یک لایهٔ دفاعی اضافه است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در زبانهٔ «گزارشات» چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانهٔ «گزارشات» در داشبورد کاربر، فقط گزارش‌های «فعال» را نشان می‌دهد — هیچ‌گاه گزارش‌های غیرفعال یا حذف‌شده ظاهر نمی‌شوند. وقتی کاربری می‌گوید «گزارشی که می‌خواستم نیامد» یا «دانلود نمی‌شود»، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">show_chart</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">رفتارهای کلیدی از دید کاربر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($areas as $a)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $a['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $a['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $a['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر تصویر جلد تعریف نشده باشد، پیش‌نمایش از روی فرمت فایل ساخته می‌شود (pdf.png / doc.png / report.png) و کش می‌شود — کاربر همیشه یک تصویر می‌بیند. نشانِ «به‌روز شده» وقتی ظاهر می‌شود که گزارش پس از انتشار ویرایش شده باشد.
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">help</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            کاربر در زبانهٔ «گزارشات» خودش یک راهنمای آماده دارد: دکمهٔ راهنما (آیکون help) بالای زبانه، یک راهنمای تب‌دار باز می‌کند که نماها، فیلتر واحد، تایم‌لاین و دانلود را توضیح می‌دهد. اگر کاربر سؤالی دربارهٔ نحوهٔ استفاده دارد، به همان دکمه ارجاع دهید.
        </p>
    </div>
</div>