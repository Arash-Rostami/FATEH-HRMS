@php
    $channels = [
        ['icon' => 'mail', 'color' => 'primary', 'label' => 'ایمیل', 'code' => 'email'],
        ['icon' => 'sms', 'color' => 'primary', 'label' => 'پیامک', 'code' => 'sms'],
        ['icon' => 'badge', 'color' => 'secondary', 'label' => 'نشانگر', 'code' => 'badge'],
        ['icon' => 'campaign', 'color' => 'tertiary', 'label' => 'پیام اعلانات', 'code' => 'nudge'],
        ['icon' => 'push_pin', 'color' => 'error', 'label' => 'پیام ثابت', 'code' => 'edge'],
    ];

    $recurrences = [
        ['icon' => 'block', 'label' => 'بدون تکرار', 'code' => 'none'],
        ['icon' => 'autorenew', 'label' => 'روزانه', 'code' => 'daily'],
        ['icon' => 'autorenew', 'label' => 'هفتگی', 'code' => 'weekly'],
        ['icon' => 'autorenew', 'label' => 'ماهانه (بر اساس ماه شمسی)', 'code' => 'monthly'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">alarm</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«یادآوری» یک زمان‌بند شخصی است که خود کاربر تنظیم می‌کند، نه یک وظیفه</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربر یادآوری را خودش تنظیم می‌کند. یادآوری‌ها جدا از اعلان‌های خودکار سیستم‌اند، اما برای اطلاع‌رسانی از همان روش‌ها استفاده می‌کنند. هر یادآوری به یک کاربر تعلق دارد و از زنگ بالای پنل یا دکمهٔ یادآوری روی وظیفه، تیکت، پروژه، سند یا رزرو مدیریت می‌شود. در این صفحه می‌توانید یادآوری را ببینید، عنوان یا سررسیدش را اصلاح کنید یا آن را حذف کنید. انجام، تعویق و توقف تکرار فقط در پنل کاربر ممکن است.
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
                پس از ثبت یادآوری تکرارشونده به‌عنوان انجام‌شده در پنل کاربر، سامانه یادآوری بعدی را می‌سازد. بستن «پیام ثابت صفحه» نیز یادآوری را انجام‌شده ثبت می‌کند.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">campaign</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">روش‌های اعلان — فقط قابل مشاهده</p>
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
                کاربر این پنج روش اعلان را از پنل خودش (با تیک‌زدن در فرم یادآوری) تنظیم می‌کند؛ این صفحه فقط روش‌های فعلی هر یادآوری را نشان می‌دهد و امکان تغییر آن‌ها از اینجا وجود ندارد.
            </p>
        </div>
    </div>
</div>
