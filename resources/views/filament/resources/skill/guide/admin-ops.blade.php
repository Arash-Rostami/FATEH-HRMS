@php
    $d90 = convertToPersian('90');
    $d4 = convertToPersian('4');

    $ops = [
        ['icon' => 'visibility', 'label' => 'مشاهده', 'hint' => 'دکمهٔ «مشاهده» صفحهٔ اینفولیست مهارت را باز می‌کند: نام، نام انگلیسی، دسته‌بندی، نماد، وضعیت فعال، تعداد دارندگان، توضیحات و تاریخ ایجاد — همگی شمسی و راست‌چین.'],
        ['icon' => 'edit', 'label' => 'ویرایش', 'hint' => 'دکمهٔ «ویرایش» فرم مهارت را باز می‌کند: نام، نام انگلیسی، دسته‌بندی، نماد، وضعیت فعال و توضیحات. برای رکوردهای ghost مخفی است — ghost فقط باید فعال شود (see زبانهٔ «اشتراک نام»).'],
        ['icon' => 'delete', 'label' => 'حذف', 'hint' => 'دکمهٔ «حذف» رکورد مهارت را کاملاً برمی‌دارد، اما فقط وقتی هیچ کاربری آن مهارت را ندارد (skill_users_count = ۰) ظاهر می‌شود. حذفِ گروهی نیز خودکار رکوردهای دارایِ دارندگان را فیلتر می‌کند.'],
    ];

    $filters = [
        ['icon' => 'category', 'label' => 'فیلتر دسته‌بندی', 'hint' => 'فیلترِ قابل‌جستجو از روی مقادیرِ متمایزِ دسته‌بندیِ مهارت‌ها.'],
        ['icon' => 'toggle_on', 'label' => 'فیلتر وضعیت فعال', 'hint' => 'فیلتر دوحالته: فقط فعال / فقط غیرفعال.'],
        ['icon' => 'schedule', 'label' => 'بازهٔ تاریخ ایجاد', 'hint' => 'فیلترِ بازهٔ تاریخ ایجادِ مهارت (مشترکِ همهٔ منابع).'],
    ];

    $related = [
        ['icon' => 'verified_user', 'label' => 'درخواست‌های مهارت', 'hint' => 'منبعِ SkillRequestResource (همان گروهِ ناوبری) صفِ درخواست‌های کاربران است: اکشن‌های «تأیید» و «رد» روی هر ردیف، و تأیید/ردِ گروهی. تأییدِ یک درخواست، مهارت را روی حسابِ کاربر فعال می‌کند.'],
        ['icon' => 'groups', 'label' => 'مهارت‌های هر کاربر', 'hint' => 'زیرِ صفحهٔ ویرایشِ هر کاربر، مدیریت ارتباط «مهارت‌ها» فقط‌خواندنی ظاهر می‌شود — نام مهارت، وضعیت، سطح (Endorsed/Active/Unused)، تعداد تأییدها، آخرین استفاده، پرچمِ راهنمایی و خصوصی. فقط وقتی کاربر مهارتی دارد نمایان می‌شود.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: نگهداریِ کاتالوگ مهارت‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در جدول یک مهارت است. سه دکمهٔ عملیات روی هر ردیف (پس از سلول‌ها) قرار دارد: مشاهده، ویرایش، حذف. دکمهٔ «ساخت مهارت» در هدرِ صفحه است. جستجوی سراسریِ پنل نیز مهارت‌ها را با نام پیدا می‌کند و مستقیم به ویرایش می‌رود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات روی هر ردیف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ops as $op)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $op['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $op['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $op['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها و گروه‌بندی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">{{ $f['icon'] }}</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
            <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">folder</span>
                </div>
                <div class="flex-1 flex flex-col gap-0.5">
                    <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">گروه‌بندی بر اساس دسته‌بندی</p>
                    <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">گروه‌بندیِ قابلِ جمع‌شدنِ جدول بر اساس فیلد category — برای دیدنِ مهارت‌های هر موضوع کنارِ هم.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">سطوحِ مدیریتیِ مرتبط</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($related as $r)
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                ستونِ «سطح» در مدیریت ارتباطِ مهارتِ کاربر سه حالت دارد: Endorsed (حداقل {{ $d4 }} تأیید همکار)، Active (استفاده در {{ $d90 }} روز اخیر)، Unused (هیچ‌کدام) — خودکار و فقط برای مهارت‌های تأییدشده.
            </p>
        </div>
    </div>
</div>