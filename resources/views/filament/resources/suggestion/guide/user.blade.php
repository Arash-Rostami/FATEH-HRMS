@php
    $panels = [
        [
            'icon' => 'format_list_bulleted',
            'label' => 'فهرست پیشنهادها',
            'hint' => 'کاربر می‌تواند با عنوان یا شناسه جستجو کند. «فقط نیازمند اقدام من» مواردی را نشان می‌دهد که باید برایشان نظر، تصمیم یا تکمیل اجرا ثبت کند.',
        ],
        [
            'icon' => 'add_circle',
            'label' => 'ثبت پیشنهاد',
            'hint' => 'کاربر عنوان، شرح، هدف، اصول سازمان و واحدهای مرتبط را انتخاب می‌کند. واحد خودش خودکار اضافه می‌شود. یک پیوست اختیاری است. ثبت پیشنهاد برای کاربران واحدهای MA و MG فعال نیست.',
        ],
        [
            'icon' => 'account_tree',
            'label' => 'صفحهٔ جزئیات',
            'hint' => 'مرحلهٔ فعلی، نظر واحدها، تصمیم و مسئولان اجرا دیده می‌شوند. نظرهای پرشده به دلیل پایان مهلت، برچسب «ثبت خودکار» دارند؛ این‌ها موافقت واحد نیستند.',
        ],
        [
            'icon' => 'rate_review',
            'label' => 'ثبت نظر (سرپرست واحد)',
            'hint' => 'وقتی نوبت واحد باشد، مدیر آن واحد موافق، مخالف یا بدون نظر قطعی را انتخاب می‌کند و دلیل می‌نویسد. برای پیشنهاد نیازمند تکمیل، مدیر واحد پیشنهاددهنده در همان پیشنهاد دوباره پاسخ می‌دهد.',
        ],
        [
            'icon' => 'gavel',
            'label' => 'تصمیم نهایی (مدیریت ارشد)',
            'hint' => 'فرد دارای اختیار تصمیم نهایی، پذیرش، رد یا نیاز به تکمیل را انتخاب می‌کند. همراه پذیرش می‌تواند واحدهای مسئول اجرا و کار آن‌ها را مشخص کند.',
        ],
        [
            'icon' => 'task_alt',
            'label' => 'تکمیل اقدام (واحد ارجاع‌شده)',
            'hint' => 'مدیر واحد مسئول اجرا، پس از انجام واقعی کار «ثبت تکمیل اقدام» را می‌زند. با ثبت همهٔ واحدهای مسئول، پیشنهاد «پایان‌یافته» می‌شود.',
        ],
        [
            'icon' => 'leaderboard',
            'label' => 'جدول برترین‌ها',
            'hint' => 'سه نفر بر اساس تعداد پیشنهادهای پذیرفته‌شده نمایش داده می‌شوند. این تعداد، به معنی تعداد کارهای اجراشده نیست.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ پیشنهادات چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        در صفحهٔ پیشنهاد، کاربر فهرست، فرم ثبت و جزئیات را کنار هم می‌بیند. کارت‌های قابل نمایش به دسترسی کاربر بستگی دارند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">devices</span>
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">tips_and_updates</span>
                اگر دکمهٔ نظر یا تصمیم دیده نمی‌شود، اول مرحلهٔ پیشنهاد و نقش فرد را بررسی کنید. اختیار کارها در هر دو پنل یکسان بررسی می‌شود.
            </p>
        </div>
    </div>
</div>
