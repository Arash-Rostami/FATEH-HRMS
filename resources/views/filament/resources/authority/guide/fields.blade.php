@php
    $delegation = [
        ['code' => 'decision_implementation', 'label' => 'تصمیم گیری و اجرا', 'color' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'],
        ['code' => 'review_proposal',         'label' => 'بررسی و پیشنهاد',     'color' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]'],
        ['code' => 'review_reporting',        'label' => 'بررسی و گزارش',       'color' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]'],
        ['code' => 'decision_reporting',      'label' => 'تصمیم گیری و گزارش',  'color' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]'],
    ];
    $execution = [
        ['code' => 'yes',     'label' => 'دارد',                  'color' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'],
        ['code' => 'no',      'label' => 'ندارد',                 'color' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]'],
        ['code' => 'pending', 'label' => 'در انتظار (تصویب / بروزرسانی)', 'color' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]'],
    ];
    $impact = [
        ['code' => 'very_high', 'label' => 'خیلی زیاد', 'color' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]'],
        ['code' => 'high',      'label' => 'زیاد',      'color' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'],
        ['code' => 'medium',    'label' => 'متوسط',     'color' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]'],
        ['code' => 'low',       'label' => 'کم',        'color' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]'],
    ];
    $repeat = [
        ['code' => 'yearly',         'label' => 'یک بار در سال'],
        ['code' => 'biyearly',       'label' => 'دو بار در سال'],
        ['code' => 'quarterly',      'label' => 'فصلی'],
        ['code' => '5_times_a_year', 'label' => 'پنج بار در سال'],
        ['code' => 'frequent',       'label' => 'بیشتر از ' . convertToPersian('4') . ' بار در ماه'],
        ['code' => 'regular',        'label' => 'بین ' . convertToPersian('1') . ' تا ' . convertToPersian('4') . ' بار در ماه'],
        ['code' => 'occasional',     'label' => 'کمتر از ' . convertToPersian('1') . ' بار در ماه'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">tune</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">چهار فیلدِ انتخابیِ داخلِ JSON، مقادیرِ ثابتِ پیش‌فرض دارند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        چهار فیلدِ «روش اجرایی»، «فراوانی تکرار»، «شاخص اثر» و دو فیلدِ «تفویض» همگی از نوعِ Select هستند و مقادیرِشان از یک enumِ پشتِ صحنه خوانده می‌شود. این مقادیر در جدول به‌صورت Badge و در خروجیِ اکسل به‌صورت برچسبِ فارسی نمایش داده می‌شوند. اگر رکوردی مقداری نداشته باشد، «داده نشده» جای آن می‌نشیند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">gavel</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">سطح تفویض اختیار — پیشنهادی در برابر مصوب</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5">
                <div class="flex-1 flex flex-col gap-2">
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">«تفویض پیشنهادی» سطحی است که هنوز به تأیید نرسیده، و «تفویض مصوب» سطحی است که رسماً تأیید و قابل اجراست. هر دو از همین چهار مقدار استفاده می‌کنند:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($delegation as $d)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold {{ $d['color'] }}">
                                <code class="font-mono text-[10px] opacity-70">{{ $d['code'] }}</code>
                                {{ $d['label'] }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                در پنل کاربر، نشانِ «تفویض مصوب» با همین چهار رنگ روی هر ردیف می‌نشیند؛ «تفویض پیشنهادی» فقط در منظرِ مدیریتیِ باز‌شده دیده می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">check_circle</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">روش اجرایی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5">
                <div class="flex-1 flex flex-wrap gap-2">
                    @foreach($execution as $e)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold {{ $e['color'] }}">
                            <code class="font-mono text-[10px] opacity-70">{{ $e['code'] }}</code>
                            {{ $e['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">trending_up</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">شاخص اثر</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5">
                <div class="flex-1 flex flex-wrap gap-2">
                    @foreach($impact as $i)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[11px] font-bold {{ $i['color'] }}">
                            <code class="font-mono text-[10px] opacity-70">{{ $i['code'] }}</code>
                            {{ $i['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">event_repeat</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فراوانی تکرار</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($repeat as $r)
                <div class="flex items-center gap-3 p-3.5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $r['code'] }}</code>
                    <p class="text-[12px] font-medium text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>