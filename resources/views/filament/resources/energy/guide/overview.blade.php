@php
    $dims = [
        ['icon' => 'fitness_center', 'label' => 'جسم'],
        ['icon' => 'favorite', 'label' => 'احساس'],
        ['icon' => 'psychology', 'label' => 'ذهن'],
        ['icon' => 'auto_awesome', 'label' => 'روح'],
    ];

    $rows = [
        ['icon' => 'trending_down', 'color' => 'error', 'label' => 'معنای امتیاز', 'text' => 'هر نشانهٔ انتخاب‌شده یک امتیاز دارد. امتیاز کلی از ۰ تا ۱۶ است و عدد بالاتر یعنی کاربر نشانه‌های بیشتری را انتخاب کرده است. نتیجهٔ ۱۲ به بالا در زبانهٔ «پرخطر» و نتیجهٔ ۸ یا کمتر در «مطلوب» دیده می‌شود.'],
        ['icon' => 'event_repeat', 'color' => 'primary', 'label' => 'فاصلهٔ ثبت ' . convertToPersian('25') . ' روزه', 'text' => 'پس از تکمیل، کاربر تا ' . convertToPersian('25') . ' روز نمی‌تواند پرسشنامهٔ تازه ثبت کند. این محدودیت در خود سیستم بررسی می‌شود، نه فقط با پنهان‌کردن فرم.'],
        ['icon' => 'event', 'color' => 'tertiary', 'label' => 'بانک پرسش‌های ماهانه', 'text' => 'برای هر بعد، ' . convertToPersian('12') . ' مجموعه پرسش وجود دارد. پرسش‌ها بر اساس ماه شمسی انتخاب می‌شوند و در هر ماه برای همهٔ کاربران یکسان‌اند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">bolt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">خواندن نتایج پرسش‌نامهٔ انرژی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربران پرسشنامه را در پنل کاربری تکمیل می‌کنند. هر ردیف جدول، پاسخ‌نامهٔ تکمیل‌شدهٔ یک کاربر است. در پنل مدیریت می‌توانید پاسخ‌ها را ببینید، اما نمی‌توانید به‌جای کاربر پاسخ ثبت کنید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چهار بعد ارزیابی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($dims as $d)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $d['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $d['label'] }}</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">۰ تا ۴</span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">در هر بخش، هر نشانهٔ انتخاب‌شده یک امتیاز دارد. اگر کاربر «هیچ‌کدام از موارد بالا» را انتخاب کند، امتیاز بخش صفر است.</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                امتیاز کلی از جمع امتیازهای چهار بخش به دست می‌آید و حداکثر ۱۶ است.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">insights</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکته‌های مهم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $row)
                @php
                    $chipClasses = match ($row['color']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $chipClasses }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $row['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
