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
            'label' => 'تأییدشده (Endorsed)',
            'hint' => 'حداقل ' . $d4 . ' تأیید همکار. بالاترین سطح — نشانِ طلایی. ترتیب در فهرست بالاتر از «فعال».',
        ],
        [
            'key' => 'active',
            'icon' => 'bolt',
            'label' => 'فعال (Active)',
            'hint' => 'استفاده در ' . $d90 . ' روز اخیر (last_used_at). حتی با کمتر از ' . $d4 . ' تأیید هم فعال است.',
        ],
        [
            'key' => 'unused',
            'icon' => 'hourglass_empty',
            'label' => 'آماده مشارکت (Unused)',
            'hint' => 'نه ' . $d4 . ' تأیید رسانده و نه در ' . $d90 . ' روز اخیر استفاده شده — پایین‌ترین سطح.',
        ],
    ];
    $rules = [
        [
            'icon' => 'rule',
            'label' => 'آستانه vs سقف اشباع',
            'hint' => 'آستانهٔ تأیید ' . $d1 . ' است (isEndorsed) ولی سقفِ سطحِ «تأییدشده» ' . $d4 . ' است (ENDORSEMENT_SATURATION_CAP). مهارتی با ' . $d1 . ' تا ' . $d4 . ' تأیید در سطحِ «فعال» می‌ماند — فقط ' . $d4 . ' تأیید به بالا سطحِ بالا می‌آورد.',
        ],
        [
            'icon' => 'hourglass_top',
            'label' => 'کم‌فعالیت (Dormant)',
            'hint' => 'پرچمی جدا از سطح‌ها — فقط روی ردیفِ «تأییدشده»ای که در ' . $d90 . ' روز اخیر استفاده نشده فعال می‌شود. «فعال» به‌تعریفِ اخیراً استفاده‌شده است و «آماده مشارکت» خودش عدمِ استفاده را می‌رساند، پس این پرچم فقط برای «تأییدشده» معنی دارد.',
        ],
        [
            'icon' => 'groups',
            'label' => 'برچسبِ تأیید همکاران',
            'hint' => 'بدون تأیید / تأیید یک همکار / تأیید N همکار (از ' . $d4 . ') / تأیید N همکار. ستونِ endorsements_count پیش‌فرض پنهان است؛ از تنظیمات ستون بازش کنید.',
        ],
        [
            'icon' => 'verified_user',
            'label' => 'فقط برای تأییدشده‌ها',
            'hint' => 'ستونِ سطح فقط برای درخواست‌های Approved محاسبه می‌شود؛ ردیفِ «در حال بررسی» و «رد شده» خط تیره نشان می‌دهد — نه نشانِ «آماده مشارکت» — تا خواننده فکر نکند بررسی و رد شده است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">workspace_premium</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">سطحِ مهارت فقط برای تأییدشده‌ها محاسبه می‌شود — از تأییدِ همکاران و استفادهٔ اخیر</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        ستونِ «سطح» در جدول، سه حالت دارد که از endorsements_count و last_used_at مشتق می‌شود — هیچ‌جا ذخیره نمی‌شود. روی هر ستونِ سطح در جدول، راهنمای کوچک (tooltip) همان آستانه‌ها را نشان می‌دهد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">leaderboard</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سه سطح و پیشینیِ آنها</p>
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
                پیشینی: «تأییدشده» ({{ $d4 }}+) بر «فعال» بر «آماده مشارکت» — اول شمارشِ تأیید تصمیم می‌گیرد، سپس استفادهٔ اخیر.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">rule</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">قواعدِ سطح‌بندی</p>
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