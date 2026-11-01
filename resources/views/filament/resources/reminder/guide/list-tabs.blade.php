@php
    $columns = [
        ['label' => 'شناسه / کاربر / عنوان / سررسید / انجام‌شده', 'hint' => 'پنج ستون پیش‌فرض نمایش. عنوان از ۶۰ نویسه به بعد کوتاه می‌شود و با نگه‌داشتن اشاره‌گر، متن کامل دیده می‌شود. اگر صاحب یادآوری حذف شده باشد، «کاربر حذف‌شده» نوشته می‌شود.'],
        ['label' => 'تکرار / تاریخ ایجاد', 'hint' => 'این دو ستون به‌صورت پیش‌فرض پنهان‌اند و از منوی ستون‌ها روشن می‌شوند.'],
        ['label' => 'ستون «انجام‌شده»', 'hint' => 'فقط نشان می‌دهد زمان انجام ثبت شده یا نه (تیک سبز یا خط خاکستری)؛ زمان دقیق در صفحهٔ مشاهده است.'],
        ['label' => 'مرتب‌سازی و جستجو', 'hint' => 'پیش‌فرض بر اساس سررسید، از جدیدترین به قدیمی‌ترین. جستجوی جدول روی «عنوان» و «نام کاربر» کار می‌کند.'],
    ];

    $filters = [
        ['label' => 'کاربر', 'hint' => 'فقط یادآوری‌های یک کاربر؛ با امکان جستجوی نام.'],
        ['label' => 'تکرار', 'hint' => 'یکی از حالت‌های بدون تکرار، روزانه، هفتگی یا ماهانه.'],
        ['label' => 'انجام‌شده', 'hint' => 'سه‌حالته: همه، فقط انجام‌شده‌ها (زمان انجام ثبت شده) یا فقط انجام‌نشده‌ها.'],
        ['label' => 'بازهٔ تاریخ ایجاد', 'hint' => 'فیلتر شمسی بر اساس تاریخ ثبت یادآوری.'],
    ];

    $stats = [
        ['label' => 'کل', 'hint' => 'همهٔ ردیف‌های موجود.'],
        ['label' => 'فعال', 'hint' => 'زمان انجام ندارند؛ به‌تعویق‌افتاده‌ها هم جزو فعال‌ها حساب می‌شوند.'],
        ['label' => 'دیرکرد', 'hint' => 'انجام‌نشده و سررسیدشان گذشته؛ این آمار به‌تعویق‌انداختن را در نظر نمی‌گیرد.'],
        ['label' => 'انجام‌شده', 'hint' => 'زمان انجام ثبت شده است.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tab</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">ستون‌ها، فیلترها و آمار فهرست</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست یادآوری‌ها زبانهٔ جداگانه ندارد؛ همهٔ یادآوری‌های همهٔ کاربران در یک جدول دیده می‌شوند و برای محدودکردن آن‌ها چهار فیلتر در دسترس است. گروه‌بندی و خروجی اکسل برای این بخش تعریف نشده است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">view_column</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">ستون‌ها و مرتب‌سازی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($columns as $c)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">table_rows</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $c['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $c['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">filter_list</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برای یافتن یادآوری‌های ازدست‌رفته، فیلتر «انجام‌شده» را روی «انجام‌نشده» بگذارید و جدول را بر اساس سررسید مرتب کنید.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">monitoring</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">آمار یادآوری‌ها در داشبورد</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($stats as $s)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">bar_chart</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
