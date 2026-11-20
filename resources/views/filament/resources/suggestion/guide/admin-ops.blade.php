@php
    $ops = [
        [
            'icon' => 'rate_review',
            'label' => 'ثبت نظر',
            'hint' => 'در صفحهٔ مشاهده، اگر نوبت واحد شما باشد، «ثبت نظر» را بزنید. مدیر واحد پیشنهاددهنده در حالت «نیازمند تکمیل» هم از همین راه توضیح تازه می‌فرستد.',
        ],
        [
            'icon' => 'gavel',
            'label' => 'تصمیم نهایی',
            'hint' => 'این دکمه فقط برای فرد دارای اختیار تصمیم نهایی و در مرحلهٔ مربوط ظاهر می‌شود. پذیرش، رد یا نیاز به تکمیل را مشخص کنید. هنگام پذیرش، ارجاع برای اجرا اختیاری است.',
        ],
        [
            'icon' => 'task_alt',
            'label' => 'تکمیل اقدام واحد',
            'hint' => 'این دکمه برای مدیر واحدی است که مسئول اجرا شده است. پس از انجام کار، تکمیل را ثبت کنید. تکمیل همهٔ واحدهای مسئول، پیشنهاد را پایان‌یافته می‌کند.',
        ],
        [
            'icon' => 'forum',
            'label' => 'بخش «بررسی‌ها» در صفحهٔ ویرایش',
            'hint' => 'نظر واحدها، ثبت‌کنندهٔ نظر، توضیح‌ها، کارهای خواسته‌شده و وضعیت تکمیل را می‌بینید. این بخش برای مشاهده است؛ نظر را با دکمهٔ مربوط به نقش خود ثبت کنید.',
        ],
        [
            'icon' => 'summarize',
            'label' => 'صفحهٔ مشاهده با دو زبانه',
            'hint' => 'در زبانهٔ پیشنهاد، شرح، هدف‌ها، واحدها و مرحله را ببینید. در زبانهٔ نظرها و تصمیم، نظر واحدها و نتیجهٔ مدیریت را بخوانید.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'پیشنهادهای موردنظر را انتخاب کنید و خروجی اکسل بگیرید.',
        ],
        [
            'icon' => 'search',
            'label' => 'جستجوی سراسری',
            'hint' => 'عنوان، متن پیشنهاد یا نام ثبت‌کننده را جستجو کنید. با انتخاب نتیجه، صفحهٔ مشاهده باز می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات مدیریتی شما روی پیشنهادها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        سه دکمهٔ «نظر»، «تصمیم» و «تکمیل اقدام» در صفحهٔ مشاهده بر اساس نقش کاربر و مرحلهٔ پیشنهاد دیده می‌شوند. قواعد دسترسی در پنل مدیریت و پنل کاربر یکسان است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">دکمه‌های صفحهٔ مشاهده و بخش «بررسی‌ها»</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ops as $op)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $op['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">tips_and_updates</span>
                مرحله را با ثبت نظر، تصمیم یا انجام کار تغییر دهید؛ سامانه نتیجهٔ این کارها را خودکار در مرحله نشان می‌دهد.
            </p>
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2 mt-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                بخش «بررسی‌ها» سابقهٔ نظرها را نشان می‌دهد. مشاهدهٔ آن، به‌تنهایی اجازهٔ ثبت نظر یا تصمیم به شما نمی‌دهد.
            </p>
        </div>
    </div>
</div>
