@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'اینفولیست با دو زبانه: «اطلاعات» (کاربر، فعال، تاریخ‌های شمسی) و «محتوای آنبوردینگ» (هفت بخشِ جمع‌شونده: خوش‌آمد، مأموریت، چشم‌انداز، برنامه، ویدیوها، راهنماها، اضافی). فایل‌های ویدیو و راهنما در اینفولیست قابل دانلود/پخش هستند.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'فرم ویرایش همان ساختارِ ساخت است. هنگام ذخیره، پسوند و حجم فایل‌های راهنما از دیسک بازخوانی و ذخیره می‌شوند و رکوردهای فعالِ همان مخاطب غیرفعال می‌شوند.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'ردیفِ آنبوردینگ کاملاً حذف می‌شود. فایل‌های آپلودشده در onboarding/video، onboarding/thumbnail و onboarding/guides روی دیسک public باقی می‌مانند — حذفِ رکورد، فایل‌ها را پاک نمی‌کند.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی bulk actions روی ردیف‌های انتخاب‌شده. ستون‌ها: شناسه، کاربر، فعال (بله/خیر)، متنِ خوش‌آمد/مأموریت/چشم‌انداز/برنامه (تا ۵۰۰ کاراکترِ بدون تگ)، تعداد و عناوین ویدیوها، تعداد و عناوین راهنماها، کلیدهای extras، و تاریخ شمسی.',
        ],
    ];
    $tabs = [
        ['label' => 'همه', 'hint' => 'تمام رکوردها — بدون فیلتر. زبانهٔ پیش‌فرض.'],
        ['label' => 'فعال', 'hint' => 'فقط رکوردهای is_active = true. شمارِ فعال به‌صورت نشان روی زبانه می‌نشیند (آماره در یک کوئریِ یکجا محاسبه می‌شود).'],
        ['label' => 'غیرفعال', 'hint' => 'فقط رکوردهای غیرفعال — نسخه‌های قدیمی یا پیش‌نویس. شمارِ غیرفعال هم نشان دارد.'],
    ];
    $filters = [
        ['label' => 'فعال (سه‌حالته)', 'hint' => 'TernaryFilter روی is_active: فقط فعال / فقط غیرفعال / همه.'],
        ['label' => 'نوع مخاطب (سه‌حالته)', 'hint' => 'TernaryFilter روی audience: حالتِ true فقط اختصاصی (user_id پر)، حالتِ false فقط عمومی (user_id خالی).'],
    ];
    $groups = [
        ['label' => 'بر اساس مخاطب', 'hint' => 'گروه‌بندی روی user_id — عنوان گروه نامِ کاربر یا «همه کاربران» است. گروه‌ها قابل جمع‌شدن هستند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما در این صفحه: ساخت و نگهداری پکیج‌های آنبوردینگ</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        جدول به‌صورت پیش‌فرض بر اساس «فعال» نزولی مرتب می‌شود و ردیف‌ها راه‌راه هستند. ستون‌های اصلی (شناسه، کاربر، فعال، بخش‌های تکمیل‌شده، شمارشِ ویدیو/راهنما/اضافی) باز هستند؛ تاریخ ایجاد به‌صورت پیش‌فرض پنهان است. جستجوی سراسری پنل با نامِ کاربر رکورد را پیدا و به ویرایش می‌برد.
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
                ستون «بخش‌های تکمیل‌شده» فقط چهار فیلدِ welcome/mission/vision/schedule را برمی‌شمارد — ویدیو، راهنما و اضافی در ستون‌های شمارشِ جداگانه نمایش داده می‌شوند.
            </p>
        </div>
    </div>
</div>