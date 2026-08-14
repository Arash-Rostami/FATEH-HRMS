@php
    $ops = [
        [
            'icon' => 'view_carousel',
            'label' => 'ساخت و ویرایش فید',
            'hint' => 'فرم دارای دو بخش است: «محتوا» با ویرایشگر غنی (رنگ متن سفارشی، تیتر، هم‌تراز، فهرست، نقل‌قول، هایلایت، پیوند) و «نویسنده» با انتخاب کاربر، دسته و رسانه. فیلد نویسنده پیش‌فرض ادمین است و در ویرایش غیرفعال می‌شود.',
        ],
        [
            'icon' => 'ballot',
            'label' => 'نظرسنجی و بسته‌بندی تنظیمات',
            'hint' => 'وقتی دسته «نظرسنجی» انتخاب شود، شبکهٔ تنظیمات (تک‌انتخابی/چندانتخابی + فعال بودن نظر و واکنش) و repeater گزینه‌ها (حداقل ۲، حداکثر ۱۰) ظاهر می‌شوند. هنگام ذخیره، تنظیمات و گزینه‌ها در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">poll_options</code> به‌صورت یک آرایه بسته‌بندی می‌شوند و هنگام پر کردن فرم، دوباره از هم باز می‌شوند.',
        ],
        [
            'icon' => 'photo_library',
            'label' => 'رسانه: تصویر و ویدیو',
            'hint' => 'بخش تصویر تا ۸ پرونده (۱۰MB هرکدام، فقط image/*) و بخش ویدیو یک پرونده (۱۰۰MB). تصاویر در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">feed/image</code> و ویدیو در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">feed/video</code> روی دیسک public ذخیره می‌شوند و در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">media_paths</code> یکی می‌شوند.',
        ],
        [
            'icon' => 'forum',
            'label' => 'مدیریت ارتباط: نظرات',
            'hint' => 'روی صفحهٔ ویرایش، نظرات فید ظاهر می‌شوند — زنجیره‌ای (نظر + پاسخ) و گروه‌بندی‌شده بر اساس نوع. هر ردیف فرستنده، تعداد پاسخ‌ها و زمان را نشان می‌دهد. دکمهٔ «مشاهده» متن کامل نظر و زنجیرهٔ پاسخ‌ها را در مودال باز می‌کند. ویرایش و حذف در دسترس است.',
        ],
        [
            'icon' => 'how_to_vote',
            'label' => 'مدیریت ارتباط: نظرسنجی (رأی‌ها)',
            'hint' => 'رأی‌ها به‌ازای هر رأی‌دهنده تجمیع می‌شوند: گزینه‌های انتخاب‌شده (با GROUP_CONCAT) و تعداد رأی‌های او. فیلتر «نوع رأی‌دهنده» رأی‌های تک‌انتخابی را از چندانتخابی جدا می‌کند. حذف یک رأی‌دهنده، همهٔ رأی‌های او در این فید را پاک می‌کند.',
        ],
        [
            'icon' => 'add_reaction',
            'label' => 'مدیریت ارتباط: واکنش‌ها',
            'hint' => 'هر رأی‌دهنده یک ایموجی ثبت کرده است (ایموجی در پایگاه به‌صورت bin2hex ذخیره می‌شود تا با کلید یکتا تداخل نکند). جدول بر اساس ایموجی گروه‌بندی می‌شود. مشاهده و حذف در دسترس است.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'با اکشن گروهی «خروجی Excel»، فیدهای انتخاب‌شده صادر می‌شوند — شناسه، نویسنده، دسته، متن پاک‌شده، شمارش نظرات و واکنش‌ها و تاریخ شمسی. رسانه در خروجی نیست.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات مدیریتی شما روی فیدها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما می‌توانید فید بسازید، ویرایش کنید، نظرات/رأی‌ها/واکنش‌ها را بازبینی و حذف کنید و خروجی اکسل بگیرید. حذف یک فید، همهٔ وابسته‌های آن را هم پاک می‌کند.
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
                تغییر دسته از «نظرسنجی» به دستهٔ دیگر، <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">poll_options</code> را <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">null</code> می‌کند ولی رأی‌های قبلی پاک نمی‌شوند — در صورت نیاز خودتان از مدیریت ارتباط حذف کنید.
            </p>
        </div>
    </div>
</div>