@php
    $tabs = [
        ['icon' => 'chat', 'label' => 'همه', 'badge' => null, 'hint' => 'تمام پیام‌ها — حذف‌شدهٔ نرم و فعال — در یک فهرست، مرتب بر اساس تاریخ ایجاد (نزولی).'],
        ['icon' => 'mail', 'label' => 'خوانده‌نشده', 'badge' => 'danger', 'hint' => 'پیام‌هایی که <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">read_at</code> آن‌ها خالی است. شمارش این زبانه یک نشان قرمز (danger) می‌گیرد.'],
        ['icon' => 'edit_note', 'label' => 'ویرایش‌شده', 'badge' => null, 'hint' => 'پیام‌هایی که <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">is_edited</code> آن‌ها <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">true</code> است — یعنی متن آن‌ها توسط خود کاربر یا از سمت ادمین ویرایش شده.'],
        ['icon' => 'delete', 'label' => 'حذف‌شده', 'badge' => 'warning', 'hint' => 'پیام‌های حذف‌شدهٔ نرم (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">onlyTrashed</code>). شمارش این زبانه یک نشان زرد (warning) می‌گیرد. این پیام‌ها هنوز از پایگاه داده حذف نشده‌اند و قابل بازیابی‌اند.'],
        ['icon' => 'warning', 'label' => 'در آستانه حذف', 'badge' => 'danger', 'hint' => 'پیام‌های حذف‌شدهٔ نرمی که از ۳۰ روز گذشته‌اند (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">deleted_at &lt;= now()-۳۰روز</code>) — در صف هرس خودکار قرار دارند. شمارش این زبانه یک نشان قرمز (danger) می‌گیرد.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tab</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">پنج زبانهٔ بالای فهرست، پیام‌ها را دسته‌بندی می‌کنند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانه‌ها با کوئریِ پایه ترکیب می‌شوند و شمارش نشان‌ها یک‌بار در هر بارگذاری صفحه و با مکانیزم <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">once()</code> کش می‌شود — یعنی پنج زبانه فقط یک کوئریِ آماری می‌زنند. زبانه‌ها را می‌توان از تنظیمات کاربری (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">show_list_tabs</code>) خاموش کرد.
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
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                            @if($t['badge'])
                                <span @class([
                                    'text-[10px] font-bold px-2 py-0.5 rounded-md',
                                    'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' => $t['badge'] === 'danger',
                                    'bg-[var(--md-sys-color-warning-container)] text-[var(--md-sys-color-on-warning-container)]' => $t['badge'] === 'warning',
                                ])>{{ $t['badge'] === 'danger' ? 'قرمز' : 'زرد' }}</span>
                            @endif
                        </div>
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
</div>