@php
    $panels = [
        [
            'icon' => 'format_list_bulleted',
            'label' => 'فهرست پیشنهادها',
            'hint' => 'نوار کناری فهرست پیشنهادها را با جستجو (در عنوان و شناسهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">SN-</code>) و دکمهٔ «بارگذاری بیشتر» نشان می‌دهد. هر کارت شمارش موافق/نیمه‌موافق/مخالف، نام ثبت‌کننده و واحدهای ذی‌نفع را نمایش می‌دهد. کارتِ پیشنهادی که از واحدِ شما انتظار پاسخ دارد، با یک نشان اعلان و حاشیهٔ قرمز مشخص می‌شود (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">requiresMyAction</code>).',
        ],
        [
            'icon' => 'add_circle',
            'label' => 'ثبت پیشنهاد',
            'hint' => 'دکمهٔ «ایجاد» فقط برای کاربران غیرِ واحد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">MA</code> ظاهر می‌شود. فرم شامل عنوان، شرح، اهداف، قواعد، واحدهای ذی‌نفع، پیوست و پرچم «تکمیل شخصی» است. واحد ثبت‌کننده خودکار به فهرست ذی‌نفعان اضافه و واحد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">MA</code> از آن حذف می‌شود. در حالت «تکمیل شخصی»، ثبت‌کننده می‌تواند پیش‌فرض بازخورد همهٔ واحدها را هم پر کند.',
        ],
        [
            'icon' => 'account_tree',
            'label' => 'صفحهٔ جزئیات',
            'hint' => 'تایم‌لاین هفت‌مرحله‌ای روند بررسی، اطلاعات تکمیلی (ثبت‌کننده، واحد، مهلت بررسی با ⚠، تکمیل شخصی، پاسخ‌های دریافتی، ارجاع به مدیریت ارشد و ارجاع برای اقدام)، شرح، اهداف، قواعد، وضعیت هر واحد ذی‌نفع و فهرست بازخوردها. بازخوردهای خودکار با برچسب «تولید خودکار» مشخص می‌شوند.',
        ],
        [
            'icon' => 'rate_review',
            'label' => 'ثبت بازخورد (سرپرست واحد)',
            'hint' => 'اگر واحد شما در مرحلهٔ بازخورد باشد، کارت «ثبت بازخورد» با گزینه‌های موافق، نیمه‌موافق و مخالف و بخش توضیحات نمایش داده می‌شود. با ثبت بازخورد، مرحله به‌روز می‌شود.',
        ],
        [
            'icon' => 'gavel',
            'label' => 'تصمیم نهایی (مدیریت ارشد)',
            'hint' => 'تصمیم‌گیرندهٔ ارشد در مرحلهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">awaiting_decision</code> کارت «تصمیم نهایی» را می‌بیند: پذیرش (با ارجاع اختیاری واحدها و دستورالعمل اجرا)، رد یا درخواست تکمیل مجدد. <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">under_review</code> یعنی «نیازمند تکمیل» — نه رد شدن.',
        ],
        [
            'icon' => 'task_alt',
            'label' => 'تکمیل اقدام (واحد ارجاع‌شده)',
            'hint' => 'وقتی پیشنهاد پذیرفته شود و واحد شما برای اجرای اقدام ارجاع داده شود، کارت «تکمیل اقدام واحد» ظاهر می‌شود. با کلیک روی «ثبت تکمیل اقدام»، ردیف بررسیِ واحد شما <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">complete</code> می‌شود. وقتی همهٔ واحدهای ارجاع‌شده تکمیل کنند، مرحله به <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">closed</code> می‌رود.',
        ],
        [
            'icon' => 'leaderboard',
            'label' => 'جدول برترین‌ها',
            'hint' => 'بالای صفحه، سه کاربر برتر بر اساس تعداد پیشنهادهای پذیرفته‌شده نمایش داده می‌شوند — انگیزهٔ مشارکت.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در صفحهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">/suggestion</code> چه می‌بیند؟</p>
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
                اگر کاربر می‌گوید «دکمهٔ بازخورد/تصمیم را نمی‌بینم»، احتمالاً پیشنهاد در مرحلهٔ مناسب نیست یا نقش او اجازهٔ این کار را نمی‌دهد؛ خطای سیستم نیست. همان قواعد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">SuggestionAccessPolicy</code> برای ادمین و کاربر اجرا می‌شود.
            </p>
        </div>
    </div>
</div>