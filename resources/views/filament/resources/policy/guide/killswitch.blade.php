@php
    $types = ['میز کار', 'پارکینگ', 'خودرو'];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-surface)] p-5 shadow-md shadow-[var(--md-sys-color-shadow)]/5">
        <span class="material-symbols-rounded text-[28px] text-[var(--md-sys-color-primary)] mt-0.5">power_settings_new</span>
        <div class="flex-1 flex flex-col gap-2">
            <h3 class="text-[15px] font-black text-[var(--md-sys-color-on-surface)]">روشن و خاموش کردن یک نوع منبع</h3>
            <p class="text-[13px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                اگر «بازه مجاز پیش‌رزرو (روز)» و «حداقل زمان قبل از رزرو (ساعت)» هر دو صفر باشند، آن نوع منبع غیرفعال می‌شود. اگر یکی از آن‌ها بزرگ‌تر از صفر باشد، نوع فعال می‌ماند.
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
                    «بازه مجاز پیش‌رزرو (روز)» را بزرگ‌تر از صفر بگذارید؛ مثلاً ۲۱. نوع در پنل کاربر فعال می‌شود و کاربر می‌تواند رزرو ثبت کند.
                </p>
            </div>
            <div class="flex items-start gap-4 p-5">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)] mt-0.5">info</span>
                <p class="flex-1 text-[12.5px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                    اگر «حداقل زمان قبل از رزرو» بیشتر از صفر باشد، این نوع منبع فعال می‌ماند. برای مشخص کردن چند روزِ قابل رزرو در تقویم کاربر، «بازه مجاز پیش‌رزرو» را تعیین کنید.
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
                    «بازه مجاز پیش‌رزرو» و «حداقل زمان قبل از رزرو» را روی صفر بگذارید و ذخیره کنید. زبانهٔ این نوع منبع در پنل کاربر قفل می‌شود و ثبت رزرو برای مدیر سامانه هم بسته می‌شود.
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
                        <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]">بازهٔ پیش‌رزرو ۲۱ روز</span>
                        <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)]">arrow_back</span>
                        <span class="text-[11px] font-black px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">فعال</span>
                    </div>
                </div>
            @endforeach
            <div class="flex items-center gap-4 p-4">
                <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] w-20 shrink-0">ملاقات</span>
                <div class="flex flex-wrap items-center gap-2 flex-1">
                    <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]">بازهٔ پیش‌رزرو صفر روز</span>
                    <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]">حداقل زمان قبل از رزرو صفر ساعت</span>
                    <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)]">arrow_back</span>
                    <span class="text-[11px] font-black px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">غیرفعال (قفل‌شده)</span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">tips_and_updates</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            نکته: اگر «بازهٔ مجاز پیش‌رزرو» خالی بماند، سیستم آن را ۱ فرض می‌کند (نه ۰). پس برای خاموش کردن یک نوع باید هر دو فیلد را با عدد ۰ پر کنید؛ خالی گذاشتن آن را خاموش نمی‌کند.
        </p>
    </div>
</div>
