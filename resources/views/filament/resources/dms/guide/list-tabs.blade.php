@php
    $tabs = [
        ['icon' => 'list', 'label' => 'همه اسناد', 'badge' => null, 'hint' => 'تمام اسناد (فعال/در بررسی/منسوخ) در یک فهرست، مرتب بر اساس آخرین بروزرسانی. زبانهٔ پیش‌فرض.'],
        ['icon' => 'check_circle', 'label' => 'فعال', 'badge' => 'success', 'hint' => 'اسناد با وضعیت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">live</code> — تنها اسنادی که در پنل کاربری دیده می‌شوند. شمارش این زبانه یک نشان سبز (success) می‌گیرد.'],
        ['icon' => 'hourglass_empty', 'label' => 'در حال بررسی', 'badge' => 'warning', 'hint' => 'اسناد با وضعیت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">under_review</code> — از دسترس کاربر خارج‌اند ولی هنوز فعال نشده‌اند. شمارش این زبانه یک نشان زرد (warning) می‌گیرد.'],
        ['icon' => 'cancel', 'label' => 'منسوخ شده', 'badge' => 'danger', 'hint' => 'اسناد با وضعیت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">obsolete</code> — از پنل کاربری حذف شده‌اند ولی ردیف در جدول باقی می‌ماند. شمارش این زبانه یک نشان قرمز (danger) می‌گیرد.'],
    ];

    $filters = [
        ['icon' => 'rule', 'label' => 'فیلتر نوع (سیستمی)', 'hint' => 'یک <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">TernaryFilter</code> روی فیلد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">type</code>: بله/خیر/همه — یعنی فقط سیستمی، فقط غیرسیستمی، یا هر دو.'],
        ['icon' => 'corporate_fare', 'label' => 'فیلتر واحد سازمانی', 'hint' => 'چند انتخابی از واحدها؛ کوئری با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">orWhereJsonContains</code> روی هر کد کار می‌کند و اسناد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">ALL</code> (سراسری) را هم شامل می‌شود.'],
        ['icon' => 'account_tree', 'label' => 'گروه‌بندی پویا', 'hint' => 'گروه‌بندی‌های اضافه از کلیدهای <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">extra</code>/<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">tags</code> ساخته می‌شوند و کلیدهای هم‌معنی (تفاوت فقط در فاصله/ بزرگی حروف) را در یک گروه ادغام می‌کنند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tab</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">چهار زبانه و فیلترهای فهرست</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانه‌ها کوئریِ پایه را فیلتر می‌کنند و شمارش نشان‌ها یک‌بار در هر بارگذاری با مکانیزم <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">once()</code> و کش <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">dms_document_counts</code> ({{ convertToPersian('15') }} دقیقه) محاسبه می‌شود. زبانه‌ها را می‌توان از تنظیمات کاربری (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">show_list_tabs</code>) خاموش کرد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">list</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چهار زبانه</p>
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
                                    'bg-[var(--md-sys-color-success-container)] text-[var(--md-sys-color-on-success-container)]' => $t['badge'] === 'success',
                                    'bg-[var(--md-sys-color-warning-container)] text-[var(--md-sys-color-on-warning-container)]' => $t['badge'] === 'warning',
                                    'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' => $t['badge'] === 'danger',
                                ])>{{ match($t['badge']) { 'success' => 'سبز', 'warning' => 'زرد', 'danger' => 'قرمز' } }}</span>
                            @endif
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $t['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">tune</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">فیلترها و گروه‌بندی</p>
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                نشان‌های شمارش وقتی صفر باشند نمایش داده نمی‌شوند (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">?: null</code>).
            </p>
        </div>
    </div>
</div>