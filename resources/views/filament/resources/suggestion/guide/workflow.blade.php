@php
    $stages = [
        ['icon' => 'hourglass_empty', 'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'code' => 'pending', 'label' => 'ثبت‌شده', 'hint' => 'پیشنهاد ثبت شده و نظر واحدها هنوز کامل نیست.'],
        ['icon' => 'group', 'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]', 'code' => 'team_remarks', 'label' => 'منتظر نظر واحد پیشنهاددهنده', 'hint' => 'مدیر واحد پیشنهاددهنده باید نظر واحد خود را ثبت کند.'],
        ['icon' => 'corporate_fare', 'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]', 'code' => 'dept_remarks', 'label' => 'منتظر نظر واحدهای مرتبط', 'hint' => 'نظر واحد پیشنهاددهنده ثبت شده و نظر یک یا چند واحد دیگر باقی است.'],
        ['icon' => 'gavel', 'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]', 'code' => 'awaiting_decision', 'label' => 'منتظر تصمیم نهایی', 'hint' => 'نظر واحدها کامل شده است. تصمیم‌گیرندهٔ ارشد باید تصمیم بگیرد.'],
        ['icon' => 'check_circle', 'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]', 'code' => 'accepted', 'label' => 'پذیرفته‌شده', 'hint' => 'پیشنهاد پذیرفته شده است. اگر برای اجرا ارجاع دارد، تا ثبت انجام کار همهٔ واحدها در همین مرحله می‌ماند.'],
        ['icon' => 'cancel', 'chip' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]', 'code' => 'rejected', 'label' => 'ردشده', 'hint' => 'پیشنهاد پذیرفته نشده و بررسی پایان یافته است.'],
        ['icon' => 'find_in_page', 'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]', 'code' => 'under_review', 'label' => 'نیازمند تکمیل', 'hint' => 'مدیر واحد پیشنهاددهنده در همان پیشنهاد، توضیح و نظر تازه ثبت می‌کند تا مدیریت ارشد دوباره تصمیم بگیرد.'],
        ['icon' => 'lock', 'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'code' => 'closed', 'label' => 'پایان‌یافته', 'hint' => 'همهٔ واحدهای مسئول اجرا، انجام کار خود را ثبت کرده‌اند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">account_tree</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">مرحلهٔ پیشنهاد خودکار تعیین می‌شود؛ مدیر آن را دستی تغییر نمی‌دهد</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        با هر نظر جدید، سیستم پاسخ همهٔ واحدها را بررسی و مرحلهٔ پیشنهاد را تعیین می‌کند. پیشنهاد هشت مرحله دارد:
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">route</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">هشت مرحلهٔ پیشنهاد</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($stages as $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $s['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">smart_toy</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">کارهای خودکار سیستم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">hourglass_empty</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">بی‌پاسخی = بدون نظر قطعی پس از {{ convertToPersian('48') }} ساعت</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">فقط در دو مرحلهٔ نظر واحدها، پاسخ نرسیده پس از مهلت ۴۸ساعته در بررسی ساعتی سامانه «بدون نظر قطعی» می‌شود. این نظر، موافقت نیست؛ نظر ثبت‌شده عوض نمی‌شود و درخواست تکمیل هم شامل این کار خودکار نیست.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">inventory_2</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">پذیرش، پایان اجرای کار نیست</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">پس از پذیرش با ارجاع، هر واحد باید انجام کار خود را ثبت کند. تا آن زمان، پیشنهاد پذیرفته‌شده است؛ با ثبت همهٔ واحدها، پایان‌یافته می‌شود. پیشنهاد بدون ارجاع در حالت پذیرفته‌شده می‌ماند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">bolt</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">رد شدن فقط با تصمیم مدیریت ارشد</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">رد شدن فقط با تصمیم نهایی انجام می‌شود. «نیازمند تکمیل» رد شدن نیست؛ مدیر واحد پیشنهاددهنده توضیح تازه ثبت می‌کند و تصمیم‌گیرنده دوباره نظر می‌دهد.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">tips_and_updates</span>
                رنگ مرحله در نمودار نشان می‌دهد پیشنهاد اکنون در کدام بخش بررسی است.
            </p>
        </div>
    </div>
</div>
