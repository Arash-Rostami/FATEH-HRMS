@php
    $ops = [
        [
            'icon' => 'person_add',
            'label' => 'ثبت تیکت — واحد درخواست‌دهنده خودکار پر می‌شود',
            'hint' => 'با انتخاب درخواست‌دهنده، واحد او خودکار پر می‌شود. سپس واحد رسیدگی‌کننده، نوع و حوزهٔ درخواست را انتخاب کنید. هنگام ثبت می‌توانید حداکثر ۳ فایل، هر کدام تا ۱۰ مگابایت، پیوست کنید.',
        ],
        [
            'icon' => 'sync',
            'label' => 'تعیین مسئول و شروع رسیدگی',
            'hint' => 'با تعیین مسئول، وضعیت به «در حال بررسی» تغییر می‌کند. با برداشتن مسئول، تیکت دوباره «باز» می‌شود. مسئول را از اعضای فعال واحد رسیدگی‌کننده انتخاب کنید. وظیفهٔ پیگیری هم با این تغییر به‌روز می‌شود.',
        ],
        [
            'icon' => 'insights',
            'label' => 'بستن تیکت و ثبت نتیجه',
            'hint' => 'بستن تیکت قطعی است؛ پس از آن امکان باز کردن دوباره، تغییر مسئول یا ارسال پاسخ وجود ندارد. در فهرست، دکمهٔ «بستن تیکت» برای موارد در حال بررسیِ مجاز نمایش داده می‌شود و بدون پرسش تأیید می‌بندد. در صفحهٔ ویرایش، همین دکمه همهٔ تغییرات ذخیره‌نشدهٔ فرم را هم ذخیره می‌کند و بدون پرسش تأیید می‌بندد. می‌توانید وضعیت را هم روی «بسته‌شده» بگذارید و ذخیره کنید. اثربخشی اختیاری است. تاریخ بسته‌شدن و نتیجهٔ اقدام خودکار از روی گفتگو ثبت می‌شوند. تیکت لغوشده امتیاز رضایت نمی‌خواهد.',
        ],
        [
            'icon' => 'assignment_ind',
            'label' => 'به عهده گرفتن رسیدگی',
            'hint' => 'در فهرست، «شروع رسیدگی توسط من» برای تیکت باز و افراد مجاز واحد نمایش داده می‌شود. با یک کلیک و بدون تأیید، شما مسئول می‌شوید و وضعیت به «در حال بررسی» تغییر می‌کند. این اقدام در گفتگو ثبت می‌شود و وظیفهٔ پیگیری هم ساخته می‌شود.',
        ],
        [
            'icon' => 'assignment',
            'label' => 'وظیفهٔ پیگیری تیکت',
            'hint' => 'با تعیین مسئول، یک وظیفهٔ پیگیری با همان مسئول و مهلت ساخته می‌شود. تغییر مسئول، مهلت یا اولویت تیکت، وظیفه را هم به‌روز می‌کند. با برداشتن مسئول یا ارجاع، وظیفه کنار گذاشته می‌شود؛ با تعیین مسئول تازه برمی‌گردد و با بستن تیکت انجام‌شده می‌شود. حذف تیکت، وظیفه و پاسخ‌ها را هم حذف می‌کند. گفتگو را از خود تیکت ادامه دهید.',
        ],
        [
            'icon' => 'calendar_today',
            'label' => 'مهلت رسیدگی — تاریخ شمسی و ساعت',
            'hint' => 'تاریخ شمسی و ساعت را جدا وارد کنید؛ ساعت پیش‌فرض ۰۸:۰۰ است. در فهرست، مهلت گذشته یا پایان دیرهنگام با قرمز، پایان در مهلت با سبز و مهلت آینده با آبی مشخص می‌شود.',
        ],
        [
            'icon' => 'lightbulb',
            'label' => 'تحلیل تیکت — زبانهٔ «تحلیل تیکت» در پنجرهٔ نمایش',
            'hint' => 'این زبانه زمان انتظار تا شروع رسیدگی، مدت رسیدگی، زمان اولین پاسخ و وضعیت مهلت را نشان می‌دهد. زمان کل با تیکت‌های مشابه مقایسه می‌شود؛ مثلاً «۲ برابر معمول» یعنی دو برابر زمان معمول طول کشیده است. اگر سابقه کافی نباشد، مقایسه نمایش داده نمی‌شود. تیکت‌های لغوشده در مقایسه حساب نمی‌شوند. برای تیکتی که بدون تعیین مسئول بسته شده، زمان انتظار و مدت رسیدگی جداگانه موجود نیست.',
        ],
        [
            'icon' => 'monitoring',
            'label' => 'خلاصهٔ واحد — زبانهٔ «خلاصهٔ واحد»',
            'hint' => 'آمار واحد رسیدگی‌کننده در ۹۰ روز اخیر را می‌بینید: تعداد تیکت‌ها، سرعت پاسخ و بسته‌شدن، حوزه‌های پرتکرار و زمان‌بر، تیکت‌های مسئولان و میانگین امتیازها. تعداد تیکت‌های بازِ قدیمی و بدون مسئول، همهٔ تیکت‌های باز را شامل می‌شود؛ حتی قدیمی‌تر از ۹۰ روز. آمار ممکن است تا ۱۰ دقیقه تأخیر داشته باشد.',
        ],
        [
            'icon' => 'download',
            'label' => 'خروجی اکسل',
            'hint' => 'تیکت‌ها را انتخاب کنید و «خروجی اکسل» را بزنید. «انتخاب همه» همهٔ موارد فهرست فعلی با فیلترهای شما را انتخاب می‌کند. خروجی شامل مشخصات، شرح، گفتگو، امتیازها و پیوند پیوست‌هاست و به شکل اکسل یا CSV دریافت می‌شود. ستون‌های دارای پسوند (key)، کد ثابت نوع و حوزه هستند؛ برای خواندن عادی از ستون‌های فارسی کنار آن‌ها استفاده کنید.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">admin_panel_settings</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">عملیات مدیریتی شما روی تیکت‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما می‌توانید تیکت بسازید، مسئول تعیین کنید، مهلت بگذارید، اثربخشی را بازبینی کنید، تیکت را ببندید و خروجی اکسل بگیرید. با تعیین مسئول، رسیدگی شروع می‌شود و با بستن تیکت پایان می‌یابد.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">عملیات فرم و ویرایش</p>
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
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-error)]">warning</span>
                بخش «گفتگو و اقدامات» در صفحهٔ ویرایش هم از دسترسی‌های تیکت پیروی می‌کند. اگر پیام «دسترسی ندارید» دیدید، نقش و دسترسی شما به همان تیکت را بررسی کنید.
            </p>
        </div>
    </div>
</div>
