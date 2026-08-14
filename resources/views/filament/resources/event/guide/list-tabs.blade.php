@php
    $tabs = [
        ['icon' => 'list', 'label' => 'همه', 'hint' => 'بدون فیلتر اضافی — همهٔ رویدادها.'],
        ['icon' => 'schedule', 'label' => 'پیش رو', 'hint' => 'رویدادهایی که `date >= NOW()` است. دارای badge سبز با شمارش رویدادهای آینده.'],
        ['icon' => 'history', 'label' => 'گذشته', 'hint' => 'رویدادهایی که `date < NOW()` است. دارای badge خاکستری.'],
        ['icon' => 'public', 'label' => 'عمومی', 'hint' => 'فقط رویدادهای `private = 0`. دارای badge آبی.'],
        ['icon' => 'lock', 'label' => 'خصوصی', 'hint' => 'فقط رویدادهای `private = 1`. دارای badge هشدار.'],
    ];

    $filters = [
        ['icon' => 'person', 'label' => 'فیلتر مخاطب/سازنده', 'hint' => 'SelectFilter روی `user_id` با جستجو و preload — رویدادهای یک کاربر خاص (سازنده یا مخاطب خصوصی) را فیلتر می‌کند.'],
        ['icon' => 'lock', 'label' => 'فیلتر مرئیت (Ternary)', 'hint' => 'سه‌حالته: همه / فقط خصوصی / فقط عمومی. روی ستون `private` اعمال می‌شود.'],
        ['icon' => 'schedule', 'label' => 'فیلتر رویدادهای آینده', 'hint' => 'یک toggle که `date >= NOW()` را اعمال می‌کند — معادل زبانهٔ «پیش رو» ولی به‌صورت فیلتر.'],
        ['icon' => 'calendar_month', 'label' => 'فیلتر بازه تاریخ', 'hint' => 'دو DatePicker «از تاریخ» و «تا تاریخ» — بازه‌ای روی `date` اعمال می‌کند. تاریخ‌ها میلادی هستند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_list</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">فهرست رویدادها با پنج زبانهٔ ازپیش‌تعریف‌شده و چهار فیلتر</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        زبانه‌های فهرست با یک کوئری آماری واحد شمارش خود را محاسبه می‌کنند و badge هر زبانه از همان کوئری می‌آید — نه کوئری جداگانه‌ای به‌ازای هر زبانه. زبانه‌ها با تنظیمات نمایش کاربر قابل خاموش شدن هستند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">view_carousel</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های فهرست</p>
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
                badgeهای زبانه از یک کوئری `selectRaw` واحد با چهار `SUM(CASE WHEN …)` می‌آیند — چهار شمارش با یک اسکن. اگر کاربر زبانه‌ها را در تنظیمات نمایش خاموش کند، همهٔ زبانه‌ها پنهان می‌شوند و فهرست بدون زبانه نمایش داده می‌شود.
            </p>
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
            <div class="flex items-start gap-4 p-5">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">splitscreen</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">گروه‌بندی مرئیت</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">رویدادها را بر اساس `private` گروه‌بندی می‌کند (خصوصی / عمومی) — گروه‌های قابل جمع‌شدن.</p>
                </div>
            </div>
        </div>
    </div>
</div>