@php
    $ops = [
        [
            'icon' => 'view_carousel',
            'label' => 'ساخت و ویرایش فید',
            'hint' => 'فرم دو بخش دارد: «محتوا» با ویرایشگر متن (رنگ متن، تیتر، تراز، فهرست، نقل‌قول، هایلایت، پیوند) و «دسته‌بندی و محتوا» برای انتخاب دسته و رسانه. نویسنده از ابتدا خودِ شما است و در ویرایش قابل تغییر نیست.',
        ],
        [
            'icon' => 'ballot',
            'label' => 'نظرسنجی و گزینه‌ها',
            'hint' => 'وقتی دسته «نظرسنجی» انتخاب شود، شبکهٔ تنظیمات (تک‌انتخابی/چندانتخابی + فعال بودن نظر و واکنش) و repeater گزینه‌ها (حداقل ۲، حداکثر ۱۰) ظاهر می‌شوند. هنگام ذخیره، تنظیمات و گزینه‌ها در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">poll_options</code> به‌صورت یک آرایه بسته‌بندی می‌شوند و هنگام پر کردن فرم، دوباره از هم باز می‌شوند.',
        ],
        [
            'icon' => 'photo_library',
            'label' => 'تصویر و ویدیو',
            'hint' => 'بخش تصویر تا ۸ پرونده (۵MB هرکدام، فقط image/*) و بخش ویدیو یک پرونده (۹۵MB). تصاویر در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">feed/image</code> و ویدیو در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">feed/video</code> روی دیسک public ذخیره می‌شوند و در <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">media_paths</code> یکی می‌شوند.',
        ],
        [
            'icon' => 'forum',
            'label' => 'بخش نظرات',
            'hint' => 'در صفحهٔ ویرایش، نظرات فید دیده می‌شوند — نظر و پاسخ‌ها به‌صورت زنجیره، گروه‌بندی‌شده بر اساس نوع. هر مورد فرستنده، تعداد پاسخ‌ها و زمان را نشان می‌دهد. دکمهٔ «مشاهده» متن کامل نظر و پاسخ‌ها را در یک پنجره باز می‌کند. ویرایش و حذف در دسترس است.',
        ],
        [
            'icon' => 'how_to_vote',
            'label' => 'بخش نظرسنجی (رأی‌ها)',
            'hint' => 'رأی‌ها برای هر رأی‌دهنده یک‌جا نمایش داده می‌شود: گزینه‌های انتخاب‌شده و تعداد رأی‌های او. فیلتر «نوع رأی‌دهنده» رأی‌های تک‌انتخابی را از چندانتخابی جدا می‌کند. حذف یک رأی‌دهنده، همهٔ رأی‌های او در این فید را پاک می‌کند.',
        ],
        [
            'icon' => 'add_reaction',
            'label' => 'بخش واکنش‌ها',
            'hint' => 'هر کاربر یک ایموجی ثبت کرده است. جدول بر اساس ایموجی گروه‌بندی می‌شود. مشاهده و حذف در دسترس است.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'با عملیات گروهی «خروجی اکسل»، فیدهای انتخاب‌شده خروجی گرفته می‌شوند — شناسه، نویسنده، دسته، متن پاک‌شده، شمارش نظرات و واکنش‌ها و تاریخ شمسی. رسانه در خروجی نیست.',
        ],
        [
            'icon' => 'toggle_on',
            'label' => 'وضعیت و مخاطبان',
            'hint' => 'کلید روشن/خاموش «وضعیت» (پیش‌فرض فعال) فید غیرفعال را فقط از پنل کاربر مخفی می‌کند — فهرست، نشان و اعلان خوانده‌نشده، جستجو و لینک مستقیم؛ ادمین همیشه همه را می‌بیند و نظرات، واکنش‌ها و رأی‌ها دست‌نخورده می‌مانند. «بخش‌های مخاطب» (departments) خالی = عمومی؛ با انتخاب واحد، فید فقط برای کاربران همان واحدها دیده می‌شود و اعلان فقط به آن‌ها می‌رسد. فیدهای قدیمی بدون تغییر عمومی و فعال می‌مانند. حذف همچنان در دسترس است.',
        ],
        [
            'icon' => 'visibility_off',
            'label' => 'فعال‌سازی/غیرفعال‌سازی گروهی',
            'hint' => '«فعال‌سازی انتخاب‌شده‌ها» و «غیرفعال‌سازی انتخاب‌شده‌ها» در منوی عملیات گروهی فقط برای کسانی که مجوز ویرایش دارند دیده می‌شود. هر مورد جداگانه به‌روز می‌شود تا اعلان‌ها و تغییرات وابسته درست انجام شوند. زبانه‌های «فعال/غیرفعال»، فیلترهای وضعیت و مخاطب و گروه‌بندی وضعیت نیز در فهرست هستند.',
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
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اکشن‌های ردیف و بخش</p>
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
                تغییر دسته از «نظرسنجی» به دستهٔ دیگر، <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">poll_options</code> را <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">null</code> می‌کند ولی رأی‌های قبلی پاک نمی‌شوند — در صورت نیاز خودتان از بخش حذف کنید.
            </p>
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2 mt-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                زبانه‌های نظرها، نظرسنجی‌ها و واکنش‌ها ماژول جدا ندارند؛ هر کس بتواند فید را باز کند آن‌ها را می‌بیند و در صفحهٔ ویرایش می‌تواند مدیریتشان کند.
            </p>
        </div>
    </div>
</div>