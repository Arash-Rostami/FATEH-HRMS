@php
    $areas = [
        [
            'icon' => 'video_library',
            'label' => 'کتابخانهٔ رسانه',
            'hint' => 'کاربران رسانه‌های «فعال» و «منتشرشده» را در صفحهٔ «رسانه» داشبورد می‌بینند: فهرست سری‌ها و موارد، با فیلتر نوع و جستجوی فوری.',
        ],
        [
            'icon' => 'play_circle',
            'label' => 'پخش با نوار پایین',
            'hint' => 'کلیک روی هر جلسه پخش را آغاز می‌کند و نوار پخش پایین صفحه (توقف، جلو/عقب ۱۰ ثانیه، جلسهٔ بعد/قبل، سرعت، صدا) تا پایان مرور می‌ماند.',
        ],
        [
            'icon' => 'history',
            'label' => 'ادامه از جایی که مانده',
            'hint' => 'موقعیت پخش هر جلسه به‌صورت محلی ذخیره و هم‌زمان با سرور همگام می‌شود؛ کاربر با «ادامه» از همان‌جا پخش را از سر می‌گیرد. درصد پیشرفت هر جلسه در فهرست نمایش داده می‌شود.',
        ],
        [
            'icon' => 'lock',
            'label' => 'جلسهٔ قفل‌شده',
            'hint' => 'جلسه‌های دارای تاریخ انتشار آینده با برچسب تاریخ باز شدن نمایش داده می‌شوند اما پخش و دانلودشان ممکن نیست.',
        ],
        [
            'icon' => 'subtitles',
            'label' => 'رونوشت و فصل‌ها',
            'hint' => 'اگر ادمین فایل vtt بارگذاری کرده باشد، متن همزمان پخش و فصل‌ها در زبانه‌های جدا نمایش داده می‌شوند؛ کلیک روی هر خط متن به همان لحظه می‌رود.',
        ],
        [
            'icon' => 'folder_open',
            'label' => 'مواد همراه',
            'hint' => 'اسناد پیوست رسانه (از بین گزارش‌ها) در زبانهٔ «مواد همراه» با دکمهٔ دانلود نمایش داده می‌شوند.',
        ],
        [
            'icon' => 'smart_display',
            'label' => 'پخش ویدیویی',
            'hint' => 'رسانه‌های تصویری در صحنهٔ ویدیویی بالای صفحه پخش می‌شوند؛ کنترل‌ها هنگام توقف یا نشاندن نشانگر ظاهر می‌شوند.',
        ],
        [
            'icon' => 'download',
            'label' => 'دانلود جلسه',
            'hint' => 'دکمهٔ دانلود کنار هر جلسه فایل اصلی را دریافت می‌کند؛ جلسهٔ قفل یا منقضی فایل ندارد.',
        ],
        [
            'icon' => 'monitoring',
            'label' => 'پیشرفت من',
            'hint' => 'کاربر نوار پیشرفت هر سری و درصد جلسه‌های تمام‌شده را در همان بخش «رسانه» می‌بیند؛ گزارش پیشرفت برای ادمین در بخش «پیشرفت» پنل مدیریت است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ «رسانه» چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        صفحهٔ «رسانه» داشبورد فقط موارد «فعال»، «منتشرشده» و «غیرمنقضی» را نشان می‌دهد — با احترام به «دسترسی واحدها»ی هر مورد. وقتی کاربری می‌گوید «جلسه‌ای که می‌خواستم نیامد»، این صفحه مرجعِ شما برای فهمیدنِ آن چیزی است که در صفحهٔ خودش می‌بیند.
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
                پخش روی مرورگر انجام می‌شود: جستجو، فیلترها و رفت‌وآمد جلسه‌ها بدون درخواست دوباره از سرور؛ فقط همگام‌سازی موقعیت با سرور رد و بدل می‌شود.
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">help</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            کاربر در بخش «رسانه» دانش سازمانی خودش راهنمای آماده دارد؛ اگر پرسشی دربارهٔ نحوهٔ استفاده دارد، به همان دکمهٔ راهنما ارجاع دهید.
        </p>
    </div>
</div>
