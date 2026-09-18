@php
    $channels = [
        ['icon' => 'mail', 'color' => 'primary', 'label' => 'ایمیل', 'code' => 'email'],
        ['icon' => 'badge', 'color' => 'secondary', 'label' => 'نشانگر', 'code' => 'badge'],
        ['icon' => 'campaign', 'color' => 'tertiary', 'label' => 'پیام اعلانات', 'code' => 'nudge'],
        ['icon' => 'push_pin', 'color' => 'error', 'label' => 'پیام ثابت', 'code' => 'edge'],
    ];

    $recurrences = [
        ['icon' => 'block', 'label' => 'بدون تکرار', 'code' => 'none'],
        ['icon' => 'autorenew', 'label' => 'روزانه', 'code' => 'daily'],
        ['icon' => 'autorenew', 'label' => 'هفتگی', 'code' => 'weekly'],
        ['icon' => 'autorenew', 'label' => 'ماهانه', 'code' => 'monthly'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">alarm</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«ماژول یادآوری زمان» — یک زمان‌بند شخصی و دستی، نه یک وظیفه</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        این ماژول ابزاری برای زمان‌بندی دستی توسط خود کاربر است — کاملاً جدا از سامانهٔ خودکار اعلان‌های نشانگر/پیام اعلانات/پیام ثابت (Badge/Nudge/Edge)؛ فقط از همان سامانه برای رساندن یادآوری در لحظهٔ سررسید استفاده می‌کند. هر یادآوری همیشه به یک کاربر مشخص (<code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">user_id</code>) تعلق دارد و در پنل خودش، از طریق زنگ شناور در نوار بالا یا دکمهٔ یادآوری روی رکوردهای وظیفه/تیکت/پروژه/سند/رزرو، مدیریت می‌شود. از این صفحه فقط برای بازبینی، اصلاح دستی عنوان/سررسید یا حذف یک ردیف استفاده کنید — تکمیل («انجام شد»)، به‌تعویق‌انداختن و توقف تکرار منطق ویژه‌ای دارند که فقط از پنل کاربر قابل انجام‌اند و در این فرم در دسترس نیستند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">autorenew</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چهار حالت تکرار</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($recurrences as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $r['code'] }}</code>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">check_circle</span>
                وقتی یادآوری تکرارشونده «انجام شد» می‌شود، سیستم یک نمونهٔ جدید با سررسید بعدی می‌سازد؛ این نمونه‌سازی فقط از اقدام «انجام شد» در پنل کاربر اتفاق می‌افتد، نه از ویرایش این فرم.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">campaign</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">کانال‌های اعلان — فقط قابل مشاهده</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($channels as $c)
                @php
                    $chip = match ($c['color']) {
                        'primary'   => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                        'tertiary'  => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'error'     => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $chip }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $c['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $c['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $c['code'] }}</code>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">visibility</span>
                کاربر این چهار کانال را از پنل خودش (چک‌باکس‌های فرم یادآوری) تنظیم می‌کند؛ این صفحه فقط ترکیب فعلی هر ردیف را نشان می‌دهد و امکان تغییر آن‌ها از این‌جا وجود ندارد.
            </p>
        </div>
    </div>
</div>
