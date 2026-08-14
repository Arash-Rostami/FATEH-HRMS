@php
    $rows = [
        [
            'icon' => 'lightbulb',
            'label' => 'پیشنهاد چیست؟',
            'hint' => 'یک ایده یا طرح بهبود است که یک کاربر (غیر از مدیریت ارشد) ثبت می‌کند، واحدهای ذی‌نفع دربارهٔ آن بازخورد می‌دهند و در نهایت مدیریت ارشد (واحد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">MA</code>) تصمیم نهایی می‌گیرد: پذیرش، رد یا درخواست تکمیل مجدد.',
        ],
        [
            'icon' => 'tag',
            'label' => 'شناسهٔ یکتا (Serial)',
            'hint' => 'هر پیشنهاد یک شناسهٔ نمایشی به‌صورت <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">SN-YYYYMMDD-NNNNNN</code> می‌گیرد — ترکیب تاریخ ثبت و شناسهٔ ردیف. جستجوی سراسری و جستجوی ستون «شناسه» هر دو این شناسه را می‌شناسند (جستجو با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" dir="ltr">SN-</code> هم کار می‌کند).',
        ],
        [
            'icon' => 'visibility',
            'label' => 'دید ادمین در برابر کاربر',
            'hint' => 'این ماژول دوطرفه است. کاربر از صفحهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">/suggestion</code> پیشنهاد می‌سازد و بازخورد می‌دهد؛ ادمین در همین صفحهٔ فیلامنت روی همهٔ پیشنهادها نظارت کل‌سازمان دارد. زبانهٔ «تجربهٔ کاربر» نشان می‌دهد کاربر در پنل خودش چه چیزی می‌بیند.',
        ],
        [
            'icon' => 'block',
            'label' => 'مدیریت ارشد نمی‌تواند ثبت‌کننده باشد',
            'hint' => 'کاربران واحد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">MA</code> مجاز به ثبت پیشنهاد نیستند — هم یک قانون اعتبارسنجی (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">SuggestionSubmitterNotFromMa</code>) و هم یک نگهبان در اکشن ساخت این محدودیت را اجرا می‌کنند. فیلد «ثبت‌کننده» در فرم ادمین قفل‌شده و پیش‌فرض روی کاربر جاری است.',
        ],
        [
            'icon' => 'edit_note',
            'label' => 'تکمیل شخصی (self_fill)',
            'hint' => 'وقتی این پرچم روشن باشد، ثبت‌کننده می‌تواند بازخورد همهٔ واحدهای ذی‌نفع را هنگام ساخت پیش‌فرض پر کند. در این حالت برای هر واحد ذی‌نفع یک ردیف بررسی (Review) در زمان ساخت ساخته می‌شود؛ در غیر این صورت فقط ردیف بررسیِ واحدِ ثبت‌کننده ساخته می‌شود.',
        ],
        [
            'icon' => 'speed',
            'label' => 'شمارش از پیش محاسبه‌شده',
            'hint' => 'کوئری پایهٔ این منبع <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">withReviewCounts</code> را می‌زند تا شمارش موافق/نیمه‌موافق/مخالف به‌صورت ستون‌های مجزا بیاید — ستون‌های جدول این سه رقم را از همان کوئری می‌خوانند، نه با یک کوئری جداگانه در هر ردیف (بدون N+1).',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«پیشنهاد» یک درخواست بهبود فرآیند است که مسیر بازخورد واحد‌ها و تصمیم مدیریت ارشد را طی می‌کند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما (ادمین) همهٔ پیشنهادهای سازمان را می‌بینید: شناسه، مرحله، ثبت‌کننده، شمارش بازخوردها و پیوست. تصمیم نهایی و ارجاع برای اقدام از همین صفحه. کاربران پیشنهاد را در پنل خودشان ثبت می‌کنند و بازخورد می‌دهند — ساخت پیشنهاد از طرف ادمین کارِ این صفحه نیست.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">lightbulb</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">مفاهیم پایه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $r['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">tips_and_updates</span>
                مرحلهٔ پیشنهاد را ادمین دستی عوض نمی‌کند — <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">syncStage</code> خودکار بر اساس بازخوردهای ثبت‌شده مرحله را محاسبه می‌کند (جزئیات در زبانهٔ «چرخهٔ بررسی»).
            </p>
        </div>
    </div>
</div>