@php
    $tabs = [
        ['icon' => 'inbox', 'label' => 'همه', 'badge' => null, 'hint' => 'تمام تیکت‌ها در یک فهرست، مرتب بر تاریخ ثبت (نزولی).'],
        ['icon' => 'pending', 'label' => 'باز', 'badge' => 'info', 'hint' => 'تیکت‌های با وضعیت «باز». شمارش این زبانه یک نشان آبی (info) می‌گیرد.'],
        ['icon' => 'sync', 'label' => 'در حال بررسی', 'badge' => 'warning', 'hint' => 'تیکت‌های با وضعیت «در حال بررسی». شمارش این زبانه یک نشان زرد (warning) می‌گیرد.'],
        ['icon' => 'check_circle', 'label' => 'بسته‌شده', 'badge' => null, 'hint' => 'تیکت‌های بسته‌شده. این زبانه نشان شمارش نمی‌گیرد.'],
        ['icon' => 'person_remove', 'label' => 'تخصیص‌نیافته', 'badge' => 'danger', 'hint' => 'تیکت‌های باز یا در حال بررسی که <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">assigned_to</code> آن‌ها خالی است. شمارش این زبانه یک نشان قرمز (danger) می‌گیرد.'],
        ['icon' => 'warning', 'label' => 'سررسید گذشته', 'badge' => 'danger', 'hint' => 'تیکت‌هایی که مهلتِ رسیدگی (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">completion_deadline</code>)شان گذشته و هنوز بسته نشده‌اند. شمارش این زبانه یک نشان قرمز (danger) می‌گیرد.'],
    ];

    $filters = [
        ['icon' => 'domain', 'label' => 'واحد هدف', 'hint' => 'تیکت‌ها را بر اساس «واحد هدف» (ذخیره‌شده در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">extra-&gt;target_department</code>) فیلتر می‌کند.'],
        ['icon' => 'priority_high', 'label' => 'اولویت', 'hint' => 'فیلتر بر اساس اولویت (عادی/فوری/خیلی فوری).'],
        ['icon' => 'category', 'label' => 'نوع درخواست', 'hint' => 'فیلتر بر اساس نوع (پشتیبانی/دسترسی/توسعه).'],
        ['icon' => 'assignment_ind', 'label' => 'مسئول و درخواست‌دهنده', 'hint' => 'دو فیلتر جدا: فیلتر «مسئول» و فیلتر «درخواست‌دهنده» — هر دو قابل جستجو با پیش‌بارگذاری.'],
        ['icon' => 'person_add', 'label' => 'وضعیت تخصیص (سه‌حالته)', 'hint' => 'فیلتر سه‌حالته: تخصیص‌یافته / تخصیص‌نیافته / هر دو.'],
        ['icon' => 'calendar_today', 'label' => 'بازه تاریخ ثبت', 'hint' => 'فیلتر بازه‌ایِ تاریخ ثبت تیکت.'],
        ['icon' => 'warning', 'label' => 'سررسید گذشته', 'hint' => 'فیلترِ گذغشته از مهلت؛ فقط تیکت‌های بسته‌نشده با مهلتِ سپری‌شده.'],
    ];

    $groups = [
        ['icon' => 'pending', 'label' => 'گروه‌بندی', 'hint' => 'تیکت‌ها را می‌توان بر اساس «وضعیت»، «مسئول» یا «نوع درخواست» گروه‌بندی کرد — هر گروهِ قابل جمع‌شدنه.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tab</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">شش زبانهٔ بالای فهرست، تیکت‌ها را بر اساس وضعیت دسته می‌کنند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شمارشِ چهار زبانهٔ وضعیت‌دار با یک کوئریِ آماریِ واحد و مکانیزمِ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">once()</code> کش می‌شود — یعنی شش زبانه فقط یک بار کوئریِ آماری می‌زنند. زبانه‌ها را می‌توان از تنظیمات کاربری (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">show_list_tabs</code>) خاموش کرد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">list</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">شش زبانه</p>
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
                                    'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => $t['badge'] === 'info',
                                    'bg-[var(--md-sys-color-warning-container)] text-[var(--md-sys-color-on-warning-container)]' => $t['badge'] === 'warning',
                                    'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' => $t['badge'] === 'danger',
                                ])>{{ $t['badge'] === 'info' ? 'آبی' : ($t['badge'] === 'warning' ? 'زرد' : 'قرمز') }}</span>
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

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">filter_alt</span>
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
            @foreach($groups as $g)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $g['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $g['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                رنگ ستون «مهلت رسیدگی»: قرمز اگر گذشته یا دیرتر از مهلت انجام شده، سبز اگر در مهلت انجام شده، آبی اگر هنوز نرسیده و نهایی نشده.
            </p>
        </div>
    </div>
</div>