@php
    $modes = [
        [
            'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'icon' => 'public',
            'label' => 'عمومی',
            'hint' => 'وقتی «دسترسی واحدها» خالی باشد، رسانه برای همهٔ کاربران قابل دید است. کاربران می‌توانند فیلتر واحدها را روی آن اعمال کنند.',
            'rule' => 'دسترسی واحدها = خالی',
        ],
        [
            'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]',
            'icon' => 'lock',
            'label' => 'محدود به یک واحد',
            'hint' => 'وقتی فقط یک واحد انتخاب شده باشد، رسانه فقط برای کاربران همان واحد قابل دید است.',
            'rule' => 'دسترسی واحدها = یک واحد',
        ],
        [
            'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'icon' => 'groups',
            'label' => 'محدود به چند واحد',
            'hint' => 'وقتی چند واحد انتخاب شده باشد، رسانه برای کاربران همهٔ آن واحدها قابل دید است.',
            'rule' => 'دسترسی واحدها = چند واحد',
        ],
    ];

    $publishing = [
        [
            'icon' => 'bookmark',
            'label' => 'سنجاق',
            'hint' => 'رسانه‌های مهم را سنجاق کنید تا همیشه بالای فهرست کاربر بمانند؛ مناسب سری‌های آموزشی دوره‌ای.',
        ],
        [
            'icon' => 'schedule',
            'label' => 'تاریخ انتشار (انتشار تدریجی)',
            'hint' => 'موردی که تاریخ انتشارش خالی یا گذشته باشد منتشرشده است. تاریخ آینده جلسه را قفل می‌کند: در فهرست دیده می‌شود اما فایل آن تا روز انتشار در دسترس نیست.',
        ],
        [
            'icon' => 'event_busy',
            'label' => 'تاریخ انقضا',
            'hint' => 'تاریخ شمسی‌ای که پس از آن رسانه به‌صورت خودکار از صفحهٔ کاربر پنهان می‌شود (بدون حذف). خالی = بدون انقضا. ادمین همچنان می‌تواند آن را ببیند و ویرایش کند.',
        ],
        [
            'icon' => 'toggle_off',
            'label' => 'وضعیت جلسه در برابر سری',
            'hint' => 'یک جلسهٔ غیرفعال حتی در سری فعال نمایش داده نمی‌شود؛ دسترسی جلسه از راه والدش محقق می‌شود اما «وضعیت» خود جلسه هم باید فعال باشد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">lock</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">دسترسیِ رسانه: عمومی یا محدود به واحدها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        مخاطبان رسانه را در بخش «دسترسی واحدها» انتخاب کنید؛ مثل گزارشات، واحدِ موضوع رسانه با مخاطبان آن فرق دارد. برای جلسه‌های یک سری، دسترسی از سری والد خوانده می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tune</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سه حالتِ دسترسی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($modes as $m)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $m['chip'] }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $m['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $m['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $m['rule'] }}</code>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $m['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اگر کاربری رسانه‌ای را نمی‌بیند، «دسترسی واحدها»، «وضعیت»، «تاریخ انتشار» و «تاریخ انقضا» را با هم بررسی کنید.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">publish</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">کنترل‌های انتشار</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($publishing as $p)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $p['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $p['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $p['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
