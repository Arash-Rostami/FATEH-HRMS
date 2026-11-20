@php
    $tabs = [
        ['icon' => 'inbox', 'label' => 'همه', 'badge' => null, 'hint' => 'همهٔ تیکت‌ها؛ تازه‌ترین موارد بالاتر هستند.'],
        ['icon' => 'pending', 'label' => 'باز', 'badge' => 'info', 'hint' => 'تیکت‌هایی که هنوز رسیدگی به آن‌ها شروع نشده است؛ تعداد با نشان آبی نمایش داده می‌شود.'],
        ['icon' => 'sync', 'label' => 'در حال بررسی', 'badge' => 'warning', 'hint' => 'تیکت‌هایی که مسئول رسیدگی دارند و هنوز بسته نشده‌اند؛ تعداد با نشان زرد نمایش داده می‌شود.'],
        ['icon' => 'check_circle', 'label' => 'بسته‌شده', 'badge' => null, 'hint' => 'تیکت‌هایی که رسیدگی به آن‌ها پایان یافته یا درخواست‌دهنده آن‌ها را لغو کرده است.'],
        ['icon' => 'person_remove', 'label' => 'بدون مسئول', 'badge' => 'danger', 'hint' => 'تیکت‌های بسته‌نشده‌ای که هنوز مسئول ندارند؛ تعداد با نشان قرمز نمایش داده می‌شود.'],
        ['icon' => 'warning', 'label' => 'سررسید گذشته', 'badge' => 'danger', 'hint' => 'تیکت‌هایی که مهلتشان گذشته و هنوز بسته نشده‌اند؛ تعداد با نشان قرمز نمایش داده می‌شود.'],
    ];

    $filters = [
        ['icon' => 'domain', 'label' => 'واحد رسیدگی‌کننده', 'hint' => 'فقط تیکت‌های فرستاده‌شده به واحد انتخابی را ببینید.'],
        ['icon' => 'priority_high', 'label' => 'اولویت', 'hint' => 'تیکت‌های عادی، فوری یا خیلی فوری را جدا ببینید.'],
        ['icon' => 'category', 'label' => 'نوع درخواست', 'hint' => 'یک یا چند نوع درخواست را انتخاب کنید. نوع‌های اختصاصی واحدها و نوع‌های قدیمی هم در فهرست هستند.'],
        ['icon' => 'location_on', 'label' => 'حوزهٔ درخواست', 'hint' => 'یک یا چند حوزهٔ درخواست را انتخاب کنید. این فیلتر بدون انتخاب نوع هم کار می‌کند و حوزه‌های همهٔ واحدها را شامل می‌شود.'],
        ['icon' => 'assignment_ind', 'label' => 'مسئول و درخواست‌دهنده', 'hint' => 'مسئول رسیدگی و درخواست‌دهنده دو فیلتر جدا و قابل جستجو هستند.'],
        ['icon' => 'person_add', 'label' => 'تعیین مسئول', 'hint' => 'فقط تیکت‌های دارای مسئول، بدون مسئول یا هر دو را ببینید.'],
        ['icon' => 'calendar_today', 'label' => 'بازه تاریخ ثبت', 'hint' => 'تاریخ شروع و پایان را مشخص کنید تا تیکت‌های ثبت‌شده در همان بازه نمایش داده شوند.'],
        ['icon' => 'warning', 'label' => 'سررسید گذشته', 'hint' => 'فقط تیکت‌های بسته‌نشده با مهلت گذشته را ببینید.'],
    ];

    $groups = [
        ['icon' => 'pending', 'label' => 'گروه‌بندی', 'hint' => 'تیکت‌ها را بر اساس وضعیت، مسئول یا نوع درخواست دسته کنید. هر دسته را می‌توانید جمع و باز کنید.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tab</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">شش زبانهٔ بالای فهرست، تیکت‌ها را بر اساس وضعیت دسته می‌کنند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        با زبانه‌ها تیکت‌ها را بر اساس وضعیت ببینید. می‌توانید نمایش زبانه‌ها را در تنظیمات خود خاموش کنید.
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
                اگر تعداد تیکت‌های یک زبانه صفر باشد، نشان تعداد نمایش داده نمی‌شود.
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
