@php
    $items = [
        [
            'icon' => 'inventory_2',
            'text' => 'برای هر نوعی که قرار است رزرو شود، حداقل یک منبع «فعال» ساخته باشید. منبع غیرفعال در صفحهٔ کاربر ظاهر نمی‌شود.',
        ],
        [
            'icon' => 'person',
            'text' => 'نام منابع «ملاقات» دقیقاً با نام یک کاربر واقعی همخوانی داشته باشد؛ وگرنه دعوتنامهٔ تقویم ساخته نمی‌شود.',
        ],
        [
            'icon' => 'key',
            'text' => 'پرچم booking روی کاربرانی که قرار است رزرو کنند تنظیم شده باشد — {"all": true} یا به‌ازای هر نوع مجاز. بدون این پرچم، صفحهٔ رزرو خالی است.',
        ],
        [
            'icon' => 'event_available',
            'text' => 'window_days برای هر نوع فعال، بزرگ‌تر از صفر باشد (معمولاً ۲۱). اگر صفر باشد، آن زبانه برای کاربر غیرفعال نمایش داده می‌شود.',
        ],
        [
            'icon' => 'layers',
            'text' => 'برای میز کار و پارکینگ، طبقه را وارد کرده باشید تا فیلتر طبقه در صفحهٔ رزرو کار کند.',
        ],
        [
            'icon' => 'image',
            'text' => 'برای منابع میز/پارکینگ/خودرو تصویر دلخواه بگذارید؛ برای ملاقات، اگر تصویر نگذارید تصویر پروفایل شخص جایگزین می‌شود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">checklist</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">چک‌لیست پیش از اولین رزرو</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        قبل از اینکه اولین کاربر رزرو کند، این شش مورد را مرور کنید. اگر همه تیک خورده باشند، سامانه آماده است و کاربردی برای عیب‌یابی باقی نمی‌ماند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">rule</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">موارد الزامی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($items as $i => $item)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] text-[11px] font-black">{{ convertToPersian((string) ($i + 1)) }}</span>
                        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">{{ $item['icon'] }}</span>
                    </div>
                    <p class="flex-1 text-[12.5px] text-[var(--md-sys-color-on-surface)] leading-7 font-medium">{{ $item['text'] }}</p>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">rocket_launch</span>
                همه تیک خورده؟ کاربر به /reservation می‌رود، زبانهٔ نوع را باز می‌کند، تاریخ را از تقویم برمی‌گزیند و روی کارت منبعِ موجود «رزرو» را می‌زند.
            </p>
        </div>
    </div>
</div>