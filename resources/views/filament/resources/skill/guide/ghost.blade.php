@php
    $d3 = convertToPersian('3');

    $lifecycle = [
        ['icon' => 'search', 'label' => 'کاربر جستجو می‌کند', 'hint' => 'وقتی کاربر در زبانهٔ «استعدادها» یا دایرکتوری استعدادها مهارتی را جستجو می‌کند که در کاتالوگ فعال نیست، جستجوی او به یک Job صف‌بند (LogMissingSkillJob) ارسال می‌شود.'],
        ['icon' => 'auto_awesome', 'label' => 'ghost ساخته می‌شود', 'hint' => 'اگر مهارتی با آن نام (فارسی یا انگلیسی) وجود نداشت، یک رکورد ghost ساخته می‌شود: is_ghost = true، is_active = false، search_count = ۱، last_searched_at = همین حالا. این رکورد در کاتالوگ کاربر ظاهر نمی‌شود.'],
        ['icon' => 'trending_up', 'label' => 'تکرارِ جستجو شمارش را بالا می‌برد', 'hint' => 'هر بار که کاربرِ دیگری همان نام را جستجو کند، search_count و last_searched_atِ همان ghost به‌روز می‌شود (recordSearch) — بدون ساختِ رکوردِ جدید. این شمارش به شما نشان می‌دهد کدام مهارت‌ها تقاضای واقعی دارند.'],
        ['icon' => 'verified_user', 'label' => 'ادمین فعال می‌کند', 'hint' => 'شما از زبانهٔ «مهارت‌های جستجوشده» رکورد ghost را پیدا می‌کنید و با «ساخت مهارت» و واردکردنِ همان نام، ghost به یک مهارتٔ فعال ارتقا پیدا می‌کند (see زبانهٔ «اشتراک نام و فعال‌سازی») — رکورد دوگانه ساخته نمی‌شود.'],
    ];

    $tabs = [
        ['label' => 'کاتالوگ', 'icon' => 'bolt', 'hint' => 'مهارت‌های فعال و غیر-ghost — همان‌هایی که کاربر در پنل خود می‌بیند. پیش‌فرضِ فهرست.'],
        ['label' => 'غیرفعال', 'icon' => 'remove_circle', 'hint' => 'مهارت‌های غیرفعال و غیر-ghost. نشانِ شمارشیِ خاکستری تعداد را نشان می‌دهد. می‌توانید با ویرایش، is_active را روشن کنید.'],
        ['label' => 'مهارت‌های جستجوشده', 'icon' => 'auto_awesome', 'hint' => 'فقط رکوردهای ghost. نشانِ زرد (warning) تعداد را نشان می‌دهد. این‌ها مهارت‌هایِ تأمین‌نشده‌اند که کاربران جستجو کرده‌اند و منتظرِ فعال‌سازیِ شما هستند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">auto_awesome</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">مهارت‌های «تأمین‌نشده» از جستجوی کاربران به‌وجود می‌آیند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        وقتی کاربری مهارتی را جستجو می‌کند که در کاتالوگ نیست، سیستم یک رکورد ghost می‌سازد تا تقاضا را ثبت کند — بدون اینکه به کاربر چیزی بگوید. این رکوردها در زبانهٔ «مهارت‌های جستجوشده» جمع می‌شوند و به شما می‌گویند کدام مهارت‌ها را باید به کاتالوگ بیفزایید. شما هرگز ghost را دستی نمی‌سازید؛ فقط فعال می‌کنید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">search</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">چرخهٔ عمرِ یک ghost</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($lifecycle as $step)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $step['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $step['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $step['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                شمارشِ زبانه‌ها از یک کوئریِ واحد با SUM CASE و موموایزِ once() محاسبه می‌شود — یعنی نشانِ زبانه‌های شمارشی با یک رفت‌وبرگشتِ دیتابیس تضمین می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tab</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">{{ $d3 }} زبانهٔ فهرست — تفکیکِ وضعت‌ها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($tabs as $t)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">{{ $t['icon'] }}</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-0.5">
                        <p class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)] leading-5 font-medium">{{ $t['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>