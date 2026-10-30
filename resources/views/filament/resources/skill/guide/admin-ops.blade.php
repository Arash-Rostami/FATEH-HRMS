@php
    $d90 = convertToPersian('90');
    $d4 = convertToPersian('4');

    $ops = [
        ['icon' => 'visibility', 'label' => 'مشاهده', 'hint' => 'دکمهٔ «مشاهده» صفحهٔ جزئیات مهارت را باز می‌کند: نام، نام انگلیسی، دسته‌بندی، نماد، وضعیت فعال، تعداد دارندگان، توضیحات و تاریخ ایجاد — همگی شمسی و راست‌چین.'],
        ['icon' => 'edit', 'label' => 'ویرایش', 'hint' => 'دکمهٔ «ویرایش» فرم مهارت را باز می‌کند: نام، نام انگلیسی، دسته‌بندی، نماد، وضعیت فعال و توضیحات. برای مهارت‌های تأمین‌نشده نمایش داده نمی‌شود؛ این مهارت‌ها فقط باید فعال شوند (به زبانهٔ «اشتراک نام و فعال‌سازی» نگاه کنید).'],
        ['icon' => 'delete', 'label' => 'حذف', 'hint' => 'دکمهٔ «حذف» مهارت را کاملاً پاک می‌کند، اما فقط وقتی دیده می‌شود که هیچ کاربری آن مهارت را نداشته باشد. در حذف گروهی هم مهارت‌هایی که دارنده دارند خودکار کنار گذاشته می‌شوند.'],
    ];

    $filters = [
        ['icon' => 'category', 'label' => 'فیلتر دسته‌بندی', 'hint' => 'فهرست دسته‌بندی‌های ثبت‌شده را می‌توانید جستجو و از میان آن‌ها انتخاب کنید.'],
        ['icon' => 'toggle_on', 'label' => 'فیلتر وضعیت فعال', 'hint' => 'فقط مهارت‌های فعال یا فقط غیرفعال را نشان می‌دهد.'],
        ['icon' => 'schedule', 'label' => 'بازهٔ تاریخ ایجاد', 'hint' => 'مهارت‌هایی را نشان می‌دهد که در بازهٔ تاریخ انتخابی ایجاد شده‌اند.'],
    ];

    $related = [
        ['icon' => 'verified_user', 'label' => 'درخواست‌های مهارت', 'hint' => 'بخش «تأیید مهارت» (در همین گروه منو) صفِ درخواست‌های کاربران است: دکمه‌های «تأیید» و «رد» روی هر مورد، و تأیید یا ردِ گروهی. تأییدِ یک درخواست، مهارت را روی حسابِ کاربر فعال می‌کند.'],
        ['icon' => 'groups', 'label' => 'مهارت‌های هر کاربر', 'hint' => 'در صفحهٔ ویرایش هر کاربر، بخش «مهارت‌ها» فقط برای مشاهده نمایش داده می‌شود: نام مهارت، وضعیت، سطح (تأییدشده / فعال / آمادهٔ مشارکت)، تعداد تأییدها، آخرین استفاده و اینکه آمادهٔ راهنمایی یا خصوصی است. فقط وقتی کاربر مهارتی دارد نمایان می‌شود.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: نگهداری فهرست مهارت‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در جدول یک مهارت است. سه دکمه کنار هر مورد قرار دارد: مشاهده، ویرایش، حذف. دکمهٔ «ساخت مهارت» بالای صفحه است. جستجوی بالای پنل هم مهارت‌ها را با نام پیدا می‌کند و شما را مستقیم به صفحهٔ ویرایش می‌برد.
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
                    <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">جدول بر اساس دسته‌بندی گروه‌بندی می‌شود و هر گروه را می‌توانید ببندید؛ تا مهارت‌های هر موضوع کنار هم دیده شوند.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">بخش‌های مرتبط</p>
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
                ستون «سطح» در بخش مهارت‌های هر کاربر سه حالت دارد: تأییدشده (حداقل {{ $d4 }} تأیید همکار)، فعال (استفاده در {{ $d90 }} روز اخیر)، آمادهٔ مشارکت (هیچ‌کدام) — خودکار محاسبه می‌شود و فقط برای مهارت‌های تأییدشده است.
            </p>
        </div>
    </div>
</div>