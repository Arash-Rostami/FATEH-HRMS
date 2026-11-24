@php
    $rows = [
        [
            'icon' => 'corporate_fare',
            'label' => 'واحدهای مالک',
            'hint' => 'واحدهای دریافت‌کنندهٔ سند را انتخاب کنید. با انتخاب «همه واحدها»، سند در دسترس کل سازمان قرار می‌گیرد.',
        ],
        [
            'icon' => 'person',
            'label' => 'کاربران اختصاصی',
            'hint' => 'در «کاربران اختصاصی»، افرادی را انتخاب کنید که باید جداگانه سند را دریافت کنند. برای مثال، می‌توانید مدیر پروژه‌ای از واحد دیگر را به مخاطبان سند اضافه کنید.',
        ],
        [
            'icon' => 'visibility',
            'label' => 'پیش‌نمایش کاربران دریافت‌کننده',
            'hint' => 'پس از انتخاب واحدها، اعضای آن‌ها در «کاربران واحدهای انتخاب‌شده» نمایش داده می‌شوند. پیش از ذخیره، این فهرست را برای بررسی مخاطبان مرور کنید.',
        ],
        [
            'icon' => 'groups',
            'label' => 'فیلتر فهرست بر اساس واحد',
            'hint' => 'فیلتر «واحد سازمانی»، اسناد همان واحد و اسناد منتشرشده برای همهٔ واحدها را نشان می‌دهد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">share</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">انتخاب دریافت‌کنندگان سند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        پس از فعال کردن سند، اعضای واحدهای انتخاب‌شده و کاربران اختصاصی آن را در کارتابل می‌بینند. وضعیت تأیید را از جزئیات سند پیگیری کنید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اجزای مالکیت</p>
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
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برای انتشار سراسری، «همه واحدها» را انتخاب کنید. برای افزودن افراد خارج از واحدهای انتخاب‌شده، از «کاربران اختصاصی» استفاده کنید.
            </p>
        </div>
    </div>
</div>
