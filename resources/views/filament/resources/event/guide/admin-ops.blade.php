@php
    $ops = [
        [
            'icon' => 'add_circle',
            'label' => 'ساخت رویداد',
            'hint' => 'دکمهٔ «رویداد جدید» در بالا. فیلد تاریخ به‌صورت شمسی وارد می‌شود و ساعت با TimePicker. اگر خصوصی را فعال کنید، فیلد «مخاطب» ظاهر و الزامی می‌شود. هنگام ذخیره، زیرکلیدهای شمارش معکوس به‌صورت JSON در ستون `countdown` بسته‌بندی می‌شوند.',
        ],
        [
            'icon' => 'edit',
            'label' => 'مشاهده و ویرایش',
            'hint' => 'دکمهٔ «مشاهده» اینفولیست را باز می‌کند (عنوان، توضیحات، تاریخ، سازنده، مرئیت، یادآوری و وضعیت شمارش معکوس). دکمهٔ «ویرایش» فرم را باز می‌کند — هنگام پر شدن فرم، JSON شمارش معکوس به فیلدهای مجزا باز می‌شود.',
        ],
        [
            'icon' => 'group',
            'label' => 'مدیریت ارتباط اشتراک‌ها',
            'hint' => 'روی صفحهٔ ویرایش، مدیریت ارتباط «اشتراک‌های این رویداد» فقط وقتی ظاهر می‌شود که رویداد حداقل یک اشتراک داشته باشد. این مدیریت فقط‌خواندنی است — ستون‌های گیرنده، اشتراک‌گذار و تاریخ اشتراک‌گذاری را نشان می‌دهد. اکشن «لغو اشتراک» (با تأیید) یک اشتراک را حذف می‌کند و فقط اگر دسترسی `deleteAny` داشته باشید نمایش داده می‌شود.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف رویداد',
            'hint' => 'دکمهٔ «حذف» روی هر ردیف و در اکشن‌های گروهی. حذف یک ردیف، کش `countdown:active` را هم پاک می‌کند. توجه: حذفِ رویدادی که از طریق رزرو ساخته شده، آن را تا همگام‌سازی بعدیِ همان رزرو پنهان می‌کند — برای لغو دائمی باید رزرو را لغو کنید.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'با اکشن گروهی «خروجی Excel» می‌توانید رویدادهای انتخاب‌شده را صادر کنید — شامل ID، عنوان، توضیحات، سازنده، تاریخ (شمسی)، مرئیت و تاریخ ایجاد.',
        ],
        [
            'icon' => 'search',
            'label' => 'جستجوی سراسری',
            'hint' => 'رویدادها در جستجوی سراسری پنل بر اساس عنوان، توضیحات و نام سازنده قابل جستجو هستند. نتیجهٔ جستجو سازنده و تاریخ شمسی را نشان می‌دهد و با کلیک به صفحهٔ ویرایش می‌رود.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات مدیریتی شما روی رویدادها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما می‌توانید رویدادها را بسازید، ویرایش و حذف کنید، اشتراک‌ها را بازبینی و لغو کنید و خروجی اکسل بگیرید. رویدادهای ساخته‌شده از طریق رزرواسیون با علامت «جلسه برنامه‌ریزی شده از طریق سیستم رزرواسیون #<id>» در توضیحات قابل شناسایی‌اند — برای تغییر یا لغو دائمی آن‌ها باید از ماژول رزرو اقدام کنید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اکشن‌های ردیف و مدیریت ارتباط</p>
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
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $op['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                کاربر در پنل خود نمی‌تواند رویدادِ رزرو را ویرایش یا حذف کند — ولی ادمین از این صفحه می‌تواند. اگر چنین رویدادی را حذف کنید، رزرو در همگام‌سازی بعدی آن را دوباره می‌سازد؛ برای حذف دائمی، رزرو را لغو کنید.
            </p>
        </div>
    </div>
</div>