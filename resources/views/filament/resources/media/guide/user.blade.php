@php
    $areas = [
        [
            'icon' => 'video_library',
            'label' => 'کتابخانهٔ رسانه',
            'hint' => 'در «دانش سازمانی › رسانه»، کاربران سری‌ها و فایل‌های مستقلی را می‌بینند که منتشر شده‌اند و به آن‌ها دسترسی دارند. می‌توانند فهرست را جست‌وجو یا فیلتر کنند.',
        ],
        [
            'icon' => 'smart_display',
            'label' => 'راهنمای ویدیویی بخش‌ها',
            'hint' => 'کاربری که به ویدیوی راهنما دسترسی دارد، گزینهٔ «راهنمای ویدیویی» را کنار عنوان همان بخش می‌بیند. در گوشی، این گزینه در منوی «⋯» کنار عنوان است. با انتخاب آن، ویدیو در زبانه‌ای تازه در «دانش سازمانی › رسانه» باز می‌شود.',
        ],
        [
            'icon' => 'play_circle',
            'label' => 'پخش با نوار پایین',
            'hint' => 'با انتخاب جلسه‌ای که فایل آمادهٔ پخش دارد، پخش شروع می‌شود. نوار پایین صفحه ابزار توقف، جابه‌جایی بین جلسه‌ها، سرعت و صدا را دارد.',
        ],
        [
            'icon' => 'history',
            'label' => 'ادامه از آخرین توقف',
            'hint' => 'جلسهٔ ناتمام از حوالی آخرین محل توقف ادامه پیدا می‌کند. جلسهٔ تمام‌شده از ابتدا پخش می‌شود. برگشت به بخش‌های قبلی، درصد پیشرفت را کم نمی‌کند.',
        ],
        [
            'icon' => 'lock',
            'label' => 'جلسهٔ در انتظار انتشار',
            'hint' => 'تاریخ انتشار کنار جلسه نوشته می‌شود. فایل از روز انتشار برای پخش و دانلود در دسترس است.',
        ],
        [
            'icon' => 'notes',
            'label' => 'توضیح هر جلسه',
            'hint' => 'اگر جلسه منتشر شده و توضیح دارد، با انتخاب یا پخش آن، عنوان و توضیح جلسه زیر توضیح سری دیده می‌شود.',
        ],
        [
            'icon' => 'subtitles',
            'label' => 'متن و بخش‌های جلسه',
            'hint' => 'اگر فایل متن یا بخش‌ها بارگذاری شده باشد، کاربر از زبانهٔ «متن جلسه» به زمان بخش دلخواه می‌رود.',
        ],
        [
            'icon' => 'folder_open',
            'label' => 'گزارش‌های مرتبط',
            'hint' => 'کاربر در «گزارش‌های مرتبط» می‌تواند فایل گزارش‌هایی را بگیرد که اجازهٔ دیدنشان را دارد.',
        ],
        [
            'icon' => 'smart_display',
            'label' => 'پخش ویدیویی',
            'hint' => 'ویدیو بالای صفحه پخش می‌شود. دکمه‌های پخش هنگام توقف یا با بردن نشانگر روی ویدیو ظاهر می‌شوند.',
        ],
        [
            'icon' => 'download',
            'label' => 'دانلود جلسه',
            'hint' => 'اگر فایل جلسه در دسترس باشد، دکمهٔ دانلود کنار آن دیده می‌شود.',
        ],
        [
            'icon' => 'monitoring',
            'label' => 'پیشرفت من',
            'hint' => 'در زبانهٔ «رسانه»، کاربر درصد پیشرفت و تعداد جلسه‌های تمام‌شدهٔ خودش را می‌بیند. جدول «پیشرفت یادگیری» دورترین نقطهٔ پخش ثبت‌شده برای هر کاربر را نشان می‌دهد.',
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
                کاربر می‌تواند هنگام پخش، فهرست جلسه‌ها را جست‌وجو و فیلتر کند. پیشرفت پخش جداگانه ثبت می‌شود.
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
