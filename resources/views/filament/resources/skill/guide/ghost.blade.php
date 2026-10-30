@php
    $d3 = convertToPersian('3');

    $lifecycle = [
        ['icon' => 'search', 'label' => 'کاربر جستجو می‌کند', 'hint' => 'وقتی کاربر در زبانهٔ «استعدادها» یا فهرست استعدادها مهارتی را جستجو می‌کند که در کاتالوگ فعال نیست، جستجوی او در پس‌زمینه ثبت می‌شود.'],
        ['icon' => 'auto_awesome', 'label' => 'مهارت تأمین‌نشده ثبت می‌شود', 'hint' => 'اگر مهارتی با آن نام (فارسی یا انگلیسی) وجود نداشت، یک مهارت «تأمین‌نشده» ثبت می‌شود: غیرفعال است، تعداد جستجوی آن یک و زمان آخرین جستجو همین لحظه است. این مهارت در کاتالوگ کاربر دیده نمی‌شود.'],
        ['icon' => 'trending_up', 'label' => 'تکرارِ جستجو شمارش را بالا می‌برد', 'hint' => 'هر بار که کاربرِ دیگری همان نام را جستجو کند، تعداد و زمان آخرین جستجوی همان مهارت تأمین‌نشده به‌روز می‌شود — بدون ساخت مورد جدید. این شمارش به شما نشان می‌دهد کدام مهارت‌ها تقاضای واقعی دارند.'],
        ['icon' => 'verified_user', 'label' => 'شما فعال می‌کنید', 'hint' => 'شما مهارت را در زبانهٔ «مهارت‌های جستجوشده» پیدا می‌کنید و با «ساخت مهارت» و نوشتن همان نام، آن را فعال می‌کنید (زبانهٔ «اشتراک نام و فعال‌سازی» را ببینید) — مورد تکراری ساخته نمی‌شود.'],
    ];

    $tabs = [
        ['label' => 'کاتالوگ', 'icon' => 'bolt', 'hint' => 'مهارت‌های فعال — همان‌هایی که کاربر در پنل خود می‌بیند. این زبانه هنگام باز شدن فهرست نمایش داده می‌شود.'],
        ['label' => 'غیرفعال', 'icon' => 'remove_circle', 'hint' => 'مهارت‌های غیرفعال. عدد خاکستری کنار زبانه تعداد آن‌ها را نشان می‌دهد. با ویرایش مهارت می‌توانید آن را دوباره فعال کنید.'],
        ['label' => 'مهارت‌های جستجوشده', 'icon' => 'auto_awesome', 'hint' => 'فقط مهارت‌های تأمین‌نشده. عدد زرد کنار زبانه تعداد آن‌ها را نشان می‌دهد. کاربران این مهارت‌ها را جستجو کرده‌اند و منتظر فعال‌سازی شما هستند.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">auto_awesome</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">مهارت‌های «تأمین‌نشده» از جستجوی کاربران ثبت می‌شوند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        وقتی کاربری مهارتی را جستجو می‌کند که در کاتالوگ نیست، سیستم آن را به‌عنوان مهارت تأمین‌نشده ثبت می‌کند تا تقاضا مشخص شود — بدون اینکه به کاربر چیزی بگوید. این رکوردها در زبانهٔ «مهارت‌های جستجوشده» جمع می‌شوند و به شما می‌گویند کدام مهارت‌ها را باید به کاتالوگ بیفزایید. شما مهارت تأمین‌نشده را دستی نمی‌سازید؛ فقط فعال می‌کنید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">search</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">مراحل یک مهارت تأمین‌نشده</p>
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
                عدد کنار زبانه‌ها همه با یک بار خواندن اطلاعات محاسبه می‌شود؛ پس فهرست سریع باز می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">tab</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">{{ $d3 }} زبانهٔ فهرست — تفکیک وضعیت‌ها</p>
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