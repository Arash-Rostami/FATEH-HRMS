@php
    $signals = [
        [
            'icon' => 'mark_chat_unread',
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'نشان منو',
            'hint' => 'یک نشان روی آیتم «اعلانات» در منوی کاربر — تا وقتی اعلان خوانده‌نشدهٔ تازه (در پنجرهٔ ' . convertToPersian('30') . ' روز) هست روشن می‌ماند. نوشتن یا ویرایش اعلان، نشان منو را خودکار به‌روز می‌کند.',
        ],
        [
            'icon' => 'notifications_active',
            'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'label' => 'زنگولهٔ اعلان',
            'hint' => 'هنگام ساخت یا ویرایش اعلان، یک یک زنگوله برای هر کاربر فعال ساخته می‌شود؛ کاربر با کلیک روی زنگوله مستقیم به اعلان در پنل خود می‌رود. خواندن اعلان، زنگوله را «خوانده‌شده» می‌کند و نشان منو را به‌روز می‌کند.',
        ],
    ];
    $rules = [
        [
            'icon' => 'today',
            'label' => 'پنجرهٔ ' . convertToPersian('30') . ' روز',
            'hint' => 'اعلان فقط تا ' . convertToPersian('30') . ' روز پس از انتشار «تازه» محسوب می‌شود (isFresh). بعد از آن، برچسب «جدید» و «دیده شد» روی کارت کاربر خاموش می‌شود و اعلان از شمارش خوانده‌نشده خارج می‌گردد — ولی خود اعلان در فهرست می‌مانَد.',
        ],
        [
            'icon' => 'mark_email_read',
            'label' => 'خوانده‌شدن با باز کردن جزئیات',
            'hint' => 'وقتی کاربر اعلان را در پنل خود باز می‌کند، زنگولهٔ همان اعلان «خوانده‌شده» می‌شود. برچسب کارت از «جدید» به «دیده شد» تغییر می‌کند.',
        ],
        [
            'icon' => 'bolt',
            'label' => 'به‌روز شدن نشان منو پس از هر تغییر',
            'hint' => 'ساخت، ویرایش، حذف و خوانده‌شدن اعلان همگی نشان منو را به‌روز می‌کنند؛ پس نشان منوی کاربر در بازدید بعدی درست نمایش داده می‌شود و نیازی به کار دستی نیست.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">notifications_active</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">هر اعلان دو نشانه می‌سازد: نشان منو و زنگولهٔ اعلان</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        انتشار یا ویرایش یک اعلان دو نشانهٔ مستقل می‌سازد. «نشان منو» یک نقطهٔ روشن روی آیتم «اعلانات» در منوی کاربر است که تا وقتی اعلان خوانده‌نشده هست می‌ماند. «زنگولهٔ اعلان» یک مورد در زنگولهٔ کاربر است که با کلیک مستقیم به اعلان می‌رود و پس از خوانده‌شدن علامت‌گذاری می‌شود. این دو از هم جدایند: رد کردن زنگوله، نشان را خاموش نمی‌کند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">notifications</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">دو نشانهٔ اعلان</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($signals as $s)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $s['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $s['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $s['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $s['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">rule</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">قواعد تازگی و خوانده‌شدن</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rules as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-[20px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربر می‌گوید «زنگوله را رد کردم ولی نشان هنوز روشن است»، این رفتار درست است — نشان فقط با باز کردن اعلان (خوانده‌شدن) خاموش می‌شود، نه با رد کردن زنگوله.
            </p>
        </div>
    </div>
</div>