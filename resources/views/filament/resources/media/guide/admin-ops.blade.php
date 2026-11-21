@php
    $ops = [
        [
            'icon' => 'visibility',
            'label' => 'مشاهده',
            'hint' => 'دکمهٔ «مشاهده» صفحهٔ جزئیات رسانه را باز می‌کند: فیلدها، فایل‌ها و تاریخ‌های شمسی.',
        ],
        [
            'icon' => 'edit',
            'label' => 'ویرایش',
            'hint' => 'فرم رسانه با دو زبانه: «اطلاعات اصلی» (نوع، عنوان، موضوع، آیکون، واحد، نویسنده، وضعیت، تاریخ‌ها، سری والد، مواد همراه، توضیح) و «فایل‌ها» (جلد، فایل رسانه، مدت، رونوشت، فصل‌ها). زبانهٔ باز هنگام بارگذاری دوبارهٔ صفحه حفظ می‌شود.',
        ],
        [
            'icon' => 'delete',
            'label' => 'حذف',
            'hint' => 'با حذف سری، جلسه‌هایش هم حذف و فایل‌های همهٔ موارد پاک می‌شود. برای پنهان‌کردن موقت، به‌جای حذف وضعیت را غیرفعال کنید.',
        ],
        [
            'icon' => 'swap_horiz',
            'label' => 'سوییچ بین بخش‌ها',
            'hint' => 'با دکمه‌های سوییچ بالای فهرست بین «گزارشات»، «رسانه‌ها»، «پیشرفت» و «گزارش‌های هوشمند» جابه‌جا می‌شوید؛ همه زیر یک مدخل واحد در نوار کنار قرار دارند.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'از منوی عملیات گروهی روی موردهای انتخاب‌شده خروجی اکسل بگیرید. در «پیشرفت» ستون‌ها: کاربر، عنوان، سری، موقعیت، وضعیت، آخرین مشاهده.',
        ],
    ];
    $form = [
        [
            'icon' => 'movie',
            'label' => 'فایل رسانه و مدت',
            'hint' => 'فایل صوتی یا ویدیویی را بارگذاری کنید؛ مدت خودکار خوانده می‌شود و در فیلد «مدت» قرار می‌گیرد و قابل ویرایش است. فایل‌های VBR ممکن است مدت دقیق ندهند.',
        ],
        [
            'icon' => 'account_tree',
            'label' => 'ساخت سری',
            'hint' => 'اول یک مورد با نوع «سری» بسازید؛ سپس در صفحهٔ ویرایش آن، از بخش «جلسه‌های سری» جلسه‌ها را اضافه و با کشیدن و رها کردن مرتب کنید.',
        ],
        [
            'icon' => 'schedule',
            'label' => 'انتشار تدریجی',
            'hint' => 'برای جلسه‌های آینده، «تاریخ انتشار» را آینده‌تر بگذارید؛ جلسه در فهرست با برچسب تاریخ باز شدن نمایش داده می‌شود اما تا آن روز پخش نمی‌شود.',
        ],
        [
            'icon' => 'subtitles',
            'label' => 'رونوشت و فصل‌ها',
            'hint' => 'برای هر جلسه می‌توانید دو فایل با فرمت vtt بارگذاری کنید: رونوشت (متن همزمان) و فصل‌ها (نقاط شروع روی نوار پخش).',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">کار شما: ساخت رسانه، بارگذاری فایل و انتشار</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        ساخت سری: ابتدا موردِ والد با نوع «سری» را بسازید، بعد جلسه‌ها را از بخش «جلسه‌های سری» زیر صفحهٔ ویرایش آن اضافه کنید. جستجوی سراسری پنل رسانه‌ها را با عنوان و موضوع پیدا می‌کند و مستقیم به ویرایش می‌رود.
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
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">edit</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فرم ویرایش — دو زبانه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($form as $f)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $f['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $f['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $f['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                فایل‌های رسانه عمومی ذخیره می‌شوند؛ کنترل دسترسی از راه «وضعیت»، «تاریخ انتشار» و «تاریخ انقضا» انجام می‌شود، نه از نشانی فایل.
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">tips_and_updates</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            برای انتشار فوری، «وضعیت» را فعال بگذارید و «تاریخ انتشار» را خالی یا گذشته — رسانه بلافاصله در صفحهٔ «رسانه» کاربر ظاهر می‌شود. برای دنباله‌دار کردن یک سری، تاریخ انتشار جلسه‌های بعدی را آینده‌تر بگذارید.
        </p>
    </div>
</div>
