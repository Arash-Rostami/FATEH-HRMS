@php
    $rows = [
        [
            'icon' => 'lightbulb',
            'label' => 'پیشنهاد چیست؟',
            'hint' => 'یک ایده برای بهتر شدن کارهاست. واحدهای مرتبط نظر می‌دهند و تصمیم‌گیرندهٔ ارشد آن را می‌پذیرد، رد می‌کند یا توضیح بیشتری می‌خواهد.',
        ],
        [
            'icon' => 'tag',
            'label' => 'شناسهٔ پیشنهاد',
            'hint' => 'شناسه با SN شروع می‌شود و تاریخ میلادی ثبت و شمارهٔ پیشنهاد را دارد. برای پیدا کردن پیشنهاد، نام یا شناسه را جستجو کنید.',
        ],
        [
            'icon' => 'visibility',
            'label' => 'نمای مدیر و نمای کاربر',
            'hint' => 'کاربر پیشنهاد را در صفحهٔ پیشنهادات ثبت و پیگیری می‌کند. در پنل مدیریت، پیشنهادها و نظر واحدها را می‌بینید. اختیار ثبت نظر، تصمیم و تکمیل اجرا به نقش فرد بستگی دارد.',
        ],
        [
            'icon' => 'block',
            'label' => 'مدیریت ارشد نمی‌تواند پیشنهاد ثبت کند',
            'hint' => 'ثبت پیشنهاد برای کاربران واحدهای دارای کد MA و MG مجاز نیست. برای دیگر کاربران، واحد سازمانی باید مشخص باشد. ثبت‌کننده در فرم معمول، همان کاربر واردشده است؛ برای ثبت از طرف فرد دیگر، از زبانهٔ پیشنهادهای همان کاربر استفاده کنید.',
        ],
        [
            'icon' => 'edit_note',
            'label' => 'ثبت نظر واحدها توسط پیشنهاددهنده',
            'hint' => 'این گزینه در پنل کاربر است و در فرم مدیریت فعال نیست. پیشنهاددهنده فقط اگر نظر واحدها را از قبل گرفته باشد، می‌تواند آن‌ها را هنگام ثبت وارد کند. این نظرها در روند بررسی پاسخ همان واحدها حساب می‌شوند.',
        ],
        [
            'icon' => 'speed',
            'label' => 'شمارش نظرها',
            'hint' => 'موافق، مخالف و بدون نظر قطعی جدا شمرده می‌شوند. «بدون نظر قطعی» موافقت نیست و ممکن است به دلیل بی‌پاسخ ماندن واحد، خودکار ثبت شده باشد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«پیشنهاد» یک درخواست بهبود فرآیند است که مسیر نظر واحد‌ها و تصمیم مدیریت ارشد را طی می‌کند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        پیشنهادهای سازمان را از این صفحه پیگیری کنید. برای ثبت نظر، تصمیم یا تکمیل اجرا، پیشنهاد را باز کنید و دکمهٔ مربوط را بزنید. نمایش دکمه‌ها به نقش شما و مرحلهٔ پیشنهاد بستگی دارد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">lightbulb</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">مفاهیم پایه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $r['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">tips_and_updates</span>
                مرحلهٔ پیشنهاد به‌صورت خودکار و بر اساس نظرها تعیین می‌شود، نه با تغییر دستی مدیر. جزئیات در زبانهٔ «چرخهٔ بررسی» آمده است.
            </p>
        </div>
    </div>
</div>
