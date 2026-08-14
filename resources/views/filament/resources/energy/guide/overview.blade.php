@php
    $dims = [
        ['icon' => 'fitness_center', 'label' => 'جسم', 'key' => 'physique'],
        ['icon' => 'favorite', 'label' => 'احساس', 'key' => 'emotion'],
        ['icon' => 'psychology', 'label' => 'ذهن', 'key' => 'mind'],
        ['icon' => 'auto_awesome', 'label' => 'روح', 'key' => 'soul'],
    ];

    $rows = [
        ['icon' => 'trending_down', 'color' => 'error', 'label' => 'امتیاز بالاتر یعنی فرسودگی بیشتر', 'text' => 'این پرسشنامه سنجهٔ فرسودگی است، نه انرژی. هر گزینهٔ انتخابی یک «بله» محسوب می‌شود و امتیازِ بالاتر یعنی وضعیت بدتر. امتیاز کلی از ' . convertToPersian('0') . ' تا ' . convertToPersian('16') . ' است؛ ' . convertToPersian('12') . ' به‌ بالا در زبانهٔ «پرخطر» و زیر ' . convertToPersian('9') . ' در زبانهٔ «مطلوب» قرار می‌گیرد.'],
        ['icon' => 'event_repeat', 'color' => 'primary', 'label' => 'دورهٔ خنک‌سازی ' . convertToPersian('25') . ' روزه', 'text' => 'پس از تکمیل، کاربر تا ' . convertToPersian('25') . ' روز نمی‌تواند پرسشنامهٔ تازه ثبت کند. این قفل سمت سرور با lockForUpdate در یک تراکنش بررسی می‌شود، نه فقط پنهان‌سازی فرم.'],
        ['icon' => 'event', 'color' => 'tertiary', 'label' => 'بانک پرسش‌های چرخشی ماهانه', 'text' => 'برای هر بعد، ' . convertToPersian('12') . ' مجموعهٔ متفاوت پرسش وجود دارد که بر اساس ماه میلادی (اندیس ' . convertToPersian('0') . ' تا ' . convertToPersian('11') . ') انتخاب می‌شود. همین پرسش در یک ماه برای همهٔ کاربران یکسان است.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">bolt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">پرسشنامهٔ انرژی، یک سنجهٔ ماهانهٔ فرسودگی در چهار بعد است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربران پرسشنامه را از پنل خودشان در صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/energy</code> تکمیل می‌کنند. شما (ادمین) در این صفحه فقط نظارت می‌کنید — ثبت پاسخ از طرف ادمین ممکن نیست. هر ردیف این جدول یک پاسخنامهٔ تکمیل‌شدهٔ یک کاربر است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چهار بعد ارزیابی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($dims as $d)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $d['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $d['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $d['key'] }}_score</code>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">۰ تا ۴</span>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">هر بعد {{ convertToPersian('4') }} پرسش دارد. گزینهٔ آخر («هیچ‌کدام از موارد بالا») انحصاری است و در شمارش امتیاز نمی‌خورد. امتیاز بعد = تعداد گزینه‌های انتخابی غیر از گزینهٔ آخر.</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                امتیاز کلی، جمعِ امتیازِ چهار بعد است (حداکثر {{ convertToPersian('16') }}). ستون «امتیاز کلی» در جدول به‌صورت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">X / 16</code> نشان داده می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">insights</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکته‌های غیر بدیهی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $row)
                @php
                    $chipClasses = match ($row['color']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $chipClasses }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $row['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>