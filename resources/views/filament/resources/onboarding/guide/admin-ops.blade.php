@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'صفحهٔ مشاهده هشت زبانه دارد: «اطلاعات» (کاربر، فعال، تاریخ‌ها) و یک زبانه برای هر بخش محتوا (خوش‌آمد، مأموریت، چشم‌انداز، برنامه، ویدیوها، راهنماها، بخش‌های دیگر)، مثل زبانه‌های فرم. فایل‌های ویدیو و راهنما در صفحهٔ جزئیات قابل دانلود/پخش هستند.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'فرم ویرایش همان ساختارِ ساخت است. هنگام ذخیره، نوع و حجم فایل‌های راهنما خودکار ثبت می‌شود و موارد فعال دیگرِ همان مخاطب غیرفعال می‌شوند.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'این مورد کاملاً حذف می‌شود و فایل‌های بارگذاری‌شدهٔ آن (ویدیو، تصویر پیش‌نمایش و راهنما) هم همزمان پاک می‌شوند.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی کارهای گروهی روی موارد انتخاب‌شده. ستون‌ها: شناسه، کاربر، فعال (بله/خیر)، متنِ خوش‌آمد/مأموریت/چشم‌انداز/برنامه (تا ۵۰۰ کاراکترِ بدون تگ)، تعداد و عناوین ویدیوها، تعداد و عناوین راهنماها، نام بخش‌های دیگر، و تاریخ شمسی.',
        ],
    ];
    $tabs = [
        ['label' => 'همه', 'hint' => 'همهٔ موارد، بدون فیلتر. زبانهٔ پیش‌فرض.'],
        ['label' => 'فعال', 'hint' => 'فقط موارد فعال. تعداد آن‌ها روی زبانه نشان داده می‌شود.'],
        ['label' => 'غیرفعال', 'hint' => 'فقط موارد غیرفعال، مثل نسخه‌های قدیمی یا پیش‌نویس. تعداد آن‌ها هم روی زبانه نشان داده می‌شود.'],
    ];
    $filters = [
        ['label' => 'فعال (سه‌حالته)', 'hint' => 'فقط فعال، فقط غیرفعال، یا همه.'],
        ['label' => 'مخاطب (سه‌حالته)', 'hint' => 'فقط مخصوص یک کاربر، یا فقط همهٔ کاربران، یا هر دو.'],
    ];
    $groups = [
        ['label' => 'بر اساس مخاطب', 'hint' => 'موارد را بر اساس مخاطب دسته می‌کند؛ عنوان هر دسته نام کاربر یا «همهٔ کاربران» است. گروه‌ها قابل جمع‌شدن هستند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: ساخت و نگهداری پکیج‌های آنبوردینگ</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        جدول به‌طور پیش‌فرض موارد فعال را بالاتر نشان می‌دهد. ستون‌های اصلی (شناسه، کاربر، فعال، بخش‌های تکمیل‌شده، شمارشِ ویدیو/راهنما/اضافی) باز هستند؛ تاریخ ایجاد به‌صورت پیش‌فرض پنهان است. جستجوی سراسری پنل با نام کاربر، مورد را پیدا می‌کند و به صفحهٔ ویرایش می‌برد.
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
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">filter_alt</span>
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
                ستون «بخش‌های تکمیل‌شده» فقط چهار بخش خوش‌آمدگویی، مأموریت، چشم‌انداز و برنامهٔ زمانی را می‌شمارد — ویدیو، راهنما و اضافی در ستون‌های شمارشِ جداگانه نمایش داده می‌شوند.
            </p>
        </div>
    </div>
</div>