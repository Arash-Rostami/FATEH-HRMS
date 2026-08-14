@php
    $types = ['میز کار', 'پارکینگ', 'خودرو'];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-surface)] p-5 shadow-md shadow-[var(--md-sys-color-shadow)]/5">
        <span class="material-symbols-rounded text-[28px] text-[var(--md-sys-color-primary)] mt-0.5">power_settings_new</span>
        <div class="flex-1 flex flex-col gap-2">
            <h3 class="text-[15px] font-black text-[var(--md-sys-color-on-surface)]">قطع‌کننده‌ی نوع منبع</h3>
            <p class="text-[13px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                هر نوع منبع با یک شرط ساده روشن یا خاموش می‌شود: اگر <code class="px-2 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">window_days</code> و <code class="px-2 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">window_hours</code> هر دو صفر باشند، آن نوع کاملاً غیرفعال می‌شود. کافی است یکی از این دو بزرگ‌تر از صفر باشد تا نوع فعال بماند.
            </p>
        </div>
    </div>

    <div class="rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden">
        <div class="px-5 py-4 bg-[var(--md-sys-color-surface-container-low)] border-b border-[var(--md-sys-color-outline-variant)]">
            <h3 class="text-[14px] font-black text-[var(--md-sys-color-on-surface)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)]">toggle_on</span>
                چطور فعال کنیم
            </h3>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)] mt-0.5">check_circle</span>
                <p class="flex-1 text-[12.5px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                    مقدار <code class="px-2 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">window_days</code> را بزرگ‌تر از صفر بگذارید (مثلاً ۲۱). نوع بلافاصله در پنل کاربر فعال می‌شود و کاربر می‌تواند رزرو ثبت کند.
                </p>
            </div>
            <div class="flex items-start gap-4 p-5">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)] mt-0.5">info</span>
                <p class="flex-1 text-[12.5px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                    اگر فقط <code class="px-2 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">window_hours</code> بزرگ‌تر از صفر باشد هم نوع فعال می‌ماند؛ اما معمولاً window_days را تنظیم می‌کنید تا تقویم کاربر بازه‌ی آینده را ببیند.
                </p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden">
        <div class="px-5 py-4 bg-[var(--md-sys-color-error-container)] border-b border-[var(--md-sys-color-outline-variant)]">
            <h3 class="text-[14px] font-black text-[var(--md-sys-color-on-error-container)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[22px]">toggle_off</span>
                چطور کاملاً غیرفعال کنیم
            </h3>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-error)] mt-0.5">block</span>
                <p class="flex-1 text-[12.5px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                    هر دو فیلد <code class="px-2 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">window_days</code> و <code class="px-2 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">window_hours</code> را روی ۰ تنظیم کنید و ذخیره کنید. تب آن نوع در /reservation کم‌رنگ و قفل‌شده نشان داده می‌شود و ثبت رزرو (حتی توسط ادمین) مسدود می‌شود.
                </p>
            </div>
            <div class="flex items-start gap-4 p-5">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-error)] mt-0.5">error</span>
                <p class="flex-1 text-[12.5px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                    کاربری که سعی کند رزرو این نوع را ثبت کند، پیام <code class="px-2 py-0.5 rounded-md font-mono text-[11px] bg-red-200 text-[var(--md-sys-color-on-surface)] font-mono tracking-widest border border-[var(--md-sys-color-outline-variant)]">[ERR-022]</code> را می‌بیند.
                </p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden">
        <div class="px-5 py-4 bg-[var(--md-sys-color-surface-container-low)] border-b border-[var(--md-sys-color-outline-variant)]">
            <h3 class="text-[14px] font-black text-[var(--md-sys-color-on-surface)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                مثال عملی
            </h3>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($types as $label)
                <div class="flex items-center gap-4 p-4">
                    <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] w-20 shrink-0">{{ $label }}</span>
                    <div class="flex flex-wrap items-center gap-2 flex-1">
                        <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] font-mono">window_days = {{ convertToPersian('21') }}</span>
                        <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)]">arrow_back</span>
                        <span class="text-[11px] font-black px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">فعال</span>
                    </div>
                </div>
            @endforeach
            <div class="flex items-center gap-4 p-4">
                <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] w-20 shrink-0">ملاقات</span>
                <div class="flex flex-wrap items-center gap-2 flex-1">
                    <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] font-mono">window_days = {{ convertToPersian('0') }}</span>
                    <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] font-mono">window_hours = {{ convertToPersian('0') }}</span>
                    <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)]">arrow_back</span>
                    <span class="text-[11px] font-black px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">غیرفعال (قفل‌شده)</span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">tips_and_updates</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            نکته‌ی هوشمند: اگر کلید window_days خالی گذاشته شود، سیستم آن را ۱ فرض می‌کند (نه ۰). پس غیرفعال کردن فقط با پر کردن هر دو فیلد با عدد ۰ ممکن است؛ خالی گذاشتن نوع را خاموش نمی‌کند.
        </p>
    </div>
</div>