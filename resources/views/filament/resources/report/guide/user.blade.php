@php
    $areas = [
        [
            'icon' => 'slideshow',
            'label' => 'نمای کارتی و تایم‌لاین',
            'hint' => 'در رایانه، گزارش‌ها به‌صورت کارت‌های افقیِ قابل پیمایش نمایش داده می‌شوند. خط زمانیِ زیر کارت‌ها فقط تزئینی است و با دکمهٔ «نمایش/مخفی تایم‌لاین» پنهان می‌شود. کارتِ فعال ۱.۱۵ برابر بزرگ‌تر می‌شود و تاریخ و نوع فایل روی آن ظاهر می‌گردد.',
        ],
        [
            'icon' => 'bookmark',
            'label' => 'نشان شخصی',
            'hint' => 'کاربر می‌تواند کنار «دانلود»، دکمهٔ «نشان کردن» را بزند. فیلتر «فقط نشان‌شده‌ها» گزارش‌های نشان‌شده را در همان مرورگر نمایش می‌دهد.',
        ],
        [
            'icon' => 'view_list',
            'label' => 'نمای فهرستی و موبایل',
            'hint' => 'نمای فهرستی فشرده‌تر است. در صفحه‌های کوچک‌تر از ۷۶۸ پیکسل، سیستم خودکار به نمای فهرستی می‌رود و انتخاب کاربر تا پایان نشست حفظ می‌شود. دکمهٔ تغییر نما بالای فهرست قرار دارد.',
        ],
        [
            'icon' => 'category',
            'label' => 'فیلترِ واحد سازمانی',
            'hint' => 'دکمه‌های واحد بالای فهرست از روی گزارش‌های «فعال» که واحد دارند ساخته می‌شوند (نه کل واحدها) — پس فقط واحدهایی نشان داده می‌شوند که گزارش دارند. این فهرست ممکن است تا ۴ ساعت دیرتر به‌روز شود. کلیک روی یک واحد، فقط گزارش‌های همان واحد را فیلتر می‌کند؛ کلیکِ دوباره آن را لغو می‌کند.',
        ],
        [
            'icon' => 'search',
            'label' => 'جستجو',
            'hint' => 'جستجو در عنوان و توضیحات گزارش‌های فعال انجام می‌شود. اگر واحدی هم انتخاب شده باشد، هر دو شرط اعمال می‌شوند. اگر گزارشی پیدا نشود، پیام مربوط نمایش داده می‌شود.',
        ],
        [
            'icon' => 'slideshow',
            'label' => 'بارگذاریِ بیشتر',
            'hint' => 'در نمای کارتی، با رسیدن به انتهای فهرست، هر بار ۱۰ گزارش دیگر خودکار بارگذاری می‌شود. در نمای فهرستی از دکمهٔ «بارگذاری بیشتر» استفاده کنید.',
        ],
        [
            'icon' => 'open_in_full',
            'label' => 'مشاهدهٔ جزئیات',
            'hint' => 'کلیک روی هر کارت یک پنجرهٔ کشویی باز می‌کند: تصویر جلدِ بزرگ، عنوان، نوع فایل، تاریخ و متن توضیحات. کلیک روی تصویر، آن را تمام‌صفحه نشان می‌دهد.',
        ],
        [
            'icon' => 'download',
            'label' => 'دانلود فایل',
            'hint' => 'دکمهٔ دانلود فایل اصلی گزارش (PDF/Word) را دریافت می‌کند. اگر گزارش غیرفعال شود، دانلود آن مسدود می‌شود — هرچند کاربر از ابتدا فقط گزارش‌های فعال را می‌بیند و این یک لایهٔ دفاعی اضافه است.',
        ],
        [
            'icon' => 'visibility_off',
            'label' => 'گزارش مخفی',
            'hint' => 'گزارش غیرفعال، منقضی یا خارج از واحد شما نه در فهرست، نه در جستجو و نه با لینک مستقیم دیده می‌شود؛ لینک مستقیم بدون خطا به فهرست عادی برمی‌گردد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در بخش «گزارش‌ها» در دانش سازمانی چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        بخش «گزارش‌ها» در دانش سازمانی در داشبورد کاربر، فقط گزارش‌های «فعال» را نشان می‌دهد — هیچ‌گاه گزارش‌های غیرفعال یا حذف‌شده ظاهر نمی‌شوند. وقتی کاربری می‌گوید «گزارشی که می‌خواستم نیامد» یا «دانلود نمی‌شود»، این زبانه مرجعِ شما برای فهمیدنِ آنچه در صفحهٔ خودش می‌بیند است.
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
                اگر تصویر جلد انتخاب نشده باشد، تصویری متناسب با نوع فایل نمایش داده می‌شود. نشان «به‌روز شده» یعنی گزارش پس از انتشار ویرایش شده است.
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">help</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            کاربر در بخش «گزارش‌ها» در دانش سازمانی خودش یک راهنمای آماده دارد: دکمهٔ راهنما بالای زبانه، یک راهنمای تب‌دار باز می‌کند که نماها، فیلتر واحد، تایم‌لاین و دانلود را توضیح می‌دهد. اگر کاربر سؤالی دربارهٔ نحوهٔ استفاده دارد، به همان دکمه ارجاع دهید.
        </p>
    </div>
</div>
