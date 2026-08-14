@php
    $tabs = [
        ['icon' => 'list', 'label' => 'همه', 'badge' => null, 'hint' => 'تمام درخواست‌ها در یک فهرست، مرتب بر اساس تاریخ ایجاد (نزولی).'],
        ['icon' => 'forum', 'label' => 'باز', 'badge' => 'info', 'hint' => 'درخواست‌های در انتظار بازبینی — وضعیت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">open</code>.'],
        ['icon' => 'schedule', 'label' => 'در حال بررسی', 'badge' => 'warning', 'hint' => 'درخواست‌های در حال پیگیری — وضعیت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">in_review</code>.'],
        ['icon' => 'check_circle', 'label' => 'حل‌شده', 'badge' => 'success', 'hint' => 'درخواست‌های پایش‌شده — وضعیت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">resolved</code>.'],
        ['icon' => 'cancel', 'label' => 'رد شد', 'badge' => 'danger', 'hint' => 'درخواست‌های رد‌شده و غیرقابل‌بازگشت — وضعیت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">rejected</code>.'],
    ];

    $filters = [
        ['icon' => 'person', 'label' => 'ثبت‌کننده', 'hint' => 'فیلتر بر اساس کاربر ثبت‌کننده — فهرست قابل‌جستجو با preload.'],
        ['icon' => 'category', 'label' => 'نوع درخواست', 'hint' => 'فیلتر بر اساس نوع (پشتیبانی / پیشنهاد / باگ).'],
        ['icon' => 'flag', 'label' => 'وضعیت', 'hint' => 'فیلتر بر اساس وضعیت (باز / در حال بررسی / حل‌شده / رد شد).'],
        ['icon' => 'event', 'label' => 'تاریخ ایجاد', 'hint' => 'فیلتر بازهٔ زمانیِ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">created_at</code> — فیلتر مشترک همهٔ منابع.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tab</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">پنج زبانهٔ بالای فهرست، درخواست‌ها را بر اساس وضعیت جدا می‌کنند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شمارشِ نشانِ هر زبانه با یک کوئریِ آماریِ واحد و مکانیزم <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">once()</code> کش می‌شود — یعنی پنج زبانه فقط یک‌بار شمارش می‌شوند. زبانه‌ها را می‌توان از تنظیمات کاربری (<code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">show_list_tabs</code>) خاموش کرد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">list</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">پنج زبانه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $t['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                نشان‌های شمارش وقتی صفر باشند نمایش داده نمی‌شوند (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">?: null</code>).
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">چهار فیلتر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $f['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>