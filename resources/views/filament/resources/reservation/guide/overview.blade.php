@php
    $types = [
        ['icon' => 'desk', 'label' => 'میز کار', 'code' => 'seat', 'full' => true],
        ['icon' => 'local_parking', 'label' => 'پارکینگ', 'code' => 'spot', 'full' => true],
        ['icon' => 'directions_car', 'label' => 'خودرو', 'code' => 'car', 'full' => true],
        ['icon' => 'person', 'label' => 'ملاقات', 'code' => 'meeting', 'full' => false],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«رزرو» یعنی یک کاربر یک منبع را برای یک بازهٔ زمانی اشغال کرده است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربران رزرو را از صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/reservation</code> در پنل کاربری ثبت می‌کنند — زبانهٔ نوع منبع را باز می‌کنند، کارت یکی از منابعِ موجود را انتخاب می‌کنند و دکمهٔ «رزرو» را می‌زنند. شما (ادمین) در این صفحه فقط نظارت دارید: کل رزروهای سازمان را می‌بینید، می‌توانید لغو یا آزاد کنید، و خروجی اکسل بگیرید. ساخت رزرو از طرف کاربر کارِ این صفحه نیست؛ اینجا صرفاً دیدگاه مدیریتی است.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">calendar_today</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">هر ردیف چیست؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">event</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">یک رزرو مستقل</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">معمولاً هر ردیف یک رزروی تک‌روز است: یک کاربر + یک منبع + یک بازهٔ زمانی. ستون «سری» در جدول مقدار «مستقل» نشان می‌دهد.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">repeat</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">یک سری تکرارشونده</p>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                            <span class="material-symbols-rounded text-[12px]">link</span> parent_id
                        </span>
                    </div>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">وقتی کاربر «رزرو تکراری» را روشن می‌کند، یک رزرو «اصلی» (master) ثبت می‌شود و چند رزرو «تکرار» (occurrence) با فیلد <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">parent_id</code> که به آیدی رزرو اصلی اشاره می‌کند. در جدول ستون «سری» روی «تکراری» می‌نشیند. روی صفحهٔ ویرایش یک رزرو اصلی، مدیریت ارتباط «تکرارها» (OccurrencesRelationManager) زیرِ صفحه ظاهر می‌شود.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">category</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">نوع منبع تعیین می‌کند رزرو ساعتی است یا تمام‌روز</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($types as $t)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $t['code'] }}</code>
                            @if($t['full'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                                    <span class="material-symbols-rounded text-[12px]">event_available</span> تمام‌روز
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                                    <span class="material-symbols-rounded text-[12px]">schedule</span> ساعتی
                                </span>
                            @endif
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">@if($t['full']) فقط تاریخ انتخاب می‌شود؛ کل روز آن منبع رزرو می‌گردد. @else ساعت شروع و پایان انتخاب می‌شود — تنها نوع ساعتی. @endif</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                جدول این صفحه رزروهای همهٔ نوع‌ها را در یک فهرست نشان می‌دهد؛ با فیلتر «نوع منبع» یا گروه‌بندی بر اساس منبع می‌توانید جدا کنید.
            </p>
        </div>
    </div>
</div>