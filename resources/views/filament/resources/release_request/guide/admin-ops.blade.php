@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» جزئیات کامل را نشان می‌دهد: شناسهٔ درخواست، عنوان، متن، پیوست‌ها (به‌صورت پیوند)، ثبت‌کننده، نوع، وضعیت (با رنگ و نماد) و پاسخ. فیلد «پاسخ» فقط در صورتی نمایش داده می‌شود که پر شده باشد، و در وضعیت «عدم امکان» به رنگ قرمز درمی‌آید.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش — تغییر وضعیت و پاسخ',
            'hint' => 'در فرم ویرایش می‌توانید نوع درخواست، وضعیت و پاسخ را تغییر دهید. «ثبت‌کننده» را نمی‌توان تغییر داد و همان کاربری است که درخواست را ثبت کرده. برای ثبت وضعیت «عدم امکان»، دکمهٔ «رد کردن» را بزنید. وقتی درخواست رد شده باشد، وضعیت آن دیگر قابل تغییر نیست.',
        ],
        [
            'icon' => 'cancel',
            'label' => 'رد کردن درخواست',
            'hint' => 'با زدن «رد کردن»، وضعیت درخواست به «عدم امکان» تغییر می‌کند. می‌توانید در کادر «پاسخ» دلیل را تا ۱۰۰۰ نویسه بنویسید. ثبت‌کننده پاسخ را در «درخواست‌های من» می‌بیند.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف و پاک شدن پیوست‌ها',
            'hint' => 'دکمهٔ «حذف» ردیف را برای همیشه پاک می‌کند. پیش از حذف، همهٔ فایل‌های پیوستِ آن درخواست از دیسک <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">public</code> هم حذف می‌شوند — حذفِ نرم نیست.',
        ],
        [
            'icon' => 'send',
            'label' => 'ثبت درخواست از طرف شما',
            'hint' => '«ثبت درخواست» بالای صفحه، نوع، عنوان و متن را می‌گیرد و درخواست را با نام شما و وضعیت «باز» ثبت می‌کند. برای افزودن پیوست، «ایجاد درخواست» را بزنید تا فرم کامل باز شود.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'با «خروجی Excel» درخواست‌های انتخاب‌شده را دریافت کنید. فایل شامل شناسه، نوع، عنوان، متن، وضعیت، پاسخ، ثبت‌کننده و تاریخ شمسی است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کارهایی که می‌توانید روی درخواست‌ها انجام دهید</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        درخواست‌ها را مشاهده و ویرایش کنید، وضعیت آن‌ها را تغییر دهید، پاسخ بنویسید یا درخواست‌های خارج از ضوابط را رد کنید. همچنین می‌توانید خروجی اکسل بگیرید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">دکمه‌های کنار هر مورد و بالای صفحه</p>
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
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برای رد کردن یک درخواست، از دکمهٔ قرمز «رد کردن» کنار آن استفاده کنید — نه از کادر وضعیت در فرم. این دکمه پنجرهٔ پاسخ را باز می‌کند و وضعیت را یک‌جا روی «رد شد» می‌گذارد.
            </p>
        </div>
    </div>
</div>
