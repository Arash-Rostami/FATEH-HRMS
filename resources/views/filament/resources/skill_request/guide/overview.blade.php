@php
    $d14 = convertToPersian('14');

    $columns = [
        [
            'icon' => 'person_search',
            'label' => 'درخواست‌کننده (user)',
            'hint' => 'کاربری که مهارت را پیشنهاد داده یا انتخاب کرده — از پروفایل او بارگذاری می‌شود (user.profile.department).',
        ],
        [
            'icon' => 'workspace_premium',
            'label' => 'مهارت (skill)',
            'hint' => 'نام مهارت از جدول skills. اگر درخواست، پیشنهادِ نام جدید باشد (requested_name)، نام پیشنهادی به‌جای نام کاتالوگ نشان داده می‌شود.',
        ],
        [
            'icon' => 'fact_check',
            'label' => 'وضعیت (status)',
            'hint' => 'در حال بررسی (Pending) / تایید شده (Approved) / رد شده (Rejected). زبانه و فیلتر پیش‌فرض روی «در حال بررسی» است.',
        ],
        [
            'icon' => 'leaderboard',
            'label' => 'سطح (tier)',
            'hint' => 'فقط برای درخواست‌های تأییدشده محاسبه می‌شود — تأییدشده / فعال / آماده مشارکت. برای ردیف‌های در حال بررسی یا ردشده خط تیره است.',
        ],
        [
            'icon' => 'groups',
            'label' => 'تأیید همکاران (endorsements_count)',
            'hint' => 'تأییدِ همکارانِ همان مهارت. ستون به‌صورت پیش‌فرض پنهان است؛ از تنظیمات ستون بازش کنید.',
        ],
        [
            'icon' => 'schedule',
            'label' => 'آخرین استفاده / تاریخ درخواست',
            'hint' => 'آخرین استفاده (پیش‌فرض پنهان) و تاریخ ایجاد درخواست — هر دو شمسی‌سازی می‌شوند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">این صفحه صفِ تأیید درخواست‌های مهارت است — فقط تأیید یا رد، بدون ساخت و ویرایش</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف یک «درخواست مهارت» از سوی یک کاربر است (جدول skill_user با کلید یکتای user_id و skill_id). کاربر مهارت را از زبانهٔ «استعدادها» در پروفایل خود پیشنهاد می‌دهد یا از کاتالوگ انتخاب می‌کند → درخواست با وضعیت Pending وارد این صف می‌شود. شما اینجا فقط تأیید یا رد می‌کنید؛ صفحهٔ ساخت، ویرایش یا حذف مستقل وجود ندارد. درخواستِ ردشده با «درخواست مجدد» از سوی کاربر دوباره باز می‌گشاید (همان ردیف، نه ردیف جدید) — پس هیچ‌گاه دو ردیف برای یک جفت کاربر-مهارت ساخته نمی‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">table</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">ستون‌های جدول صف</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($columns as $c)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $c['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $c['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $c['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                مرتب‌سازی پیش‌فرض: ابتدا درخواست‌های «در حال بررسی»، سپس قدیمی‌ترین‌ها — تا قدیمی‌ترین درخواست معوق بالای جدول بماند. فیلترِ کهنه، درخواست‌های در حال بررسیِ بیش از {{ $d14 }} روزه را جدا نشان می‌دهد.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">linked_services</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">از کجا درخواست می‌آید و کجا تأیید می‌شود؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">account_box</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">پنل کاربر — زبانهٔ «استعدادها»</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">درخواست از پروفایل کاربر (/profile) و زبانهٔ «استعدادها» ثبت می‌شود: انتخاب از کاتالوگ یا پیشنهاد نام جدید. تمامِ چرخهٔ پیشنهاد، تأیید همکاران، «استفاده اخیر»، خصوصی/عمومی و «آماده راهنمایی» در همان زبانه است — راهنمای آن زبانه از قبل موجود است.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">verified_user</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">پنل ادمین — همین صف</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">این صفحه تنها مسیرِ تأیید/رد درخواست‌های در صف است. یک مسیر ادمینیِ دیگر هم هست: زبانهٔ «مهارت‌ها» در صفحهٔ پروفایلِ کاربر (ProfileResource) که ادمین مستقیماً مهارت به کاربر اضافه می‌کند — آن مسیر از این صف عبور نمی‌کند و ردیف پیش‌فرض «تأیید شده» ثبت می‌شود.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">forum</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">اعلان‌ها در زبانهٔ مهارت کاربر، نه زنگ ادمین</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">اعلانِ تأیید/رد در کشِ اختصاصی کاربر نگه‌داری می‌شود و فقط در زبانهٔ «استعدادها»ی همان کاربر نمایش داده می‌شود — در زنگِ اعلانِ سراسری ظاهر نمی‌شود.</p>
                </div>
            </div>
        </div>
    </div>
</div>