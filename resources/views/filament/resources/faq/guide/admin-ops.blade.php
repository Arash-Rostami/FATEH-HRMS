@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ جزئیات پرسش را باز می‌کند: پرسش و پاسخ در یک بخش، و اطلاعات ثبت (شناسه، دسته‌بندی، ثبت‌کننده، واحد، تاریخ ایجاد و ویرایش) در بخش دیگر؛ تاریخ‌ها شمسی هستند.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'دکمهٔ «ویرایش» فرم پرسش را باز می‌کند: دسته‌بندی، ثبت‌کننده و واحد در یک بخش، و پرسش و پاسخ در بخش دوم. ثبت‌کننده قفل است و قابل تغییر نیست. دسته‌بندی را می‌توانید از موارد موجود انتخاب کنید یا دسته‌بندی جدید بسازید.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'دکمهٔ «حذف» پرسش را برای همیشه پاک می‌کند و قابل بازیابی نیست. پرسش بلافاصله از پنل کاربران هم برداشته می‌شود؛ قبل از حذف مطمئن شوید دیگر به آن نیازی نیست.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'ابتدا چند پرسش را انتخاب کنید و از منوی عملیات گروهی «خروجی اکسل» را بزنید. ستون‌ها: شناسه، دسته‌بندی، سوال، پاسخ، ثبت‌کننده، واحد و تاریخ ایجاد. تاریخ‌ها شمسی هستند. خروجی گرفتن از کل فهرست فیلترشده هم ممکن است.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: نگهداریِ دانشِ سازمانی و دسته‌بندی پرسش‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در جدول یک پرسش متداول است. سه دکمهٔ عملیات روی هر ردیف موجود است (پس از سلول‌ها): مشاهده، ویرایش، حذف. دکمهٔ «ساخت پرسش» در هدر صفحه قرار دارد. جستجوی سراسری پنل نیز پرسش‌ها را با دسته‌بندی، نام واحد یا توضیحات واحد پیدا می‌کند و مستقیم به ویرایش می‌رود.
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
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                پیش از حذف توجه کنید که همهٔ پرسش‌ها، چه «عمومی» و چه مربوط به یک واحد، برای همهٔ کاربران نمایش داده می‌شوند.
            </p>
        </div>
    </div>
</div>