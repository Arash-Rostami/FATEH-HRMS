@php
    $d1 = convertToPersian('1');
    $d4 = convertToPersian('4');
    $d90 = convertToPersian('90');

    $tierChip = fn(string $tier): string => match ($tier) {
        'endorsed' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'active'   => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
        default    => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]',
    };

    $tiers = [
        [
            'key' => 'endorsed',
            'icon' => 'military_tech',
            'label' => 'تأییدشده',
            'hint' => 'حداقل ' . $d4 . ' تأیید همکار. بالاترین سطح — با نشان طلایی. در فهرست بالاتر از «فعال» می‌آید.',
        ],
        [
            'key' => 'active',
            'icon' => 'bolt',
            'label' => 'فعال',
            'hint' => 'استفاده در ' . $d90 . ' روز اخیر. حتی با کمتر از ' . $d4 . ' تأیید هم فعال است.',
        ],
        [
            'key' => 'unused',
            'icon' => 'hourglass_empty',
            'label' => 'آمادهٔ مشارکت',
            'hint' => 'نه ' . $d4 . ' تأیید رسانده و نه در ' . $d90 . ' روز اخیر استفاده شده — پایین‌ترین سطح.',
        ],
    ];
    $rules = [
        [
            'icon' => 'rule',
            'label' => 'تفاوت «تأیید یک همکار» با سطح «تأییدشده»',
            'hint' => 'مهارت با ' . $d1 . ' تأیید همکار، «تأییدشده» حساب می‌شود، اما برای رسیدن به سطح «تأییدشده» در جدول ' . $d4 . ' تأیید لازم است. مهارتی با ' . $d1 . ' تا ' . $d4 . ' تأیید در سطح «فعال» می‌ماند — فقط ' . $d4 . ' تأیید به بالا، سطح را بالا می‌برد.',
        ],
        [
            'icon' => 'hourglass_top',
            'label' => 'کم‌فعالیت',
            'hint' => 'نشانی جدا از سطح‌ها — فقط روی ردیفِ «تأییدشده»ای که در ' . $d90 . ' روز اخیر استفاده نشده فعال می‌شود. «فعال» به‌تعریفِ اخیراً استفاده‌شده است و «آماده مشارکت» خودش عدمِ استفاده را می‌رساند، پس این پرچم فقط برای «تأییدشده» معنی دارد.',
        ],
        [
            'icon' => 'groups',
            'label' => 'برچسبِ تأیید همکاران',
            'hint' => 'بدون تأیید / تأیید یک همکار / تأیید چند همکار (از ' . $d4 . ') / تأیید چند همکار. ستون «تأیید همکاران» از ابتدا پنهان است؛ از منوی ستون‌ها نمایشش دهید.',
        ],
        [
            'icon' => 'verified_user',
            'label' => 'فقط برای تأییدشده‌ها',
            'hint' => 'ستون سطح فقط برای درخواست‌های تایید شده محاسبه می‌شود؛ درخواست «در حال بررسی» و «رد شده» خط تیره نشان می‌دهد — نه «آمادهٔ مشارکت» — تا کسی فکر نکند آن مهارت بررسی یا رد شده است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">workspace_premium</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">سطحِ مهارت فقط برای تأییدشده‌ها محاسبه می‌شود — از تأییدِ همکاران و استفادهٔ اخیر</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        ستون «سطح» در جدول سه حالت دارد که از تعداد تأیید همکاران و زمان آخرین استفاده محاسبه می‌شود و جایی ذخیره نمی‌شود. با نگه داشتن نشانگر روی هر سطح، راهنمای کوچکی همین شرایط را نشان می‌دهد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">leaderboard</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سه سطح و اولویت آن‌ها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tiers as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $tierChip($t['key']) }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                اولویت: «تأییدشده» ({{ $d4 }}+) بر «فعال» بر «آماده مشارکت» — اول شمارشِ تأیید تصمیم می‌گیرد، سپس استفادهٔ اخیر.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">rule</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">قواعد سطح‌بندی</p>
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
    </div>
</div>