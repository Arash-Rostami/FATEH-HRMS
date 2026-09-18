@php
    $hosts = [
        ['icon' => 'dashboard', 'label' => 'برد وظایف', 'hint' => 'دکمهٔ یادآوری کنار زبانه‌های فرم ویرایش وظیفه، در حالت ویرایش نمایش داده می‌شود.'],
        ['icon' => 'workspaces', 'label' => 'پروژه‌ها', 'hint' => 'دکمهٔ یادآوری کنار دکمه‌های تمام‌صفحه/ویرایش در سربرگ پروژه.'],
        ['icon' => 'support_agent', 'label' => 'تیکت پشتیبانی', 'hint' => 'دکمهٔ یادآوری بالای کارت اطلاعات تیکت، در فضای کاری تیکت.'],
        ['icon' => 'description', 'label' => 'اسناد (DMS)', 'hint' => 'دکمهٔ یادآوری روی هر ردیف سند در جدول اسناد.'],
        ['icon' => 'event', 'label' => 'رزرو منابع', 'hint' => 'دکمهٔ یادآوری در تاریخچهٔ رزرو.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">visibility</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کاربر در پنل خود چه می‌بیند؟</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربر یک زنگ سراسری (<span class="material-symbols-rounded text-[14px] align-middle">alarm</span>) در نوار بالای هر صفحه دارد که همهٔ یادآوری‌های خودش را نشان می‌دهد — چه به رکورد خاصی وصل باشند چه یک یادداشت آزاد. علاوه بر آن، روی پنج نوع رکورد، یک دکمهٔ یادآوریِ مقیاس‌شده به همان رکورد هم وجود دارد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">category</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">پنج رکورد میزبان یادآوری مقیاس‌شده</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($hosts as $h)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $h['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $h['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $h['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">schedule</span>
                یادآوری مقیاس‌شده به یک رکورد، فقط برای همان کاربری که ساخته نمایش داده می‌شود — دو کاربر روی یک رکورد مشترک، یادآوری یکدیگر را نمی‌بینند.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">edit</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">مودال یادآوری — دو زبانه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                    <span class="material-symbols-rounded text-[22px]">list</span>
                </span>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">زبانهٔ «لیست»</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">فیلترهای «همه فعال / امروز / دیرکرد / این هفته» و جدولی از یادآوری‌ها با اقدامات انجام‌شد، به‌تعویق‌انداختن، ویرایش، توقف تکرار و حذف.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                    <span class="material-symbols-rounded text-[22px]">add_circle</span>
                </span>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">زبانهٔ «یادآوری جدید»</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">عنوان (با پیشنهاد خودکار از عناوین پیشین کاربر)، یادداشت، تاریخ و ساعت سررسید، تکرار و چهار کانال اعلان.</p>
                </div>
            </div>
        </div>
    </div>
</div>
