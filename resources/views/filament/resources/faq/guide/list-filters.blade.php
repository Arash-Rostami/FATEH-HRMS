@php
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام پرسش‌ها — بدون فیلتر اضافه.'],
        ['label' => 'مربوط به واحد', 'hint' => 'فقط پرسش‌هایی که به یک واحد سازمانی وصل شده‌اند. تعداد روی زبانه نمایش داده می‌شود.'],
        ['label' => 'عمومی', 'hint' => 'فقط پرسش‌های بدون واحد.'],
    ];
    $filters = [
        ['label' => 'دسته‌بندی', 'hint' => 'فیلتر بر اساس دسته‌بندی؛ گزینه‌ها همان دسته‌بندی‌های ثبت‌شده‌اند.'],
        ['label' => 'واحد سازمانی', 'hint' => 'فیلتر بر اساس واحد مرتبط؛ گزینه‌ها همان واحدهای سازمانی‌اند.'],
        ['label' => 'ثبت‌کننده', 'hint' => 'فیلتر بر اساس کاربری که پرسش را ایجاد کرده است.'],
        ['label' => 'بازهٔ تاریخ ایجاد', 'hint' => 'فیلتر بازهٔ تاریخ ایجاد پرسش، با تاریخ شمسی.'],
    ];
    $groups = [
        ['label' => 'بر اساس دسته‌بندی', 'hint' => 'پرسش‌ها را به ازای دسته‌بندی موضوعی گروه‌بندی می‌کند — برای دیدن پراکندگی موضوعی.'],
        ['label' => 'بر اساس واحد', 'hint' => 'پرسش‌ها را به ازای واحد سازمانی گروه‌بندی می‌کند — واحدهای بدون پرسش با برچسب «بدون واحد» نمایش داده می‌شوند.'],
        ['label' => 'بر اساس ثبت‌کننده', 'hint' => 'پرسش‌ها را به ازای کاربرِ ثبت‌کننده گروه‌بندی می‌کند — برای دیدن سهم هر ادمین در نگهداری دانش.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">filter_alt</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">زبانه‌ها، فیلترها و گروه‌بندی فهرست</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        فهرست پرسش‌ها به‌صورت پیش‌فرض از جدیدترین به قدیمی‌ترین مرتب می‌شود. سه زبانهٔ بالای جدول، تفکیک عمومی/واحد را نشان می‌دهد و فیلترها و گروه‌بندی به شما امکان تحلیل موضوعی و سازمانی می‌دهند.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tab</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">زبانه‌های فهرست</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">tab</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_list</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلترها و گروه‌بندی</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($filters as $f)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">filter_list</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
            @foreach($groups as $g)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">folder</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $g['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $g['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                زبانه‌ها فقط وقتی تنظیم «نمایش زبانه‌های فهرست» روشن باشد دیده می‌شوند؛ اگر زبانه‌ها را نمی‌بینید، این تنظیم را در تنظیمات کاربری بررسی کنید.
            </p>
        </div>
    </div>
</div>